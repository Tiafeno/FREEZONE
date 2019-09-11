<?php
$to_admins = ['contact@falicrea.com', 'david@freezonemada.com'];

/**
 * Cette action permet d'envoyer au administrateur un mail pour
 * les informer d'une demande de service après vente
 */
add_action('fz_insert_sav', function ($sav_id) use ($to_admins) {
    global $Engine;
    $Sav = new \classes\fzSav($sav_id);
    $User = wp_get_current_user();
    $phone = get_field('phone', 'user_' . $User->ID);
    $content = $Engine->render('@MAIL/fz-insert-sav.html', [
        'sav' => $Sav,
        'User' => ['name' => $User->first_name . ' ' . $User->last_name, 'phone' => $phone]
    ]);
    $from = $User->user_email;
    $to = implode($to_admins, ',');
    $subject = "Service apres vente sur le site freezone.click";
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: $User->first_name . ' ' . $User->last_name <{$from}>";

    wp_mail($to, $subject, $content, $headers);
}, 10, 1);


add_action('fz_insert_new_article', function ($article_id) use ($to_admins) {
    $from = "no-reply@freezone.click";
    $to = implode($to_admins, ',');
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $article = new \classes\fzSupplierArticle($article_id);
    $supplier_id = $article->get_user_id();
    $reference = get_field('reference', 'user_' . $supplier_id);
    $url = "https://admin.freezone.click/articles";
    $content = "Bonjour<br><br>";
    $content .= "Une article <b>{$article->name}</b> vient d'être ajouter par le fournisseur <b>{$reference}</b>.<br> {$url}";
    $subject = "#{$article_id} - Un nouveau article vient d'être ajouter sur le site freezone.click";

    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

add_action('fz_new_user', function ($user_id, $role) use ($to_admins)  {
    $from = "no-reply@freezone.click";
    $to = implode($to_admins, ',');
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $user = new WP_User(intval($user_id));
    $url = "https://admin.freezone.click/";
    $content = "Bonjour<br><br>";
    $content .= "Un nouveau client vient de s'inscrire:<br>";
    $content .= "Nom: <b>{$user->first_name}</b> <br>";
    $content .= "Prénom: <b>{$user->last_name}</b> <br>";
    $content .= "Adresse Email: <b>{$user->user_email}</b>";
    $subject = "#{$user_id} - Un nouveau client vient de s'inscrire sur le site freezone.click";

    wp_mail($to, $subject, $content, $headers);

}, 10, 1);

// Cette action permet d'envoyer un mail au fournisseur pour valider leur articles
add_action('fz_submit_articles_for_validation', function ($supplier_id, $subject, $message, $cc = '', $articles = '') {
    global $Engine;

    $Supplier = new \classes\fzSupplier($supplier_id);

    $from = "david@freezonemada.com";
    $to = $Supplier->user_email;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    // Ajouter les adresses email en copie s'il est definie
    if (!empty($cc)) {
        $emails = explode(',', $cc);
        foreach ( $emails as $mail ) {
            $headers[] = "Cc: {$mail}";
        }
    }

    $url = home_url('/updated');
    $nonce = base64_encode("update-{$Supplier->ID}");
    $url .= "?fznonce={$nonce}&email={$Supplier->user_email}";

    $today = date_i18n('Y-m-d H:i:s');
    $date_expired = new DateTime("$today + 2 day");
    $expired_encode = base64_encode($date_expired->format('Y-m-d H:i:s'));
    $url .= "&e={$expired_encode}&articles=$articles";

    $article_ids = explode(',', $articles);
    $article_posts = array_map(function ($id) { return new \classes\fzSupplierArticle((int)$id); }, $article_ids);

    $content = $Engine->render('@MAIL/fz_submit_articles_for_validation.html', [
        'message' => html_entity_decode($message),
        'articles' => $article_posts,
        'url' => $url
    ]);

    $send = wp_mail($to, $subject, $content, $headers);
    if ($send) {
        wp_send_json_success("Mail envoyer avec succès");
    } else {
        wp_send_json_error("Une erreur s'est produite pendant l'envoie. Le lien {$url}");
    }

}, 10, 5);

/**
 * Envoyer un mail au administrateur la confirmation d'une demande par le client
 * Rejeter ou Accepter
 */
add_action('complete_order', function ($order_id, $status = 'completed') use ($to_admins) {
    global $Engine;

    $from = "no-reply@freezone.click";
    $to = $to_admins;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $url = "https://admin.freezone.click/dashboard/quotation/{$order_id}/edit";
    $quotation = new \classes\fzQuotation($order_id);
    $client = $quotation->get_author();
    $content = $Engine->render('@MAIL/complete_order.html', [
        'quotation' => $quotation,
        'client' => $client,
        'url' => $url
    ]);
    $st = $status === 'completed' ? 'validée' : 'rejetée';
    $subject = "#{$order_id} - Une demande vient d'être {$st} sur le site freezone.click";

    wp_mail($to, $subject, $content, $headers);
}, 10, 2);

add_action('fz_received_order', function ($order_id) use ($to_admins) {
    global $Engine;
    $from = "no-reply@freezone.click";
    $to = $to_admins;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $url = "https://admin.freezone.click/dashboard/quotation/{$order_id}/edit";
    $quotation = new \classes\fzQuotation($order_id);
    $content = $Engine->render('@MAIL/received_order.html', [
        'quotation' => $quotation,
        'url' => $url
    ]);
    $subject = "#{$order_id} - Vous avez reçu une demande de devis sur le site freezone.click";

    wp_mail($to, $subject, $content, $headers);

}, 10, 1);

/**
 * Update articles succefuly
 */
add_action('fz_updated_articles_success', function ($_articles, $supplier_id = 0) {
    global $Engine;
    $article_ids = explode(',', $_articles);
    $articles = array_map(function ($id) { return new \classes\fzSupplierArticle(intval($id)); }, $article_ids);

    $from = "no-reply@freezone.click";
    $admins = ['contact@falicrea.com', 'david@freezonemada.com'];
    $to = implode($admins, ',');
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $supplier_reference = '';
    if (!empty($supplier_id)) {
        $supplier = new \classes\fzSupplier($supplier_id);
        $supplier_reference = $supplier->reference;
    }

    $url = "https://admin.freezone.click";
    $content = $Engine->render('@MAIL/fz_updated_articles_success.html', [
        'reference' => $supplier_reference,
        'articles' => $articles,
        'url' => $url
    ]);

    $subject = "Un fournisseur {$supplier_reference} à mis à jour son catalogue d'article";

    wp_mail($to, $subject, $content, $headers);

}, 10, 2);


add_action('fz_sav_contact_mail', function ($sav_id, $sender_user_id, $mailing_id, $subject, $message) {
    global $Engine;

    $SAV = new \classes\fzSav($sav_id, true);
    $author_data = $SAV->auctor->get_data();
    $from = "no-reply@freezone.click";
    $to = $author_data['email'];
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $url = wc_get_account_endpoint_url('sav');
    $message = html_entity_decode($message);
    $content = $Engine->render('@MAIL/fz_sav_contact_mail.html', [
        'message' => stripslashes($message),
        'url' => $url
    ]);

    $update_result = wp_update_post(['ID' => (int)$sav_id, 'post_status' => 'publish'], true);
    wp_mail($to, $subject, $content, $headers);
    wp_send_json($sav_id);
}, 10, 5);
