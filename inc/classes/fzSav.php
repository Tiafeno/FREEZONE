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
    public $date_add = null; // Date d'ajout ou demande de SAV
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
        'reference', // Reference de l'article (fz_sav)
        'accessorie', // number
        'other_accessories_desc'// Si l'accessoire est definie sur autre, ce champ contient la valer pour autre
    ];

    public function __get ($name) {
        if ($name === 'customer') {
            $user_controller = new \WP_REST_Users_Controller();
            $request = new \WP_REST_Request();
            $request->set_param('context', 'edit');
            $customer_id = $this->get_customer_id();
            $response = $user_controller->prepare_item_for_response(new \WP_User((int)$customer_id), $request);
            return $response->get_data();
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

    public static function getInstance($sav_id, $api = false) {
        return new self((int) $sav_id, $api);
    }

    // Recupéré l'identifiannt du client
    public function get_customer_id() {
        $id = get_field("customer", $this->id);
        return $id ? intval($id) : 0;
    }

    // Retourne la description exacte sur le status actuelle de l'SAV
    public function get_status_string() {
        $status = intval($this->status_sav);
        if (is_nan($status)) return 'Non definie';
        switch ($status) {
            case 1: return 'Diagnostique en cours'; break;
            case 2: return 'Diagnostique fini'; break;
            case 3: return 'Réparation accordée'; break;
            case 4: return 'Réparation refusée'; break;
            case 5: return 'Produit récupéré par le client'; break;
            default: return 'Aucun'; break;
        }
    }
}

add_action('init', function() {
    $register_metas = ['has_edit', 'editor_accessorie', 'editor_other_accessorie_desc'];
    // Reference: https://developer.wordpress.org/reference/functions/register_meta/
    register_meta('post', "has_edit", [
        'object_subtype' => 'fz_sav',
        'type' => 'boolean',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', "editor_accessorie", [
        'object_subtype' => 'fz_sav',
        'type' => 'number',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', "editor_other_accessorie_desc", [
        'object_subtype' => 'fz_sav',
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', "editor_breakdown", [
        'object_subtype' => 'fz_sav',
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    // Envoyer un mail (Nouvelle article SAV)
    add_action('wp_ajax_new_sav', function() {
        $no_reply = _NO_REPLY_;
        if (empty($_POST['post_id'])) wp_send_json_error("Parametre manquant (post_id)");
        $post_id = intval($_POST['post_id']);
        if (get_post_type($post_id) === 'fz_sav') {
            global $Engine;

            // Mettre à jour la reference
            // Le SAV est ajouté depuis le RESTFull API, donc la mise à jour nécessite son ID
            update_field('reference', "SAV{$post_id}", $post_id);

            $Sav = new fzSav($post_id);
            $customer_id = $Sav->get_customer_id();
            $customer = get_userdata( $customer_id );
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
            // Ne pas envoyer si le message à envoyer est vide
            if (empty($message)) return wp_send_json_error( "Email non envoyer. Aucun messsage n'est formulé pour cette demande" );

            $message   = html_entity_decode($message);
            $to        = $customer->user_email;
            $subject   = "Cher client - Freezone";
            $headers   = [];
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = "From: Freezone <$no_reply>";
            $content   = $Engine->render('@MAIL/default.html', [
                'message' => $message,
                'Year'  => date('Y'),
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
    add_filter('rest_fz_sav_query', function($args, $request) {
        $args += array(
            'meta_key'   => $request['meta_key'],
            'meta_value' => $request['meta_value'],
            'meta_query' => $request['meta_query'],
        );
        return $args;
    }, 99, 2);

    $fields = array_merge(fzSav::$fields, ['customer']);
    foreach ( $fields as $field ) {
        register_rest_field('fz_sav', $field, [
            'update_callback' => function ($value, $object, $field_name) {
                if (!in_array($field_name, fzSav::$fields)) return false;
                switch ($field_name) {
                    case 'status_sav':

                        /**
                         * /////////// Deprecate status //////////
                         *   1 : Diagnostic réalisé
                         *   2 : Diagnostic non réalisé
                         *   3 : A réparer
                         *   4 : Ne pas réparer
                         *   5 : Terminer
                         * //////////////////////////////////////
                         */

                        /**
                            case 1:  'Diagnostique en cours'
                            case 2:  'Diagnostique fini'
                            case 3:  'Réparation accordée'
                            case 4:  'Réparation refusée'
                            case 5:  'Produit récupéré par le client'
                        */

                        /**
                         * Le status du SAV est sur <Produit récupéré par le client>
                         *
                         * Veuillez envoyer une facture au client VVB pour la réparation de la machine XYZ
                         */
                        if (intval($value) === 5) {
                            do_action('sav_status_release', $object->ID);
                        }

                        /**
                         * Le status du SAV est sur <Réparation refusée>
                         *
                         * Veuillez envoyer une facture pour le diagnostic au client VVB pour la machine XYZ.
                         */
                        if (intval($value) === 4) {
                            do_action('sav_status_do_not_repair', $object->ID);
                        }

                        break;
                }
                return update_field($field_name, $value, $object->ID);

            },
            'get_callback' => function ($object, $field_name) {
                if ($field_name === 'customer' ) {
                    return fzSav::getInstance($object['id'])->customer;
                }
                return get_field($field_name, $object['id']);
            }
        ]);
    }
});


add_action('sav_status_release', function ($post_id) {
    global $Engine;

    $client_id = get_post_meta($post_id, 'customer', true);
    $client_id = intval($client_id);
    $client = get_userdata($client_id);
    $no_reply = _NO_REPLY_;

    $admins    = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author'] ]);
    $admin_emails = [];
    foreach ($admins->get_results() as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    $Sav = new fzSav($post_id);
    $message   = "Bonjour, <br><br>Veuillez envoyer une facture au client id <b>{$client->ID}</b> ({$client->first_name} {$client->last_name}) pour la réparation de la machine {$Sav->product}";
    $message   = html_entity_decode($message);
    $to        = implode(',', $admin_emails);
    $subject   = "SAV{$post_id} Facture pour la réparation - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );

}, 10, 1);

add_action('sav_status_do_not_repair', function ($post_id) {
    global $Engine;

    $client_id = get_post_meta($post_id, 'customer', true);
    $client_id = intval($client_id);
    $client = get_userdata($client_id);
    $no_reply = _NO_REPLY_;

    $admins    = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author'] ]);
    $admin_emails = [];
    foreach ($admins->get_results() as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    $Sav = new fzSav($post_id);
    $message   = "Bonjour, <br><br>Veuillez envoyer une facture pour le diagnostic au client id <b>{$client->ID}</b> ({$client->first_name} {$client->last_name}) pour la machine {$Sav->product}";
    $message   = html_entity_decode($message);
    $to        = implode(',', $admin_emails);
    $subject   = "SAV{$post_id} Facture pour le diagnostic  - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );
}, 10, 1);

