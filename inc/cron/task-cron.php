<?php

// Tous les jours
add_action('everyday', function () {
    global $wpdb, $Engine;

    $no_reply = _NO_REPLY_;
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }

    $to = implode(',', $admin_emails);
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";

    /**
     * Vérifier si la date du delais approximatif est atteinte, s'il est atteinte on envoie un mail
     *
     * La réparation du matériel XYV du client VVB est elle achevée ?
     */

    $date_now = date_i18n('Y-m-d');
    $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID, pm.meta_value as approximate_time FROM $wpdb->posts as pst
JOIN $wpdb->postmeta as pm ON (pm.post_id = pst.ID) 
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND pm.meta_key = 'approximate_time' 
    AND CAST(pm.meta_value AS DATE) < CAST('$date_now' AS DATE) 
SQL;

    $results = $wpdb->get_results($sql);
    if (empty($results)) return;
    $savs = get_hardwards($results);

    $message = "Bonjour, <br><br>";
    $message .= "La réparation des matériels suivant sont elles achevée?: <br>";
    $message .= "<ul>";
    foreach ( $savs as $sav ) {
        $message .= "<li>Le matériel <b>{$sav['name']}</b> du client <b>{$sav['reference']}</b></li>";
    }
    $message .= "</ul>";
    $message = html_entity_decode($message);
    $subject = "Notification de réparation des matériels - Freezone";
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019]);

    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);


    /**
     * Envoyer un mail au téchnicien si le status du SAV est sur <à réparer>
     *
     * Pouvez-vous rentrer le délais, approximatif de réparation du matériel XYV du client VVB »
     */

    $wpdb->flush();
    $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
JOIN $wpdb->postmeta as pm ON (pm.post_id = pst.ID) 
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND pm.meta_key = 'status_sav' 
    AND CAST(pm.meta_value AS SIGNED) = 3 
SQL;
    $results = $wpdb->get_results($sql);
    if (empty($results)) return;
    $savs    = get_hardwards($results);

    $admins = new \WP_User_Query(['role' => ['Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }
    if (empty($admin_emails)) return;
    $to = implode(',', $admin_emails);
    $message = "Bonjour, <br><br>";
    $message .= "Pouvez-vous rentrer le délais approximatif des matériels suivants ? <br>";
    $message .= "<ul>";
    foreach ( $savs as $sav ) {
        $message .= "<li>Le matériel <b>{$sav['name']}</b> du client <b>{$sav['reference']}</b></li>";
    }
    $message .= "</ul>";
    $message = html_entity_decode($message);
    $subject = "Notification d'ajout de delais approximatif - Freezone";
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019]);

    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);

}, 10);

// Tous les 2 jours
add_action('every_2_days', function () {
    global $wpdb, $Engine;
    $no_reply = _NO_REPLY_;

    /**
     * Envoyer un mail si le status du SAV est sur <diagnostique réalisé>
     *
     * Nous vous rappelons que le matériel XYV du client VVB est encore dans l’atelier aussi nous
     * vous demandons de relancer le client à propos du devis Réf TTTT
     */

    $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
JOIN $wpdb->postmeta as pm ON (pm.post_id = pst.ID) 
WHERE pst.post_type = 'fz_sav' 
    AND pst.post_status = 'publish'
    AND pm.meta_key = 'status_sav' 
    AND CAST(pm.meta_value AS SIGNED) = 1
SQL;
    $results = $wpdb->get_results($sql);
    if (empty($results)) return;
    $savs    = get_hardwards($results);

    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }
    if (empty($admin_emails)) return;
    $to = implode(',', $admin_emails);

    foreach ($savs as $sav) {
        $devis_ref = get_field('quotation_ref', $sav['sav_id']);
        $message =  "Bonjour, <br><br>";
        $message .= "Nous vous rappelons que le matériel {$sav['name']} du client {$sav['reference']} est encore dans l’atelier aussi nous vous " .
            "demandons de relancer le client à propos du devis Réf $devis_ref";
        $message = html_entity_decode($message);

        $subject = "Notification d'ajout de delais approximatif - Freezone";
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: Freezone <$no_reply>";
        $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019]);

        // Envoyer le mail
        wp_mail($to, $subject, $content, $headers);
    }

}, 10);


function get_hardwards ($results)
{
    $response = [];
    foreach ( $results as $post ) {
        $name = get_field('product', (int)$post->ID);
        $client_id = (int)get_post_meta($post->ID, 'sav_auctor', true);
        if (is_nan($client_id) || 0 === $client_id || empty($client_id)) continue;
        $client_reference = get_field('reference', 'user_' . $client_id);

        $response[] = ['name' => $name, 'reference' => $client_reference, 'sav_id' => (int) $post->ID];
    }

    return $response;
}