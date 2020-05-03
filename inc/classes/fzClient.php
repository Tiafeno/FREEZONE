<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 23/01/2020
 * Time: 20:16
 */

namespace classes;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

class fzClient
{
    private static $role = "";
    private static $customer = null;
    private static $_instance = null;
    private static $customer_id = 0;
    public function  __construct () { }

    /**
     * @param int $id
     * @param bool $hasInstance
     * @return fzClient|null
     */
    public static function initializeClient($id = 0, $hasInstance = true) {
        self::$_instance = new self;
        $customer_id = intval($id);
        self::$customer_id = $customer_id;
        $user_meta = get_userdata($customer_id);
        self::$_instance::$role = $user_meta->roles[0];
        if ($hasInstance)
            self::$_instance::$customer = in_array('fz-company', $user_meta->roles) ? new fzCompany($customer_id) : 
                new fzParticular($customer_id);
        return self::$_instance;
    }

    public function get_client() {
        return self::$customer;
    }

    public function get_role() {
        return self::$role;
    }

    public function get_responsible() {
        $commercial_id = get_user_meta(self::$customer_id, "responsible", true);
        return $commercial_id ? intval($commercial_id) : 0;
    }
}

// add_action( 'rest_user_query', 'custom_topic_query', 10, 2 );
// function custom_topic_query( $args, $request ) {
//     if ( isset($request['search']) ) {
//         $pre_meta_query = array(
//             'relation' => 'OR'
//         );
//         $topics = explode( ' ', $request['search'] );  // NOTE: Assumes comma separated taxonomies
//         for ( $i = 0; $i < count( $topics ); $i++) {
//             array_push( $pre_meta_query, array(
//                 'key' => 'company_name',
//                 'value' => $topics[$i],
//                 'compare' => "LIKE"
//             ));
//         }
//         $meta_query = array(
//             'relation' => 'AND',
//             $pre_meta_query
//         );
//         $args[ 'meta_query' ] = $meta_query;
//     }
//     return $args;
// } 