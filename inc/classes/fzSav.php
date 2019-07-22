<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 21/05/2019
 * Time: 12:51
 */

namespace classes;

define('_NO_REPLY_', 'no-reply@freezone.clik');

/**
 * @property  status_product
 * @property  product_provider
 */
class fzSav
{
    public $ID;
    public $date_add = null;
    private static $fields = [
        'client',
        'product',
        'mark',
        'status_product',
        'product_provider',
        'date_purchase',
        'bill',
        'serial_number',
        'reference', // Reference
        'auctor',
        'description',
        'status_sav',
        'approximate_time',
        'quotation_ref' // Reference du devis (SAGE)
    ];
    public $status_product;
    public $product_provider;

    public function __construct ($sav_id, $api = false)
    {
        $this->ID = $sav_id;
        foreach ( self::$fields as $key ) {
            if ($key === 'auctor' || $key === 'reference') {
                $value = get_post_meta($sav_id, 'sav_' . $key, true);
                if ($api && $key === 'auctor') {
                    $user_controller = new \WP_REST_Users_Controller();
                    $request = new \WP_REST_Request();
                    $request->set_param('context', 'edit');

                    $this->$key = $user_controller->prepare_item_for_response(new \WP_User((int)$value), $request);
                    continue;
                }

                $this->$key = $value;
                continue;
            }

            $field_value = get_field($key, $sav_id);
            $this->$key = $field_value;
        }
        $post_sav = get_post($sav_id);
        $this->date_add = $post_sav->post_date;
    }

    public static function get_fields ()
    {
        return self::$fields;
    }

}

add_action('init', function() {
    // Envoyer un mail (Nouvelle article SAV)
    add_action('wp_ajax_new_sav', function() {
        if (empty($_POST['post_id'])) wp_send_json_error("Parametre manquant (post_id)");
        $post_id = intval($_POST['post_id']);
        if (get_post_type($post_id) === 'fz_sav') {
            global $Engine;

            $no_reply = _NO_REPLY_;

            $client_id = get_post_meta($post_id, 'sav_auctor', true);
            $client_id = intval($client_id);
            $client = get_userdata($client_id);

            $Sav = new fzSav($post_id);
            $message = '';
            // hors garantie
            $status_product = is_array($Sav->status_product) ? (int)$Sav->status_product['value'] : (int)$Sav->status_product;
            $product_provider = is_array($Sav->product_provider) ? (int)$Sav->product_provider['value'] : (int)$Sav->product_provider;

            if ($status_product == 2) {
                $message = "Cher Client, <br> <br> Votre demande sera étudiée sous 24 heures jours ouvrables, " .
                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier car " .
                    "nous ne réparons pas chez le client. Une fois le matériel en notre possession le " .
                    "technicien donnera son diagnostic qui vous sera envoyé par email. <br>Soit vous acceptez " .
                    "que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " .
                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter " .
                    "du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
            }

            // Sous garantie & freezone
            if ($status_product == 1 && $product_provider == 1) {
                $message = "Cher Client, <br> <br> Votre demande sera étudiée sous 24 heures jours ouvrables, " .
                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " .
                    "car nous ne réparons pas chez le client.";
            }

            // Sous garantie & autre fournisseur
            if ($status_product == 1 && $product_provider == 2) {
                $message = "Cher Client, <br> <br> Votre demande sera traitée sous 24 heures jours ouvrables, " .
                    "Pour votre information sachez qu’en nous confiant un produit qui est sous garantie " .
                    "chez un autre revendeur vous risquez de perdre votre garantie chez ce revendeur. <br>" .
                    "Nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " .
                    "car nous ne réparons pas chez le client. <br> Une fois le matériel en notre possession " .
                    "le technicien donnera son diagnostic qui vous sera envoyé par email. <br> Soit vous acceptez" .
                    " que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " .
                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter" .
                    " du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
            }


            $message   = html_entity_decode($message);
            $to        = $client->user_email;
            $subject   = "Cher client - Freezone";
            $headers   = [];
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = "From: Freezone <$no_reply>";
            $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019]);

            // Envoyer un mail
            $result = wp_mail( $to, $subject, $content, $headers );
            if ($result) {
                wp_send_json_success("Email envoyer avec seccès");
            } else {
                wp_send_json_error("Une erreur s'est produit: {$message}");
            }
        }
    });
});

