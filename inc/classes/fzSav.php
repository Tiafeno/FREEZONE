<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 21/05/2019
 * Time: 12:51
 */

namespace classes;


class fzSav
{
    public $ID;
    public $date_add = null;
    private $fields = [
        'client',
        'product',
        'mark',
        'status',
        'product_provider',
        'date_appointment',
        'date_purchase',
        'bill',
        'serial_number',
        'reference',
        'auctor',
        'description'
    ];

    public function __construct ($sav_id, $api = false) {
        $this->ID = $sav_id;
        foreach ($this->fields as $key) {
            if ($key === 'auctor' || $key === 'reference') {
                $value = get_post_meta($sav_id, 'sav_'.$key, true);
                if ($api && $key = 'auctor') {
                    $user_controller = new \WP_REST_Users_Controller();
                    $request = new \WP_REST_Request();
                    $request->set_param('context', 'edit');

                    $this->$key = $user_controller->prepare_item_for_response(new \WP_User((int) $value), $request);
                    continue;
                }

                $this->$key = $value;
                continue;
            }

            $field_value = get_field($key, $sav_id);
            $this->$key = $field_value;
        }
        $post_sav = get_post($sav_id);
        $this->date_add = $post_sav->post_date;
    }
}