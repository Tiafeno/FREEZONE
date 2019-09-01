<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 18/05/2019
 * Time: 21:58
 */

class apiSav
{
    public function __construct () { }
    public function get(WP_REST_Request $request) {
        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];
        $args = [
            'limit' => $length,
            'offset' => $start,
            'paginate' => true,
            'post_type' => 'fz_sav'
        ];

        $the_query = new WP_Query($args);
        $savs = array_map(function ($sav) {
            $fzSav = new \classes\fzSav($sav->ID, true);
            return $fzSav;
        }, $the_query->posts);

        if ($the_query->have_posts()) {
            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => $savs
            ];
        } else {

            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => []
            ];
        }
    }

}