add_action('rest_api_init', function() {
    foreach ( fzSav::get_fields() as $field ) {
        register_rest_field('fz_sav', $field, [
            'update_callback' => function ($value, $object, $field_name) {

                switch ($field_name) {
                    case 'product_provider':
                        // Ajouter la reference
                        update_post_meta($object->ID, 'sav_reference', "SAV{$object->ID}");
                        break;
                    case 'auctor':
                    case 'reference':
                        return update_post_meta($object->ID, 'sav_' . $field_name, $value);
                        break;
                    case 'status_sav':

                        /**
                         * Le status du SAV est sur <Terminer>
                         *
                         * Veuillez envoyer une facture au client VVB pour la réparation de la machine XYZ
                         */
                        if (intval($value) === 5) {
                            do_action('sav_status_finish', $object->ID);
                        }

                        /**
                         * Le status du SAV est sur <Ne pas réparer>
                         *
                         * Veuillez envoyer une facture pour le diagnostic au client VVB pour la machine XYZ.
                         */
                        if (intval($value) === 4) {
                            do_action('sav_status_do_not_repair', $object->ID);
                        }

                        break;
                    case 'approximate_time':

                        /**
                         * La date du diagnostic du matériel XYV du client VVB a été de nouveau prolongé au XX/XX/XX
                         */

                        do_action('sav_change_approximate_time', $object->ID, $value);

                        break;
                }
                return update_field($field_name, $value, $object->ID);

            },
            'get_callback' => function ($object, $field_name) {
                if ($field_name === 'auctor' || $field_name === 'reference') {
                    $value = get_post_meta($object['id'], 'sav_' . $field_name, true);
                    if ($field_name === 'auctor') {
                        $user_controller = new \WP_REST_Users_Controller();
                        $request = new \WP_REST_Request();
                        $request->set_param('context', 'edit');
                        $response = $user_controller->prepare_item_for_response(new \WP_User((int)$value), $request);
                        return $response->get_data();
                    } else {
                        return $value;
                    }
                }
                return get_field($field_name, $object['id']);
            }
        ]);
    }
});


add_action('sav_status_finish', function ($post_id) {
    global $Engine;

    $client_id = get_post_meta($post_id, 'sav_auctor', true);
    $client_id = intval($client_id);
    $client = get_userdata($client_id);
    $no_reply = _NO_REPLY_;

    $admins    = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author'] ]);
    $admin_emails = [];
    foreach ($admins->get_results() as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    $Sav = new fzSav($post_id);
    $message   = "Bonjour, <br><br>Veuillez envoyer une facture au client id <b>{$client->ID}</b> ({$client->first_name} 
{$client->last_name}) pour la réparation de la machine {$Sav->product}";
    $message   = html_entity_decode($message);
    $to        = implode(',', $admin_emails);
    $subject   = "#{$post_id} Facture pour la réparation - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );

}, 10, 1);

add_action('sav_status_do_not_repair', function ($post_id) {
    global $Engine;

    $client_id = get_post_meta($post_id, 'sav_auctor', true);
    $client_id = intval($client_id);
    $client = get_userdata($client_id);
    $no_reply = _NO_REPLY_;

    $admins    = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author'] ]);
    $admin_emails = [];
    foreach ($admins->get_results() as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    $Sav = new fzSav($post_id);
    $message   = "Bonjour, <br><br>Veuillez envoyer une facture pour le diagnostic au client id <b>{$client->ID}</b> ({$client->first_name} 
{$client->last_name}) pour la machine {$Sav->product}";
    $message   = html_entity_decode($message);
    $to        = implode(',', $admin_emails);
    $subject   = "#{$post_id} Facture pour le diagnostic  - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );
}, 10, 1);

add_action('sav_change_approximate_time', function ($post_id, $date) {
    global $Engine;

    $client_id = get_post_meta($post_id, 'sav_auctor', true);
    $client_id = intval($client_id);
    $client = get_userdata($client_id);
    $no_reply = _NO_REPLY_;

    $admins    = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author'] ]);
    $admin_emails = [];
    foreach ($admins->get_results() as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    $Sav = new fzSav($post_id);
    $message   = "Bonjour, <br><br>La date du diagnostic du matériel {$Sav->product} du client id <b>{$client->ID}</b> 
({$client->first_name} {$client->last_name}) a été de nouveau prolongé au $date";
    $message   = html_entity_decode($message);
    $to        = implode(',', $admin_emails);
    $subject   = "#{$post_id} Date nouveau prolongé  - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );
}, 10, 2);
