<?php

add_action('export_articles_csv', function () {
    global $wpdb;
    $SQL = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts as pts 
    WHERE pts.post_type = 'fz_product' ";

    $results = $wpdb->get_results($SQL);
    $count_sql = "SELECT FOUND_ROWS()";
    $total = $wpdb->get_var($count_sql);

    $marges = [
        ['name' => 'marge', 'key'        => '_fz_marge'], // (int)
        ['name' => 'marge_dealer', 'key' => '_fz_marge_dealer'], // (int)
        ['name' => 'marge_particular', 'key' => '_fz_marge_particular'], // (int)
    ];

    $response = [];

    foreach ($results as $article) {
        $new_article = new stdClass();
        $article_id = (int) $article->ID;
        $new_article->id = $article_id;
        $new_article->title = $article->post_title;
        $new_article->price = (int) get_field('price', $article_id);
        foreach ($marges as $marge) {
            $marge_value = get_post_meta($article_id, $marge['key'], true);
            $new_article->{$marge['name']} =  floatval($marge_value);
        }

        array_push($response, $new_article);
    }

    wp_send_json($response);
}, 10);