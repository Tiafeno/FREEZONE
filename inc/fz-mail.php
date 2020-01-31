<?php

/**
 * Recuperer les adresses emails des responsables (Commercial et Administrateur)
 */
add_filter( 'get_responsible', function ($args) {
    $values = is_array($args) ? $args : ['administrator'];
    $args = array('role__in' => $values);
    $editors = get_users( $args );
    return array_map(function($user) { return $user->user_email; }, $editors);
}, 10 );

/**
 * Call in 'attente_intervention_client' action
 */
add_action('fz_cron_intervention_client_1', function($user, $order) {
    $from = "no-reply@freezone.click";
    $to = $user->user_email;
    $account_url = wc_get_account_endpoint_url('demandes');
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $content = "Bonjour<br><br>";
    $content .= "Nous vous informons que votre demande <b>N°{$order->get_id()}</b> sera bientôt expirée dans deux jours,<br> 
        Si il n'y a pas de réponse de votre part il sera rejeté. Pour voir vos demandes en cours <a href='{$account_url}' target='_blank'>cliquez ici</a><br>.
        Merci de votre compréhension <br><br> Cordialement<br> Equipe Freezone";
    $subject = "Demande #{$$order->get_id()} - Votre demande seras expirée dans deux(2) jours sur Freezone";
    wp_mail($to, $subject, $content, $headers);
}, 10, 2);

/**
 * Call in 'schedule_order_expired' action
 */
add_action('fz_cron_intervention_client_2', function($user, $order) {
    $from = "no-reply@freezone.click";
    $to = $user->user_email;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $content = "Bonjour<br><br>";
    $content .= "Nous vous informons que votre demande <b>N°{$order->get_id()}</b> est expirée,<br> 
    Pour avoir un nouveau devis nous vous prions de refaire une autre demande.
    Merci de votre compréhension <br><br> Cordialement<br> Equipe Freezone";

    $subject = "Demande #{$$order->get_id()} - Votre demande seras expirée dans deux(2) jours sur Freezone";
    wp_mail($to, $subject, $content, $headers);
}, 10, 2);


