<?php
/**
 * Cette action permet d'envoyer au administrateur un mail pour
 * les informer d'une demande de service après vente
 */
add_action('fz_insert_sav', function ($sav_id) {
    global $Engine;
    $admins = ['contact@falicrea.com'];
    $Sav = \model\fzModel::getInstance()->get_sav($sav_id);
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


/**
 * Cette action permet d'envoyer un mail au fournisseur pour valider leur articles
 */
add_action('fz_submit_articles_for_validation', function ($supplier_id) {

}, 10, 1);