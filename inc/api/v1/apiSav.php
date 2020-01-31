<?php
class apiSav
{
    public function __construct () { 
        add_action('rest_api_init', function () {
            register_rest_route('api', '/savs/', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [&$this, 'collect_sav'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ]
            ]);

            register_rest_route('api', '/sav/retrieve/(?P<com_id>\d+)', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [&$this, 'get_customers_responsible'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ]
            ]);

            // Envoyer un mail au client pour la demande de servise
            register_rest_route('api', '/mail/sav/(?P<sav_id>\d+)', [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => function(WP_REST_Request $rq) {
                        $params  = $_REQUEST;
                        $subject = stripslashes($params['subject']);
                        $message = stripslashes($params['message']);
                        $sender = (int)$params['sender'];
                        $mailing_id = (int)$params['mailing_id'];
                        $sav_id = (int)$rq['sav_id'];
                        do_action('fz_sav_contact_mail', $sav_id, $sender, $mailing_id, $subject, $message);
                    },
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => [
                        'sav_id' => [
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param);
                            }
                        ]
                    ]
                ]
            ], false);
        });
    }
    public function collect_sav(WP_REST_Request $request) {
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

    public function get_customers_responsible(WP_REST_Request $request) {
        $commercial_id = $request['com_id'];
    }

}

new apiSav();