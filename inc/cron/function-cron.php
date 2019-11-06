<?php

add_action('schedule_order_expired', function() {
    $date_expired = new DateTime("now");
    $date_expired->modify('-7 day');

    $args = [
        'post_type' => wc_get_order_types(),
        'post_status' => array_keys( wc_get_order_statuses() ),
        "posts_per_page" => -1,
        "meta_query" => [
            'relation' => 'OR',
            [
                'relation' => 'AND',
                [
                    'key' => "position",
                    'value' => 1,
                    'compare' => "="
                ],
                [
                    'key' => 'date_send',
                    'type' => 'DATETIME',
                    'compare' => '<',
                    'value' => $date_expired->format('Y-m-d H:i:s')
                ]
            ],
            [
                'relation' => 'AND',
                [
                    'key' => "position",
                    'value' => 1,
                    'compare' => "="
                ],
                [
                    'key' => 'date_send',
                    'type' => 'DATETIME',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            
        ],
        'order'   => 'DESC',
        'orderby' => 'ID'
    ];

    $the_query = new WP_Query($args);
    if ($the_query) {
        array_map(function ($post) {
            $order = wc_get_order($post->ID);
            // Rejetée la demande automatiquement
            $order->update_meta_data('position', 2);
            $order->save_meta_data();

            return $order;
        }, $the_query->posts);
    }


}, 10);

add_action('ask_product_repair', function ($admin_emails) {
    global $wpdb, $Engine;

    $to = implode(',', $admin_emails);
    $no_reply = _NO_REPLY_;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";

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
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

add_action('ask_approximate_date_product', function ($admin_emails) {
    global $wpdb, $Engine;
    $no_reply = _NO_REPLY_;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$no_reply>";

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
    $content = $Engine->render('@MAIL/default.html', ['message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail($to, $subject, $content, $headers);

}, 10, 1);
