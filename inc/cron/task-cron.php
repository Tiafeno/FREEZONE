<?php

include_once __DIR__ . '/function-cron.php';
$no_reply = _NO_REPLY_;

// Tous les jours
add_action('everyday', function () {
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }

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

// <`La réparation accordée`> et <`La réparation refusée`>
add_action('every_3_days', function(){
    // Mail de notification pour `La réparation accordée` et `La réparation refusée` envoyer seulement au commercial du client et a l'administrateur
    $savs_diagnostic_inProgress = apply_filter("get_db_savby_status", [3, 4]); // return wpdb results posts
    foreach ($savs_diagnostic_inProgress as $sav) {
        if (!is_object($sav) || !isset($sav->ID)) continue;
        $object_sav = new \classes\fzSav( intval($sav->ID));
        $customer_id = $object_sav->get_customer_id();
        $commercial_id = \classes\fzClient::initializeClient($customer_id, false)->get_responsible(); // return 0 or user id
        $commerical_data = get_userdata($commercial_id); // to
        $admins = new \WP_User_Query(['role' => ['Administrator']]); // cc
        // Envoyer un mail au commercial
        do_action("fz_sav_cron_mail", $object_sav, $commerical_data, $admins);
    }
}, 10);

// <En cours de traitement>
add_action('every_2_days', function () use ($no_reply) {
    global $wpdb, $Engine;
    $date_now = new DateTime("now");
    $data_now_string = $date_now->format('Y-m-d H:i:s');
    /**
     * Envoyer un mail si le status du SAV est non definie ou En cours de traitement
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
    //Envoyer un mail si le SAV a une status 'Non definie' ou 'En cours de traitement'
    $savs = $wpdb->get_results($sql_nothing_status);
    // Ajouter l'adresse email du commercial responsable
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor']]); // cc
    $to = implode(',', $admins);
    $message =  "Bonjour, <br><br>";
    $message .= "Nous vous rappelons que le(s) matériel(s) suivant(s) est/sont en cours de traitement: <br>";
    $message .= "<ul>";
    foreach ($savs as $sav) {
        $fz_sav = new \classes\fzSav($sav->ID);
        $message .= "<li><b>{$fz_sav->product}</b> de reference <b>{$fz_sav->reference}</b></li>";
    }
    $message .= "</ul>";
    $message .= "Nous vous demandons de relancer le(s) client(s).<br> Equipe " . __SITENAME__;
    $message = html_entity_decode($message);

    $subject = "Notification sur les S.A.V en cours de traitement du {$date_now->format('d l')} - Freezone";
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => date('Y'), 'Phone' => freezone_phone_number]);
    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);
}, 10);

// <Le diagnostique en cours >
add_action('every_2_days', function () use ($no_reply) {
    global $wpdb, $Engine;
    $date_now = new DateTime("now");
    $data_now_string = $date_now->format('Y-m-d H:i:s');
    /**
     * Envoyer un mail si le status du SAV est en diagnostique en cours
     */
    $sql_nothing_status = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND (SELECT * FROM $wpdb->postmeta pm WHERE pm.meta_key = 'status_sav' AND cast(pm.meta_value AS UNSIGNED) = 1)
    AND cast(DATE_ADD(pst.post_date, INTERVAL 1 DAY) AS DATE) < cast('{$data_now_string}' AS DATE)
SQL;
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor']]); // cc
    $to = implode(',', $admins);
    $message =  "Bonjour, <br><br>";
    $message .= "Nous vous rappelons que le(s) matériel(s) suivant(s) est/sont en cours de diagnostique : <br>";
    $message .= "<ul>";
    $savs = $wpdb->get_results($sql_nothing_status);
    if (empty($savs)) return;
    foreach ($savs as $sav) {
        $fz_sav = new \classes\fzSav($sav->ID);
        $message .= "<li><b>{$fz_sav->product}</b> de reference <b>{$fz_sav->reference}</b></li>";
    }
    $message .= "</ul>";
    $message .= "Nous vous demandons de relancer le(s) client(s).<br> Equipe " . __SITENAME__;
    $message = html_entity_decode($message);

    $subject = "Notification(s) sur le(s) S.A.V est/sont en cours de diagnostique au {$date_now->format('d l')} - Freezone";
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => date('Y'), 'Phone' => freezone_phone_number]);
    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);
}, 10);

// <Diagnostique fini>
add_action('every_2_days', function () use ($no_reply) {
    global $wpdb, $Engine;

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

        $subject = "SAV{$sav_id} - Notification sur le diagnostique fini - Freezone";
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: Freezone <$no_reply>";
        $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => date("Y"), 'Phone' => freezone_phone_number]);
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