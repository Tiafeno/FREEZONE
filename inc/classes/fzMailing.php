<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 13/07/2019
 * Time: 00:50
 */

namespace classes;


class fzMailing
{
    public $ID;
    public $attach_post; // bigint
    public $response_post; // bigint
    public $sender; // bigint

    public function __construct ($post_id, $api = false) {
        $post = \WP_Post::get_instance($post_id);
        foreach (get_object_vars($post) as $key => $value)
            $this->$key = $value;

        $this->attach_post = get_field('attach_post', $this->ID); // Post fz_sav type (id)
        $this->response_post = get_field('response_post', $this->ID); // Post fz_mailing type (id)
        $this->sender = get_field('sender', $this->ID); // User id
    }

}


add_action('init', function () {
    register_post_type('fz_mailing', [
        'label' => "boite aux lettres",
        'labels' => [
            'name' => "Boite aux lettres",
            'singular_name' => "Boite aux lettres",
            'add_new' => 'Ajouter',
            'add_new_item' => "Ajouter un nouveau",
            'edit_item' => 'Modifier',
            'view_item' => 'Voir',
            'search_items' => "Trouver",
            'all_items' => "Tous les lettres",
            'not_found' => "Aucun",
            'not_found_in_trash' => "La corbeille est vide"
        ],
        'public' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'show_ui' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'boite-au-lettre'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'query_var' => true
    ]);
}, 10);


add_action('rest_api_init', function() {
    foreach ( ['attach_post', 'response_post', 'sender'] as $field ) {
        register_rest_field('fz_mailing', $field, [
            'update_callback' => function ($value, $object, $field_name) {
                return update_field($field_name, $value, $object->ID);

            },
            'get_callback' => function ($object, $field_name) {
                $params = $_REQUEST;
                $value = get_field($field_name, $object['id']);

                $request = new \WP_REST_Request();
                $request->set_param('context', 'edit');

                if (isset($params['context']) && $params['context'] === "edit") {
                    if (empty($value) || is_null($value)) return $value;
                    switch ($field_name):
                        case 'attach_post':
                        case 'response_post':
                            return  $field_name === "attach_post" ? new fzSav(intval($value), true) : new fzMailing(intval($value), true);
                            break;
                        case 'sender':
                            $controller = new \WP_REST_Users_Controller();
                            $response = $controller->prepare_item_for_response(new \WP_User(intval($value)), $request);
                            return $response->get_data();
                            break;
                    endswitch;

                }
                return $value;
            }
        ]);
    }
});
