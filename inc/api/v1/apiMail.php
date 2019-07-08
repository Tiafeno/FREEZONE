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
        $message = sanitize_text_field($message);
        $subject = sanitize_text_field($subject);
        if ($order_id === 0 || is_null($order_id)) wp_send_json_error("Parametre 'order_id' est incorrect");

        $order = new WC_Order($order_id);

        $total = (int) $order->get_total();
        $tva = ($total * $this->tva) / 100;
        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $_item = new stdClass();
            $_item->name = $item->get_name();
            $_item->quantity = $item->get_quantity();
            $_item->price = round((int) $item['total'] / $item->get_quantity());

            $items[] = $_item;
        }
        $data = $order->get_data();

        $content = $Engine->render('@MAIL/ask-confirm-order.html', [
            'data' => $data,
            'items' => $items,
            'tva' => $tva,
            'pay' => $total + $tva,
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