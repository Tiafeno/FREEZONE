<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 21/05/2019
 * Time: 12:51
 */

namespace classes;
define('_NO_REPLY_', 'no-reply@freezone.clik');

class fzSav
{
    public $id = 0;
    public $date_add = null;
    public $guarentee_product = [];
    public $product_provider = [];
    public static $fields = [
        'product', // Nom du produit
        'mark', // Marque et modele
        'guarentee_product', // Garantie du produit
        'garentee', // Le nombre de mois de garantie
        'product_provider', // Fournisseur du produit Freezone ou autre
        'date_purchase', // Date d'achat
        'date_receipt', // Date de réception du matériel par le SAV
        'date_release', // * Date de sortie dans l'atelier
        'date_handling', // * Date de prise en main
        'date_diagnostic_end', // * Date de fin diagnostic
        'bill', // Numero de facture
        'serial_number', // Numero de serie (S/N)
        'description',
        'status_sav', // ... diagnostique, reparation
        //'approximate_time',
        'quotation_ref', // Reference du devis (SAGE)
        //'auctor', // WP_User (json)
        //'reference', // Reference de l'article (fz_sav)
        'accessorie', // number
        'other_accessories_desc'
    ];

    public function __get ($name) {
        if ($name === 'customer') {
            $user_controller = new \WP_REST_Users_Controller();
            $request = new \WP_REST_Request();
            $request->set_param('context', 'edit');
            $customer_id = $this->get_customer_id();
            return $user_controller->prepare_item_for_response(new \WP_User((int)$customer_id), $request);
        }
        if ($name === 'customer_role') {
            $customer_id = $this->get_customer_id();
            $user_data = get_userdata($customer_id);
            return in_array("fz-company", $user_data->roles) ? "Entreprise" : "Particulier";
        }
    }

    public function __construct ($sav_id, $api = false) {
        $this->id = $sav_id;
        foreach ( self::$fields as $key ) {
            $field_value = get_field($key, $sav_id);
            $this->$key = $field_value;
        }
        $post_sav = get_post($sav_id);
        if (is_object($post_sav))
            $this->date_add = $post_sav->post_date;
    }

    public function get_customer_id() {
        $id = get_field("customer", $this->id);
        return $id ? intval($id) : 0;
    }

    public function get_status_string() {
        $status = intval($this->status_sav);
        if (is_nan($status)) return 'Non definie';
        switch ($status) {
            case 1: return 'Diagnostique en cours'; break;
            case 2: return 'Diagnostique fini'; break;
            case 3: return 'Réparation accordée'; break;
            case 4: return 'Réparation refusée'; break;
            case 5: return 'Produit récupéré par le client'; break;
        }
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
            $client = get_userdata(intval($client_id));
            $Sav = new fzSav($post_id);
            $message = '';
            $guarentee_product = is_array($Sav->guarentee_product) ? (int)$Sav->guarentee_product['value'] : (int)$Sav->guarentee_product;
            $product_provider = is_array($Sav->product_provider) ? (int)$Sav->product_provider['value'] : (int)$Sav->product_provider;
            // Hors garantie
            if ($guarentee_product == 2) {
                $message = "Cher Client, <br> <br> Votre demande sera étudiée sous 24 heures jours ouvrables, " .
                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier car " .
                    "nous ne réparons pas chez le client. Une fois le matériel en notre possession le " .
                    "technicien donnera son diagnostic qui vous sera envoyé par email. <br>Soit vous acceptez " .
                    "que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " .
                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter " .
                    "du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
            }
            // Sous garantie & freezone
            if ($guarentee_product == 1 && $product_provider == 1) {
                $message = "Cher Client, <br> <br> Votre demande sera étudiée sous 24 heures jours ouvrables, " .
                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " .
                    "car nous ne réparons pas chez le client.";
            }
            // Sous garantie & autre fournisseur
            if ($guarentee_product == 1 && $product_provider == 2) {
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
            $content   = $Engine->render('@MAIL/default.html', [
                'message' => $message,
                'Year'  => 2019,
                'Phone' => freezone_phone_number
            ]);
            $result = wp_mail( $to, $subject, $content, $headers );
            if ($result) {
                wp_send_json_success("Email envoyer avec succès");
            } else {
                wp_send_json_error("Une erreur s'est produit: {$message}");
            }
        }
    });
});

add_action('rest_api_init', function() {
    $fields = array_merge(fzSav::$fields, ['customer']);
    foreach ( $fields as $field ) {
        register_rest_field('fz_sav', $field, [
            'update_callback' => function ($value, $object, $field_name) {

                switch ($field_name) {
                    case 'status_sav':

                        /**
                         *   1 : Diagnostique en cours
                         *   2 : Diagnostique fini
                         *   3 : Réparation accordée
                         *   4 : Réparation refusée
                         *   5 : Produit récupéré par le client
                         */

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
                if ($field_name === 'auctor' || $field_name === 'reference' || $field_name === 'garentee') {
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
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

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
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

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
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );
}, 10, 2);
