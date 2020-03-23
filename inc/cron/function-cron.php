<?php

function fn_query_expired_order($day, $position = 1) {
    $date_expired = new DateTime("now");
    $date_expired->modify($day);
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
                    'value' => $position, // 1: Envoyer
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
                    'value' => $position, // 1: Envoyer
                    'compare' => "="
                ],
                [
                    'key' => 'date_send', // contient la date d'envoye du mail au client
                    'type' => 'DATETIME',
                    'compare' => 'NOT EXISTS'
                ]
            ],
        ],
        'order'   => 'DESC',
        'orderby' => 'ID'
    ];
    return $args;
}

// Recuperer les posts de type: fz_sav par statut
add_filter("get_db_savby_status", function($status) {
    global $wpdb;
    $status =  is_array($status) ? implode(',', $status) : intval($status);
    $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM $wpdb->posts as pst
JOIN $wpdb->postmeta as pm ON (pm.post_id = pst.ID) 
WHERE pst.post_type = 'fz_sav' AND pm.meta_key = 'status_sav' AND CAST(pm.meta_value AS UNSIGNED) IN ($status)
SQL;
    return $wpdb->get_results($sql);
});

add_action('schedule_order_expired', function() {
    // Commande en attente plus de 7 jours ou une semaine
    $args = fn_query_expired_order('-7 day', 1);
    $the_query = new WP_Query($args);
    if ($the_query->have_posts()) {
        array_map(function ($post) {
            $order = wc_get_order($post->ID);
            // Envoyer un mail au client
            $customer = new WP_User($order->get_customer_id());
            do_action("fz_cron_intervention_client_2", $customer, $order);
            // Rejetée la demande automatiquement si la date d'expiration arrive
            $order->update_meta_data('position', 2); // 2: Rejeter
            $order->save_meta_data();
            return $order;
        }, $the_query->posts);
    }
}, 10);

// Supprimer tous les commandes rejeter plus de 7jours ou une semaine
add_action('schedule_order_reject_expired', function () {
    global $wpdb;
    $order_post_type = "shop_order";
    $date_now = new DateTime("now");
    $data_now_string = $date_now->format('Y-m-d H:i:s');
    // On ajouter 14j car le 7em Jour sans reponse de la par du client, On se refere toujours par la date d'envoie,
    // la commande sera automatiquement "Rejeter".
    // Donc, on attend 7j encore pour envoyer au administrateur un mail pour informe la supperssion de cette commande
    $query_sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM {$wpdb->posts} as pst
    JOIN {$wpdb->postmeta} as pm ON (pm.post_id = pst.ID) 
    JOIN {$wpdb->postmeta} as pm2 ON (pm2.post_id = pst.ID)
    WHERE pst.post_type = '{$order_post_type}' 
        AND (pm.meta_key = 'date_send' AND cast(DATE_ADD(pm.meta_value, INTERVAL 14 DAY) AS DATE) < cast('{$data_now_string}' AS DATE))
        AND (pm2.meta_key = 'position' AND cast(pm2.meta_value AS unsigned) = 2)
SQL;
    $results = $wpdb->get_results($query_sql);
    foreach ($results as $post) {
        $order = wc_get_order((int)$post->ID);
        // Envoyer un mail pour informer l'administrateur pour la suppression
        do_action('fz_cron_intervention_order_admin', $order);
    }
});

add_action('attente_intervention_client', function() {
    // SQL: https://www.w3schools.com/sql/func_mysql_date_add.asp
    global $wpdb;
    $order_post_type = "shop_order";
    $date_now = new DateTime("now");
    $data_now_string = $date_now->format('Y-m-d H:i:s');
    // Recuprer les commandes 2 jours avant la date expiration, s'il n'y a pas d'intervention par le client
    $query_sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pst.ID FROM {$wpdb->posts} as pst
        JOIN {$wpdb->postmeta} as pm ON (pm.post_id = pst.ID) 
        JOIN {$wpdb->postmeta} as pm2 ON (pm2.post_id = pst.ID)
        WHERE pst.post_type = '{$order_post_type}' 
            AND (pm.meta_key = 'date_send' AND cast(DATE_ADD(pm.meta_value, INTERVAL 4 DAY) AS DATE) = cast('{$data_now_string}' AS DATE))
            AND (pm2.meta_key = 'position' AND cast(pm2.meta_value AS unsigned) = 1)
SQL;
    $results = $wpdb->get_results($query_sql);
    if (empty($results)) return;
    foreach ($results as $shop_order) {
        $order = wc_get_order((int) $shop_order->ID);
        $customer = new WP_User($order->get_customer_id());
        // Envoyer un mail au client
        do_action('fz_cron_intervention_client_1', $customer, $order);
    }
});


// @SAV
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
WHERE pst.post_type = 'fz_sav' AND pst.post_status = 'publish'
    AND pm.meta_key = 'approximate_time' AND CAST(pm.meta_value AS DATETIME) < CAST('$date_now' AS DATETIME) 
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
WHERE pst.post_type = 'fz_sav' AND pst.post_status = 'publish'
    AND pm.meta_key = 'status_sav' AND CAST(pm.meta_value AS UNSIGNED) = 3 
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

