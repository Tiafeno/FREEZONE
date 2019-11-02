<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 13/05/2019
 * Time: 13:11
 */

class apiMail
{
    private $tva = 20;
    public $no_reply = "no-reply@freezone.click";
    public function __construct () { }
    public function send_order_client(WP_REST_Request $rq) {
        global $Engine;

        /**
         * Generer un lien pour la confirmation de la demande de devis.
         * Ajouter un texte pour le mail
         * L'Objet du mail est définie automatiquement
         */
        $order_id = (int) $rq['order_id'];
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;
        $subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : "Demande de confirmation pour votre demande sur Freezone";
        $message = esc_html($message);
        $subject = sanitize_text_field($subject);
        if ($order_id === 0 || is_null($order_id)) wp_send_json_error("Parametre 'order_id' est incorrect");

        $order = new WC_Order($order_id);
        $data = $order->get_data();
        $message = html_entity_decode($message);
        $content = $Engine->render('@MAIL/ask-confirm-order.html', [
            'message' => stripslashes($message),
            'Phone' => freezone_phone_number,
            'Year'  => date('Y'),
            'demande_url' => wc_get_account_endpoint_url('demandes') . '?componnent=edit&id=' .$order_id
        ]);

        $to = $data['billing']['email'];
        $headers   = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: Freezone <{$this->no_reply}>";

        $send = wp_mail($to, $subject, $content, $headers);
        if ($send) {
            wp_send_json_success("Envoyer avec succès");

        } else {
            wp_send_json_error("Une erreur s'est produite pendant l'envoie");
        }
    }

}