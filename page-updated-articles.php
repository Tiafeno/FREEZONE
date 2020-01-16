<?php
/**
 * The template for displaying pages
 * Cette page permet de mettre à jours les articles en attente d'un fournisseur
 *
 */

/**
 * Template Name: Update articles supplier
 */

$fzProducts = [];

wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);
// https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js
wp_enqueue_script('vue', "https://cdn.jsdelivr.net/npm/vue/dist/vue.js", ['jquery']);
wp_localize_script('vue', 'rest_api', [
    'rest_url' => esc_url_raw(rest_url()),
    'nonce' => wp_create_nonce('wp_rest'),
    'admin_url' =>  admin_url('admin-ajax.php'),
]);

/**
 * Exmples:
 * https://jsfiddle.net/crswll/24txy506/9/ (formArray)
 */
if (!empty($_GET)) {
    if (isset($_GET['fznonce']) && isset($_GET['email'])) {
        $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            $User = get_user_by('email', $email);
            $nonce = stripslashes($_GET['fznonce']);
            $nonce = base64_decode($nonce);
            // Si la date d'expiration n'est pas definie dans le lien, on redirige vers la page d'accueil
            if (!isset($_GET['e'])) wp_redirect(home_url('/'));
            // Contient la date d'expiration du lien (2 jours)
            $expired = stripslashes($_GET['e']);
            $expired_format = base64_decode($expired);
            $expired_date = strtotime($expired_format);
            if (!$expired_date) wp_redirect(home_url('/'));
            $now = date_i18n('Y-m-d H:i:s'); // Date actuel depuis wordpress
            $now_date = strtotime($now);
            // Si le jeton a expiré on ajoute une redirection dans l'espace client
            if ($nonce !== "update-{$User->ID}" || $now_date > $expired_date) {
                wp_redirect(get_permalink(wc_get_page_id('myaccount')));
                exit;
            }
            // Ajouter une ssession utilisateur s'il n'est pas connecter
            if (!is_user_logged_in()) {
                // Ajouter la session utilisateur
                wp_set_current_user($User->ID);
                wp_set_auth_cookie($User->ID);
                // Redicrection
                fz_reload_header();
            }
            // Déconnecter l'utilisateur actuelle s'il y a une session
            $current_user = wp_get_current_user();
            if ($current_user->ID !== $User->ID) {
                // Deconnexion
                wp_logout();
                // Ajouter une nouvelle session utilisateur
                wp_set_current_user($User->ID);
                wp_set_auth_cookie($User->ID);
                // redirection
                fz_reload_header();
            }
            // Si les articles ids sont present dans le lien GET, on recharche la page
            if (isset($_GET['articles'])) {
                // Redirection
                fz_reload_header();
            }
            global $wpdb;
            // Recuperer le cookie aui contient les IDS des articles en attente de mise à jours
            $articles = isset($_COOKIE['freezone_ua']) ? $_COOKIE['freezone_ua'] : '';
            $item_articles = explode(',', $articles);
            // Recuperer la date d'aujourd'hui depuis 06h du matin, car tous les articles sont considerer "en attente"
            // a partir de 06h du matin
            $today_date_time = new DateTime($now);
            $today_date_time->setTime(6, 0, 0); // Ajouter 06h du matin
            $sql = <<<CODE
SELECT  SQL_CALC_FOUND_ROWS pts.ID
FROM $wpdb->posts AS pts
WHERE
    pts.ID IN ($articles) 
    AND pts.post_type = 'fz_product'
    AND pts.post_status = 'publish'
    AND pts.ID IN (SELECT post_id
        FROM $wpdb->postmeta
        WHERE meta_key = 'date_review'
            AND CAST(meta_value AS DATETIME) < CAST('{$today_date_time->format("Y-m-d H:i:s")}' AS DATETIME)
    )
CODE;

            $post_products = $wpdb->get_results($sql);
            $count_sql = "SELECT FOUND_ROWS()";
            $total = $wpdb->get_var($count_sql);
            // Boucler une a une les articles trouver dans la recherche
            foreach ( $post_products as $_post ) {
                $article = new \classes\fzSupplierArticle($_post->ID);
                $product_id = $article->get_product_id();
                $quantity = [];
                // Récuperer les quantité demander pour cette article dans les commandes "en attente"
                $orders = new WP_Query([
                    'post_type' => wc_get_order_types(),
                    'post_status' => array_keys(wc_get_order_statuses()),
                    "posts_per_page" => -1,
                    'meta_query' => [
                        [
                            'key' => 'position',
                            'value' => 0, // Tous les commande en attente
                            'compare' => '='
                        ]
                    ]
                ]);
                if (empty($orders->posts)) continue;
                // Boucler tous le commandes trouver dans la recherche
                foreach ( $orders->posts as $order ) {
                    $current_order = new WC_Order($order->ID);
                    $items = $current_order->get_items();
                    foreach ( $items as $item_id => $item ) {
                        $data = $item->get_data();
                        if ($data['product_id'] === $product_id) {
                            $quantity[] = (int)$data['quantity'];
                        }
                    }
                }
                $article->quantity = array_sum($quantity);
                $fzProducts[] = $article;
            }
        }
    }
}

