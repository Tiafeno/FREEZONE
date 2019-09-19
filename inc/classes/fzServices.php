<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 29/04/2019
 * Time: 18:51
 */

class fzServices
{
    protected $sector_activity = [
        [
            'id' => 1,
            'name' => "Informatique/Developpeur/Marketing"
        ]
    ];
    public function __construct () { }
    public function get_sector_activity() {
        return $this->sector_activity;
    }


}