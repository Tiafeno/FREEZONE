<?php

include_once __DIR__ . '/function-cron.php';

// Tous les jours
add_action('everyday', function () {
    $admins = new \WP_User_Query(['role' => ['Administrator', 'Editor', 'Author']]);
    $admin_emails = [];
    foreach ( $admins->get_results() as $admin ) {
        $admin_emails[] = $admin->user_email;
    }

    /**
     * Vérifier si la date du delais approximatif est atteinte, s'il est atteinte on envoie un mail
     * La réparation du matériel XYV du client VVB est elle achevée ?
     */
    do_action( 'ask_product_repair', $admin_emails );

    /**
     * Envoyer un mail au téchnicien si le status du SAV est sur <à réparer>
     * Pouvez-vous rentrer le délais, approximatif de réparation du matériel XYV du client VVB »
     */
    do_action( 'ask_approximate_date_product', $admin_emails );

    /**
     * Rejetée automatiquement les demandes envoyer qui ne sont pas consulté par les clients
     */
    do_action('schedule_order_expired');
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
        $message .= "Nous vous rappelons que le matériel <b>{$sav['name']}</b> du client {$sav['reference']} est encore dans l’atelier aussi nous vous " .
            "demandons de relancer le client à propos du devis Réf $devis_ref";
        $message = html_entity_decode($message);

        $subject = "Notification d'ajout de delais approximatif - Freezone";
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: Freezone <$no_reply>";
        $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

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