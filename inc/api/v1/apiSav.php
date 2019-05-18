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
    public function get($sav_id) {
        global $wpdb;
        $sql = <<<SQL
SELECT * FROM {$wpdb->prefix}sav WHERE ID = $sav_id
SQL;
        return $wpdb->get_row($sql);

    }
    public function delete($sav_id) {
        global $wpdb;
        $query = <<<QRY
DELETE FROM {$wpdb->prefix}sav WHERE ID = $sav_id
QRY;
        return $wpdb->query($query);

    }

}