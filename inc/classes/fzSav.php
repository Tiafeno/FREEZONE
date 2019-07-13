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
    private static $fields = [
        'client',
        'product',
        'mark',
        'status_product',
        'product_provider',
        'date_purchase',
        'bill',
        'serial_number',
        'reference',
        'auctor',
        'description'
    ];

    public function __construct ($sav_id, $api = false)
    {
        $this->ID = $sav_id;
        foreach ( self::$fields as $key ) {
            if ($key === 'auctor' || $key === 'reference') {
                $value = get_post_meta($sav_id, 'sav_' . $key, true);
                if ($api && $key === 'auctor') {
                    $user_controller = new \WP_REST_Users_Controller();
                    $request = new \WP_REST_Request();
                    $request->set_param('context', 'edit');

                    $this->$key = $user_controller->prepare_item_for_response(new \WP_User((int)$value), $request);
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

    public static function get_fields ()
    {
        return self::$fields;
    }

}

add_action('rest_api_init', function() {
    foreach ( fzSav::get_fields() as $field ) {
        register_rest_field('fz_sav', $field, [
            'update_callback' => function ($value, $object, $field_name) {
                if ($field_name === 'auctor' || $field_name === 'reference') {
                    return update_post_meta($object->ID, 'sav_' . $field_name, $value);
                }
                return update_field($field_name, $value, $object->ID);

            },
            'get_callback' => function ($object, $field_name) {
                if ($field_name === 'auctor' || $field_name === 'reference') {
                    $value = get_post_meta($object['id'], 'sav_' . $field_name, true);
                    if ($field_name === 'auctor') {
                        $user_controller = new \WP_REST_Users_Controller();
                        $request = new \WP_REST_Request();
                        $request->set_param('context', 'edit');
                        $response = $user_controller->prepare_item_for_response(new \WP_User((int)$value), $request);
                        return $response->get_data();
                    } else {
                        return $value;
                    }
                }
                return get_field($field_name, $object['id']);
            }
        ]);
    }
});
