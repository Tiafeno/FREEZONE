<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 07/08/2019
 * Time: 12:49
 */

namespace classes;


class fzCarousel
{
    public function __construct () { }

}

add_action('init', function () {
}, 10);


add_action('rest_api_init', function () {
    register_rest_route('api', '/carousel', [
        [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => function () {
                $carousel = get_option('medias_carousel');
                return $carousel;
            }
        ],
        [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function () {
                $id = isset($_POST['id']) && !empty($_POST['id']) ? $_POST['id'] : null;
                if (!is_null($id)) {
                    $attach_id = intval($id);
                    $attach_ids = get_option('medias_carousel');
                    if (false === $attach_ids || empty($attach_ids) || is_null($attach_ids)) {
                        $attach_ids = [];
                    };
                    if (is_array($attach_ids)) {
                        $attach_ids = array_map(function ($id) { return intval($id); }, $attach_ids);
                        array_push($attach_ids, $attach_id);
                        $result = update_option('medias_carousel', $attach_ids);

                        return $result;
                    } else {
                        $result = update_option('medias_carousel', [$attach_id]);
                        return $result;
                    }
                }
            }
        ]
    ]);
});
