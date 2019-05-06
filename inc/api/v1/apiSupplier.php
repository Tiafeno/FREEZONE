<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 11:27
 */

class apiSupplier
{
    public function __construct () { }
    public function collect_suppliers (WP_REST_Request $rq)
    {
        $length = (int)$_POST['length'];
        $start = (int)$_POST['start'];
        $args = [
            'number' => $length,
            'offset' => $start,
            'role' => 'fz-supplier',
        ];

        $the_query = new WP_User_Query($args);
        if ( ! empty($the_query->get_results()) ) {

            $suppliers = array_map(function ($supplier) {
                $response = new \classes\fzSupplier($supplier->ID);
                $response->lastname = $response->last_name;
                $response->firstname = $response->first_name;
                return $response;
            }, $the_query->results);

            return [
                "recordsTotal" => (int)$the_query->total_users,
                "recordsFiltered" => (int)$the_query->total_users,
                'data' => $suppliers
            ];
        } else {

            return [
                "recordsTotal" => (int)$the_query->total_users,
                "recordsFiltered" => (int)$the_query->total_users,
                'data' => []
            ];
        }

    }
}