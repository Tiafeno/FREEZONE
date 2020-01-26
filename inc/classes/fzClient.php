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
    public function  __construct () { }

    /**
     * @param int $id
     * @return fzClient|null
     */
    public static function initializeClient($id = 0) {
        self::$_instance = new self;
        $customer_id = intval($id);
        $user_meta = get_userdata($customer_id);
        self::$_instance::$role = $user_meta->roles[0];
        self::$_instance::$customer = in_array('fz-company', $user_meta->roles) ? new fzCompany($customer_id) : new fzParticular($customer_id);
        return self::$_instance;
    }

    public function get_client() {
        return self::$customer;
    }

    public function get_role() {
        return self::$role;
    }


}