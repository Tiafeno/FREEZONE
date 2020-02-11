<?php
/**
 * The template for displaying pages
 * Cette page permet de mettre à jours les articles en attente d'un fournisseur
 *
 */

/**
 * Template Name: Update articles supplier
 */

$User = wp_get_current_user();
wp_enqueue_script('underscore');
wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);
wp_enqueue_script('momenjs', "https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js", ['jquery']);
// https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js
wp_enqueue_script('vue', "https://cdn.jsdelivr.net/npm/vue/dist/vue.js", ['jquery']);
wp_enqueue_script('update-articles', get_stylesheet_directory_uri() . '/assets/js/update-articles.js', ['jquery', 'vue', 'underscore'], '1.0.5');
wp_localize_script('update-articles', 'rest_api', [
    //'root' => esc_url_raw(rest_url()),
    //'nonce' => wp_create_nonce('wp_rest'),
    'ajax_url' =>  admin_url('admin-ajax.php'),
    'account_url' => get_permalink( wc_get_page_id( 'myaccount' ) )
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
            // if (isset($_GET['articles'])) {
            //     fz_reload_header();
            // }
        }
    }
}

function fz_reload_header () {
    $url = get_the_permalink();
    setcookie('freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + (60 * 30));
    setcookie('__freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + (60 * 30));
    wp_redirect(add_query_arg([
        //'articles' => isset($_GET['articles']) ? $_GET['articles'] : '',
        'fznonce' => isset($_GET['fznonce']) ? $_GET['fznonce'] : '',
        'email' => isset($_GET['email']) ? $_GET['email'] : '',
        'e' => isset($_GET['e']) ? $_GET['e'] : '',
    ], $url));
    exit;
}



get_header();
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
    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container'); ?> inner">
        <div class="row">
            <div id="main-content" class="main-page">
                <main id="main" class="site-main clearfix" role="main" style="margin-bottom: 40px">
                    <?php
                    wc_print_notices();
                    if ( ! is_user_logged_in() ) {
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
                        <div id="app-update-articles">
                            <form method="POST" name="form_update" class="updated-form" @submit="submitForm" action="" >
                                <div class="table-responsive">
                                    <table class="table" style="margin-bottom: 0px !important">
                                        <thead style="background: #2584cf;color: white;" v-if="articles.length > 0">
                                            <tr>
                                                <th scope="col">Designation</th>
                                                <th scope="col">Qté disponible</th>
                                                <th scope="col">Qté demandée</th>
                                                <th scope="col">Statut de produit</th>
                                                <th scope="col">Prix en ariary</th>
                                                <th scope="col">Garantie</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(article, index) in articles">
                                                <th scope="row">
                                                    <div class="form-row form-row-wide designation" style="font-weight: lighter">
                                                    {{ article.designation }}
                                                    </div>
                                                </th>
                                                <td width="10%">
                                                    <div class="stock">
                                                        <input type="number" v-on:blur="onChangeQty($event, index)" v-model="article.qty_disp"
                                                        v-bind:disabled="article.condition == 1 || article.condition == 2" style="width: 100%;" min="0"
                                                            class="form-control radius-0 input-qty-disp"/>
                                                    </div>
                                                </td>
                                                <td width="10%">
                                                    <div class="qty_request">
                                                        <input type="number" v-model="article.qty_ask"  disabled="disabled"  style="width: 100%;" min="0" class="form-control radius-0 " />
                                                    </div>
                                                </td>
                                                <td width="12%">
                                                    <div class="statut">
                                                        <select v-model="article.condition" class="form-control radius-0" v-on:change="onChangeCondition($event, index)" style="width: 100%;">
                                                            <option :value="condition.key" :checked="condition.key == article.condition" v-for="condition in condition_product">
                                                                {{ condition.value }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td width="15%">
                                                    <div class="price">
                                                        <input type="number" v-model="article.cost" step="1" style="width: 100%;" min="0" class="form-control radius-0" />
                                                    </div>
                                                </td>
                                                <td width="10%">
                                                    <div class="garentee">
                                                        <select v-model="article.garentee" v-bind:disabled="article.garentee != 0" class="form-control radius-0" style="width: 100%;">
                                                            <option value="0">Aucun</option>
                                                            <option :value="item" v-for="item in _.range(1, 13)">{{ item }} mois</option>
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                </table>
                                </div>
                                
                                <div class="row" v-if="articles.length > 0" style="margin-left: 15px; margin-top: 15px">
                                    <button class="btn btn-primary radius-0" type="submit" id="submit-update-form">Enregistrer</button>
                                </div>
                                <div class="row" v-if="articles.length == 0 && loading == false">
                                    Vous n'avez aucun articles en attente
                                </div>
                                <div class="row" v-if="loading == true">
                                    Chargement...
                                </div>
                            </form>
                        </div>
                    <?php } ?>

                    <!--                    Test -->
                    <!--                    End test -->

                </main><!-- .site-main -->
            </div><!-- .content-area -->
        </div>
    </section>
<?php get_footer(); ?>