<?php

namespace classes;

/**
 * untitledModel - class.fzSupplier.php
 *
 * $Id$
 *
 * This file is part of untitledModel.
 *
 * Automatically generated on 23.04.2019, 17:34:38 with ArgoUML PHP module
 * (last revised $Date: 2010-01-12 20:14:42 +0100 (Tue, 12 Jan 2010) $)
 *
 * @author firstname and lastname of author, <author@example.org>
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}


/**
 * Inherit property
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
 * @property int $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 */
class fzSupplier extends \WP_User
{

    public $error = null;
    public $address = null;
    public $phone = null;
    public $company_name = null;
    public $mail_commercial_cc = null;
    public $mail_logistics_cc = null;
    public $firstname;
    public $lastname;
    public $stat;
    public $nif;
    public $rc;
    public $cif;
    public $date_add;

    /**
     * Short description of attribute reference
     *
     * @access public
     * @var String
     */
    public $reference = null; // F1, F2 ...


    /**
     * Short description of method __construct
     *
     * @access public
     * @author Tiafeno and Finel of author, <tifenofnel@gmail.com>
     * @param  Integer user_id
     * @return mixed
     */
    public function __construct ($user_id)
    {
        parent::__construct($user_id);
        if (in_array('fz-supplier', $this->roles)) {
            $this->reference = get_field('reference', 'user_' . $this->ID);

            $mail_cc_fields = ['mail_commercial_cc', 'mail_logistics_cc'];
            foreach ( $mail_cc_fields as $field ) {
                $cc = get_field($field, 'user_' . $this->ID);
                $this->$field = $cc ? \explode(',', $cc) : [];
            }
            /**
             * Récuperer la valeur des champs pour les informations du fournisseur
             */
            $infos = ['stat', 'nif', 'rc', 'cif'];
            foreach ( $infos as $info ) {
                $value = get_field($info, 'user_' . $this->ID);
                $this->$info = $value;
            }

            $this->address = get_field('address', 'user_' . $this->ID);
            $this->phone = get_field('phone', 'user_' . $this->ID);

            $User = wp_get_current_user();
            // Vérifier si l'utilisateur est un administrateur
            $admin = $User->ID === 0 ? null : (in_array('administrator', $User->roles) ? 'administrator' : null);
            // Afficher le nom de l'entreprise ou la société au administrateur seulement
            $this->company_name = $admin === 'administrator' ? get_field('company_name', 'user_' . $this->ID) : $this->reference;
            $this->firstname = $this->first_name;
            $this->lastname = $this->last_name;
            $register = $this->user_registered;
            $this->date_add = date('Y-m-d H:i:s', strtotime($register));
        } else {
            $this->error = new \WP_Error('exist', "Le fournisseur n'existe pas");
        }
    }

} /* end of class fzSupplier */

?>