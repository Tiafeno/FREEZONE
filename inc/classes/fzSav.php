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
    private $user_id;
    public $author;
    public $mark;
    public $type;
    public $reference;
    public $product_number;
    public $serial_number;
    public $description;
    public $date_appointment;
    public $date_add;

    private $model;
    public function __construct ($sav_id, $api = false) {
        $this->model = new \apiSav();
        $Sav = $this->model->get(intval($sav_id));
        $sav_objet_vars = get_object_vars($Sav);
        foreach ($sav_objet_vars as $key => $value) {
            $this->$key = $value;
        }

        $this->author = new \WP_User(intval($this->user_id));

        if ($api) :
            $this->author->first_name = $this->author->first_name;
            $this->author->last_name = $this->author->last_name;
            endif;


    }
}