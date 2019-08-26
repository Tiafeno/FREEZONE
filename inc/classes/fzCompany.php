<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 08/07/2019
 * Time: 13:40
 */


namespace classes;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}


class fzCompany extends \WP_User
{
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

    public $address = null;
    public $phone;
    public $reference;
    /**
     * @var integer
     * 0: En attente
     * 
     * 1: Professionel
     * 2: Revendeur
     */
    public $type;
    public $company_name;
    public $stat;
    public $nif;
    public $rc;
    public $cif;


    public function __construct ($id = 0, $name = '', $site_id = '') {
        parent::__construct($id, $name, $site_id);

        $this->address = get_field('address', 'user_'.$this->ID);
        $this->phone = get_field('phone', 'user_'.$this->ID);
        $this->reference = get_field('client_reference', 'user_'.$this->ID);
        $this->type = get_field('role_office', 'user_'.$this->ID);
        $this->company_name = get_field('company_name', 'user_'.$this->ID);
        $this->stat = get_field('stat', 'user_'.$this->ID);
        $this->nif = get_field('nif', 'user_'.$this->ID);
        $this->rc = get_field('rc', 'user_'.$this->ID);
        $this->cif = get_field('cif', 'user_'.$this->ID);
    }


}