function fz_reload_header ()
{
    $current_url = get_the_permalink();
    setcookie('freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + (60 * 30));
    setcookie('__freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + (60 * 30));


    wp_redirect(add_query_arg([
        //'articles' => isset($_GET['articles']) ? $_GET['articles'] : '',
        'fznonce' => isset($_GET['fznonce']) ? $_GET['fznonce'] : '',
        'email' => isset($_GET['email']) ? $_GET['email'] : '',
        'e' => isset($_GET['e']) ? $_GET['e'] : '',
    ], $current_url));
    exit;
}

get_header();
$current_user = wp_get_current_user();
$sidebar_configs = yozi_get_page_layout_configs();
yozi_render_breadcrumbs();
?>
    <style type="text/css">
        .updated-form input[type='text'],
        .updated-form input[type='number'] {
            padding: 0px !important;
            text-align: center;
        }

        .updated-form input[type='submit'] {
            padding-top: 0;
            padding-bottom: 0;
        }

        .price, .stock, .designation, .reference, .qty_request {
            position: relative;
        }
    </style>

    <script type="text/javascript">
        var _apiArticles = <?= json_encode($fzProducts); ?>;
        var _userId = <?= $current_user->ID ?>;
    </script>

    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container'); ?> inner">
        <?php wc_print_notices(); ?>
        <div class="row">
            <div id="main-content" class="main-page">
                <main id="main" class="site-main clearfix" role="main" style="margin-bottom: 40px">

                    <?php
                    wc_print_notices();
                    if (!is_user_logged_in()) {
                        wc_get_template('woocommerce/myaccount/form-login.php');
                    } else {
                        ?>
                        <div style="margin-bottom: 14px">
                            <?php
                            while (have_posts()) : the_post();
                                the_content();
                            endwhile;
                            ?>
                        </div>

                        <?php
                        if (!empty($fzProducts)) : ?>

                                <form method="POST" name="form_update" class="updated-form" id="app-update-articles">
                                    <table class="table table-striped" style="margin-bottom: 0px !important">

                                        <thead style="background: #2584cf;color: white;">
                                            <tr>
                                                <th scope="col">Designation</th>
                                                <th scope="col">Qté disponible</th>
                                                <th scope="col">Qté demandée</th>
                                                <th scope="col">Prix en ariary</th>
                                                <th scope="col">Garantie</th>
                                                <th scope="col">#</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <div class="form-row form-row-wide designation"
                                                        style="font-weight: lighter">
                                                        <?= $article->name ?>
                                                    </div>
                                                </th>
                                                <td width="15%">
                                                    <div class="stock">
                                                        <input type="number" name="stock" style="width: 100%;"
                                                            id="reg_stock" min="0"
                                                            value="0"/>
                                                    </div>
                                                </td>
                                                <td width="15%">
                                                    <div class="qty_request">
                                                        <input type="number" disabled="disabled" name="qty_request" style="width: 100%;"
                                                            id="reg_qty_request" min="0"
                                                            value="<?= $article->quantity ?>"/>
                                                    </div>
                                                </td>
                                                <td width="15%">
                                                    <div class="price">
                                                        <input type="number" name="price" style="width: 100%;"
                                                            id="reg_price" min="0"
                                                            value="<?= $article->regular_price ?>"/>
                                                    </div>
                                                </td>
                                                <td width="15%">
                                                    <div class="garentee">
                                                        <select name="garentee"  style="width: 100%;">
                                                            <option value="">Aucun</option>
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </form>
                            <?php 
                        else:
                            echo "Vous n'avez aucun article en attente de révision";
                        endif;
                        ?>
                    <?php } ?>

                    <!--                    Test -->
                    <!--                    End test -->

                </main><!-- .site-main -->
            </div><!-- .content-area -->
        </div>
    </section>
<?php get_footer(); ?>