<?php

include_once __DIR__ . '/function-cron.php';

// Tous les jours
add_action('everyday', function () {
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }
    // Vérifier si le technicien a diagnostiquer la demande du client
    // La réparation du matériel XYV du client VVB est elle achevée ?
    do_action( 'ask_product_repair', $admin_emails );

    // Envoyer un mail au téchnicien si le status du SAV est sur <à réparer>
    // Pouvez-vous rentrer le délais, approximatif de réparation du matériel XYV du client VVB »
    // do_action( 'ask_approximate_date_product', $admin_emails );

    // Rejetée automatiquement les demandes envoyer qui ne sont pas consulté par les clients
    do_action('schedule_order_expired');

    // Vérifier la validité d’un devis sur une semaine. Si le client n’est pas intervenue
    // sur le devis au d’une semaine lui envoyer une notification pour lui dire de
    // refaire une demande
    do_action('attente_intervention_client');

    // Supprimer tous les commandes rejeter plus de 7jours ou une semaine
    do_action("schedule_order_reject_expired");
}, 10);

add_action('every_3_days', function(){
    // TODO: Mail de notification pour `La réparation accordée` et `La réparation refusée`
    // Pour les servcies " diagnostique en cours"
    // envoyer seulement au commercial du client et a l'administrateur
    $savs_diagnostic_inProgress = apply_filter("get_db_savby_status", [1, 3]); // return wpdb results posts
    foreach ($savs_diagnostic_inProgress as $sav) {
        if (!is_object($sav) || !isset($sav->ID)) continue;
        $object_sav = new \classes\fzSav( intval($Sav->ID));
        $customer_id = $object_sav->get_customer_id();
        $commercial_id = \classes\fzClient::initializeClient($customer_id, false)->get_responsible(); // return 0 or user id
        $commerical_data = get_userdata($commercial_id); // to
        $admins = new \WP_User_Query(['role' => ['Administrator']]); // cc
        // Envoyer un mail au commercial
        do_action("fz_sav_cron_mail", $object_sav, $commerical_data, $admins);
    }
}, 10);

add_action('every_2_days', function () {
    $date_now = new DateTime("now");
    $data_now_string = $date_now->format('Y-m-d H:i:s');
    /**
     * Envoyer un mail si le status du SAV est non definie
     *
     * Nous vous rappelons que le matériel XYV du client VVB est encore dans l’atelier aussi nous
     * vous demandons de relancer le client à propos du devis Réf TTTT
     */
    $sql_nothing_status = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND NOT EXISTS (SELECT * FROM $wpdb->postmeta pm WHERE pm.meta_key = 'status_sav' AND pst.ID = pm.post_id)
    AND cast(DATE_ADD(pst.post_date, INTERVAL 1 DAY) AS DATE) < cast('{$data_now_string}' AS DATE)
SQL;
    // TODO: Envoyer un mail si le SAV a une status 'Non definie'
}, 10);

// Tous les 2 jours
add_action('every_2_days', function () {
    global $wpdb, $Engine;
    $no_reply = _NO_REPLY_;

    /**
     * Envoyer un mail si le status du SAV est sur <Diagnostique fini>
     *
     * Nous vous rappelons que le matériel XYV du client VVB est encore dans l’atelier aussi nous
     * vous demandons de relancer le client à propos du devis Réf TTTT
     */

    $sql_diagnostic_progress = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
JOIN $wpdb->postmeta as pm ON (pm.post_id = pst.ID) 
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND (pm.meta_key = 'status_sav' AND cast(pm.meta_value AS UNSIGNED) = 2 )
SQL;

    $results = $wpdb->get_results($sql_diagnostic_progress);
    if (empty($results)) return;
    $fields    = get_hardwards($results);
    $admins = new \WP_User_Query(['role' => ['Administrator']]);
    
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }
    if (empty($admin_emails)) return;
    foreach ($fields as $field) {
        $sav_id = (int) $field['sav_id'];
        $devis_ref = get_field('reference', $sav_id);
        $commerical = get_editor_for_customer_id((int) $field['customer_id']);
        // Ajouter l'adresse email du commercial responsable
        if ($commerical) { $admin_emails[] = $commerical->user_email; }
        $to = implode(',', $admin_emails);
        $message =  "Bonjour, <br><br>";
        $message .= "Nous vous rappelons que le matériel <b>{$field['name']}</b> du client {$field['reference']} est encore dans l’atelier aussi nous vous " .
            "demandons de relancer le client à propos du devis Réf $devis_ref";
        $message = html_entity_decode($message);

        $subject = "SAV{$sav_id} - Notification su le diagnostique en cours - Freezone";
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: Freezone <$no_reply>";
        $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);
        // Envoyer le mail
        wp_mail($to, $subject, $content, $headers);
    }
}, 10);

function get_hardwards ($results) {
    $response = [];
    foreach ( $results as $post ) {
        $name = get_field('product', (int)$post->ID);
        $customer_id = (int)get_post_meta($post->ID, 'cutomer', true);
        if (is_nan($customer_id) || 0 === $customer_id || empty($customer_id)) continue;
        $client_reference = get_field('reference', 'user_' . $customer_id);
        $response[] = [
            'name' => $name, 
            'reference' => $client_reference, 
            'sav_id' => (int) $post->ID, 
            'customer_id' => $customer_id
        ];
    }
    return $response;
}

function get_editor_for_customer_id($customer_id) {
    $editor_id = \classes\fzClient::initializeClient($customer_id, false)->get_responsible();
    if (intval($editor_id) === 0 || is_nan($editor_id)) return false;
    return new WP_User( (int) $editor_id);
}