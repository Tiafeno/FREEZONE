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
    private $fields = [
        'client',
        'product',
        'mark',
        'status',
        'product_provider',
        'date_appointment',
        'date_purchase',
        'bill',
        'serial_number',
        'reference',
        'auctor',
        'description'
    ];

    public function __construct ($sav_id, $api = false) {
        foreach ($this->fields as $key) {
            $field_value = get_field($key, $sav_id);
            $this->$key = $field_value;
        }

    }
}