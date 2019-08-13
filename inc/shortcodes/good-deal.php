<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 13/08/2019
 * Time: 09:45
 */

add_action('init', function () {
    add_shortcode('fz_good_deal', 'fn_good_deal');
});

function fn_good_deal($attr, $content = '') {

}
