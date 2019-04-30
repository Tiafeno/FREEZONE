<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 15:29
 */

namespace classes;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/**
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int    $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 */
class fzParticular extends \WP_User
{
    public $address = null;
    public $phone;
    public $firstname;
    public $lastname;

    public function __construct ($id = 0, $name = '', $site_id = '') {
        parent::__construct($id, $name, $site_id);
        $this->firstname = $this->first_name;
        $this->lastname = $this->last_name;
        $this->address = get_field('address', 'user_'.$this->ID);
        $this->phone = get_field('phone', 'user_'.$this->ID);
    }

}