add_action('fz_cron_intervention_order_admin', function(WC_Order $order) {
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";

    $content = "Bonjour<br><br>";
    $content .= "Nous vous informons que la demande <b>N°{$order->get_id()}</b> est expirée.<br> 
        Vous pouvez le supprimer.<br><br> Cordialement<br> Freezone";

    $subject = "Demande #{$$order->get_id()} - Demande rejeter expirée sur Freezone";
    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

/**
 * Cette action permet d'envoyer au administrateur un mail pour
 * les informer d'une demande de service après vente
 */
add_action('fz_insert_sav', function ($sav_id) {
    global $Engine;
    $Sav = new \classes\fzSav($sav_id);
    $User = wp_get_current_user();
    $phone = get_field('phone', 'user_' . $User->ID);
    $content = $Engine->render('@MAIL/fz-insert-sav.html', [
        'sav' => $Sav,
        'User' => ['name' => $User->first_name . ' ' . $User->last_name, 'phone' => $phone]
    ]);
    $from = $User->user_email;
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $subject = "Service apres vente sur le site freezone.click";
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: $User->first_name . ' ' . $User->last_name <{$from}>";
    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

add_action('fz_insert_new_article', function ($article_id) {
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $article = new \classes\fzProduct($article_id);
    $supplier_id = $article->get_user_id();
    $reference = get_field('reference', 'user_' . $supplier_id);
    $url = "https://admin.freezone.click/articles";
    $content = "Bonjour<br><br>";
    $content .= "Une article <b>{$article->name}</b> vient d'être ajouter par le fournisseur <b>{$reference}</b>.<br> {$url}";
    $subject = "#{$article_id} - Un nouveau article vient d'être ajouter sur le site freezone.click";
    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

// Envoyer un email a l'administratuer pour une inscription
add_action('fz_new_user', function ($user_id, $role)  {
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $client = new WP_User(intval($user_id));
    $url = "https://admin.freezone.click/";
    $content = "Bonjour<br><br>";
    if (in_array('fz-company', $client->roles)):
        // Une entreprise vient de s'inscrire
        $content .= "Une nouvelle entreprise vient de s'inscrire:<br>";
        $company_name = get_field('company_name', 'user_'. $user_id);
        $content .= "Nom de l'entreprise: <b>{$company_name}</b> <br>";
    else:
        // Un particulier vient de s'inscrire dans le site
        $content .= "Un nouveau client vient de s'inscrire:<br>";
        $content .= "Nom: <b>{$client->first_name}</b> <br>";
        $content .= "Prénom: <b>{$client->last_name}</b> <br>";
    endif;
    $content .= "Adresse Email: <b>{$client->user_email}</b>";
    $subject = "#{$user_id} - Une nouvelle inscription sur le site freezone.click";
    wp_mail($to, $subject, $content, $headers);
}, 10, 2);

/**
 * Envoyer un mail au client que le responsable vient d'ajouter
 * pour l'informer que sont compte à été crée et bien actif
 * 
 * NB: Avant que le client voit les demandes ajouter par le commercial,
 * il devrais remplir le formulaire d'information sur lui ou son entreprise avant
 * de valider ou rejeter les demandes crée à son compte
 */
add_action('fz_mail_api_insert_user', function ($user_id, $pwd = "") {
    $user = new \WP_User((int) $user_id);
    $account_url = wc_get_account_endpoint_url('demandes');
    $from = "no-reply@freezone.click";
    $to = $user->user_email;
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $content = "Bonjour,<br>
        Suite à votre demande de devis nous vous informons que votre demande est désormais disponible et consultable sur notre site 
        <a href=\"{$account_url}\" target=\"_blank\">en cliquant ici</a><br><br>
        À noter que cette demande sera expirée dans 7 jours.<br><br>
        Actuellement vous possédez un accès espace client Freezone pour gérer et créer une demande de devis tout en parcourant notre catalogue en ligne.<br>
        Votre compte:<br>
        Adresse email: {$user->user_email}<br>
        Mot de passe: {$pwd}<br><br>
        N’hésitez pas à nous contacter si vous avez des questions<br>
        Merci";
    $subject = "Inscription reussi sur le site freezone";
    $send = wp_mail($to, $subject, $content, $headers);
    if ($send) {
        wp_send_json_success("Message envoyer avec succes");
    } else {
        wp_send_json_error("Une erreur s'est produit pendant l'envoie");
    }
}, 10, 2);

/**
 * Envoyer un mail au responsable pour informer q'un client a selectionner un ou des prestations
 */
add_action("fz_mail_send_selected_catalogue", function ($ids = []) {
    if (!is_array($ids) || empty($ids)) return false;
    $prestations = [];
    foreach ($ids as $id) {
        $id = intval($id);
        if (!$id || is_nan($id)) continue;
        $prestations[] = new \classes\fzCatalogue($id);
    }
    $user_id = get_current_user_id();
    $current_userdata = get_userdata( $user_id );
    $from = $current_userdata->user_email;
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $content = "Bonjour<br><br> Vous avez recus une demande de prestation:<br>";
    $content .= "<ul>";
    foreach ($prestations as $prestation) {
        $price = $prestation->price ? $prestation->price.' MGA, ' : '';
        $content .= "<li> {$prestation->name} <i>({$price}{$prestation->platform['name']})</i></li>";
    }
    $content .= "</ul>";
    $subject = "#{$user_id} - Une demande de prestation sur freezone";
    $send = wp_mail($to, $subject, $content, $headers);
    if ($send) {
        wp_send_json_success("Message envoyer avec succes");
    } else {
        wp_send_json_error("Une erreur s'est produit pendant l'envoie");
    }
}, 10, 1);

// Cette action permet d'envoyer un mail au fournisseur avec un lien pour valider leur articles
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
    $article_posts = array_map(function ($id) { return new \classes\fzProduct((int)$id); }, $article_ids);
    $content = $Engine->render('@MAIL/fz_submit_articles_for_validation.html', [
        'message' => html_entity_decode($message),
        'articles' => $article_posts,
        'url' => $url,
        'Phone' => freezone_phone_number
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
add_action('complete_order', function ($order_id, $status = 'completed')  {
    global $Engine;
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $url = "https://admin.freezone.click/dashboard/quotation/{$order_id}/edit";
    $quotation = new \classes\FZ_Quote($order_id);
    $client = $quotation->get_author();
    $content = $Engine->render('@MAIL/complete_order.html', [
        'quotation' => $quotation,
        'client' => $client,
        'url' => $url,
        'Phone' => freezone_phone_number
    ]);
    $st = $status === 'completed' ? 'validée' : 'rejetée';
    $subject = "#{$order_id} - Une demande vient d'être {$st} sur le site freezone";
    wp_mail($to, $subject, $content, $headers);
}, 10, 2);

/**
 * Demande de devis envoyer au administrateur
 */
add_action('fz_received_order', function ($order_id) {
    global $Engine;
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['editor', 'administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $url = "https://admin.freezone.click/dashboard/quotation/{$order_id}/edit";
    $customer_id = get_current_user_id();
    $role = \classes\fzClient::initializeClient($customer_id)->get_role();
    $message = '';
    switch ($role) {
        case 'fz-company':
            $company_name = get_field('company_name', 'user_' . $customer_id);
            $message .= "Vous avez reçu une demande de devis de l’entreprise <b>{$company_name}</b>.";
            break;
        default:
            $customer = wp_get_current_user();
            $message .= "Vous avez reçu une demande de devis d’un particulier dénommé <b>{$customer->first_name} {$customer->last_name}</b>";
            break;
    }
    $content = $Engine->render('@MAIL/received_order.html', [
        'quotation' => new \classes\FZ_Quote($order_id),
        'message' => html_entity_decode($message),
        'url' => $url,
        'Phone' => freezone_phone_number
    ]);
    $subject = "#{$order_id} - Vous avez reçu une demande de devis sur le site freezone";
    wp_mail($to, $subject, $content, $headers);
}, 10, 1);

/**
 * Update articles succefuly
 */
add_action('fz_updated_articles_success', function ($_articles, $supplier_id = 0) {
    global $Engine;
    $article_ids = explode(',', $_articles);
    $articles = array_map(function ($id) { return new \classes\fzProduct(intval($id)); }, $article_ids);
    $from = "no-reply@freezone.click";
    $to = implode(',', apply_filters( 'get_responsible', ['administrator'] ));
    $headers = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: FreeZone <{$from}>";
    $supplier_reference = '';
    if ( 0 !== $supplier_id && is_numeric($supplier_id)) {
        $supplier = new \classes\fzSupplier($supplier_id);
        $supplier_reference = $supplier->reference;
    } else {
        return false;
    }
    $url = "https://admin.freezone.click";
    $content = $Engine->render('@MAIL/fz_updated_articles_success.html', [
        'reference' => $supplier_reference,
        'articles' => $articles,
        'url' => $url,
        'Phone' => freezone_phone_number
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
        'url' => $url,
        'Phone' => freezone_phone_number
    ]);

    wp_update_post(['ID' => (int)$sav_id, 'post_status' => 'publish'], true);
    wp_mail($to, $subject, $content, $headers);
    wp_send_json($sav_id);
}, 10, 5);

// Envoyer un rappel au administrateur
add_action('fz_sav_revival_mail', function ($sav_id, $user_id = 0) {
    global $Engine;
    $from      = "no-reply@freezone.click";
    $to        = implode(',', apply_filters( 'get_responsible', ['author', 'administrator'] ));

    $user = $user_id === O || is_null($user_id) ? wp_get_current_user() : new WP_User(intval($user_id));
    $sav = new classes\fzSav($sav_id);
    $message   = "Bonjour, <br><br>Un client <b>{$user->first_name} {$user->last_name}</b> aimerait savoir l'état 
    de son produit <b>«{$sav->product}»</b> d'identification <b>N°{$sav->id}</b> en SAV";
    $message   = html_entity_decode($message);
    $subject   = "Rappel pour SAV N°{$sav->id} - Freezone";
    $headers   = [];
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = "From: Freezone <$from>";
    $content   = $Engine->render('@MAIL/default.html', [ 'message' => $message, 'Year' => 2019, 'Phone' => freezone_phone_number]);

    // Envoyer le mail
    wp_mail( $to, $subject, $content, $headers );
}, 10, 1);
