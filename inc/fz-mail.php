<?php
/**
 * Cette action permet d'envoyer au administrateur un mail pour
 * les informer d'une demande de service après vente
 */
add_action('fz_insert_sav', function ($sav_id) {
    global $Engine;
    $admins = ['contact@falicrea.com'];
    $Sav = new \classes\fzSav($sav_id);
    $User= wp_get_current_user();
    $phone = get_field('phone', 'user_'.$User->ID);
    $content = $Engine->render('@MAIL/fz-insert-sav.html', [
        'sav' => $Sav,
        'User' => ['name' => $User->first_name . ' ' . $User->last_name, 'phone' => $phone]
    ]);
    $from = $User->user_email;
    $to = implode($admins, ',');
    $subject = "Service apres vente sur le site freezone.click";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: $User->first_name . ' ' . $User->last_name <{$from}>";

    $send = wp_mail($to, $subject, $content, $headers);
    if ($send) {
        wp_send_json_success("Envoyer avec succès");
    } else {
        wp_send_json_error("Une erreur s'est produite pendant l'envoie");
    }
}, 10, 1);

// Cette action permet d'envoyer un mail au fournisseur pour valider leur articles
add_action('fz_submit_articles_for_validation', function ($supplier_id, $subject, $message) {
    global $Engine;

    $Supplier = new \classes\fzSupplier($supplier_id);

    $from = "contact@freezone.click";
    $to = $Supplier->user_email;
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $url = home_url('/updated');
    $nonce = base64_encode("update-{$Supplier->ID}");
    $url .= "?fznonce={$nonce}&email={$Supplier->user_email}";

    $today = date_i18n('Y-m-d H:i:s');
    $date_expired = new DateTime("$today + 2 day");
    $expired_encode = base64_encode($date_expired->format('Y-m-d H:i:s'));
    $url .= "&e={$expired_encode}";

    $content = $Engine->render('@MAIL/fz_submit_articles_for_validation.html', [
        'message' => $message,
        'url' => $url
    ]);

    $send = wp_mail($to, $subject, $content, $headers);
    if ($send) {
        wp_send_json_success("Mail envoyer avec succès");
    } else {
        wp_send_json_error("Une erreur s'est produite pendant l'envoie. Le lien {$url}");
    }

}, 10, 3);

add_action('complete_order', function ($order_id) {
    global $Engine;

    $from = "no-reply@freezone.click";
    $admins = ['contact@falicrea.com', 'commercial@freezone.click'];
    $to = implode($admins, ',');
    $headers   = [];
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
    $subject = "#{$order_id} - Une commande vient d'être validé sur le site freezone.click";

    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

add_action('fz_received_order', function ($order_id) {
    global $Engine;
    $from = "no-reply@freezone.click";
    $admins = ['contact@falicrea.com', 'commercial@freezone.click'];
    $to = implode($admins, ',');
    $headers   = [];
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