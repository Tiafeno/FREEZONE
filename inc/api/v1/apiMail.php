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
        $order_id = $rq['order_id'];
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;
        $subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : "Demande de confirmation pour votre demande sur Freezone";
        $message = sanitize_text_field($message);
        $subject = sanitize_text_field($subject);
        if ($order_id === 0 || is_null($order_id)) wp_send_json_error("Parametre 'order_id' est incorrect");

        WC()->api->rest_api_includes();
        $order_controller = new WC_REST_Orders_V2_Controller();
        $request = new WP_REST_Request();
        $request->set_param('context', 'edit');
        $order = $order_controller->prepare_object_for_response(new WC_Order($order_id), $request);
        $data = $order->data;

        $total = (int) $data['total'];
        $tva = ($total * $this->tva) / 100;
        $prices = array_map(function ($i) { return (int)$i['total']; }, $data['line_items']);
        $sum = array_sum($prices);

        $content = $Engine->render('@MAIL/ask-confirm-order.html', [
            'order' => $data,
            'items' => $data['line_items'],
            'tva' => $tva,
            'pay' => $sum + $tva,
            'message' => $message,
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