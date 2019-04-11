<?php
add_action('init', function () {
    add_shortcode('fz_after_sales_service', 'after_sales_service');
});

function after_sales_service ($attrs, $content = '')
{
    global $Engine, $wp_query;
    if (!$Engine instanceof Twig_Environment) return false;
    extract(shortcode_atts(array(), $attrs));
    if (!is_user_logged_in()) {
        wc_add_notice("Désolé! Vous devez vous connecter avant de remplir le formulaire", 'error');
        get_template_part("woocommerce/myaccount/form", 'login');
    }

    if (isset($wp_query->query_vars['sav'])) {
        // TODO: Enregistrer les données du formulaire
    }

    return $Engine->render('@SC/after_sales_service.html', []);
}