<?php
/**
 * The template for displaying pages
 * Cette page permet de mettre à jours les articles en attente d'un fournisseur
 *
 */

/**
 * Template Name: Page Updated Review
 */

$fzProducts = [];
$paged = 1;

if (!empty($_POST)) {
    // Code for update article here...
    if (wp_verify_nonce($_POST['updated-nonce'], 'freezone-updated')) {
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $article_id = $_POST['article_id'];

        $article = new \classes\fzSupplierArticle(intval($article_id));
        $article->set_price((int) $price);
        $article->set_total_sales( (int) $stock);
        $article->save();

        $article->update_date_review(); // Mettre à jour l'article
        wc_add_notice("Article <b>{$article->name}</b> mis à jour avec succès", 'success');
    }
}

if (!empty($_GET)) {
    if (isset($_GET['fznonce']) && isset($_GET['email'])) {
        $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            $User = get_user_by('email', $email);
            $nonce = stripslashes($_GET['fznonce']);
            $nonce = base64_decode($nonce);

            if (!isset($_GET['e'])) wp_redirect(home_url('/'));

            $expired = stripslashes($_GET['e']);
            $expired_format = base64_decode($expired);
            $expired_date = strtotime($expired_format);

            if (!$expired_date) wp_redirect(home_url('/'));

            $now = date_i18n('Y-m-d H:i:s');
            $now_date = strtotime($now);

            // Si le jeton a expiré on ajoute une redirection
            if ($nonce !== "update-{$User->ID}" || $now_date > $expired_date) {
                wp_redirect(get_permalink(wc_get_page_id('myaccount')));
                exit;
            }

            // Ajouter une ssession utilisateur s'il n'est pas connecter
            if (!is_user_logged_in()) {
                wp_set_current_user($User->ID);
                wp_set_auth_cookie($User->ID);

                fz_reload_header();
            }

            // Déconnecter l'utilisateur actuelle s'il y a une session
            $current_user = wp_get_current_user();
            if ($current_user->ID !== $User->ID) {
                wp_logout();

                // Ajouter une nouvelle session utilisateur
                wp_set_current_user($User->ID);
                wp_set_auth_cookie($User->ID);

                fz_reload_header();
            }

            if (isset($_GET['articles'])) {
                fz_reload_header();
            }

            global $wpdb;
            $articles = isset($_COOKIE['freezone_ua']) ? $_COOKIE['freezone_ua'] : '';
            $item_articles = explode(',', $articles);
            if (isset($_POST['article_id'])) {
                $article_id = $_POST['article_id'];
                $item_articles = array_filter($item_articles, function ($item) use ($article_id) { return $item != $article_id; });
                $articles = implode(',', $item_articles);
                // Ajouter les identifiants des articles en attente dans la cookie
                setcookie('freezone_ua', $articles, time() + 1800);
            }
            
            if (empty($articles)) {
                // Mise à jour reussi! Envoye un mail au adminstrateur
                do_action('fz_updated_articles_success', $_COOKIE['__freezone_ua'], $User->ID);
                $articles = '0'; // Ajouter le '0' pour corriger le bug dans la requete SQL
            }

            $paged = get_query_var('pa_') ? get_query_var('pa_') : 1;
            $length = 10;
            $offset = $length * ($paged - 1);
            $sql = <<<CODE
SELECT 
    SQL_CALC_FOUND_ROWS
    pts.ID
FROM
    $wpdb->posts AS pts
WHERE
    pts.ID IN (SELECT 
            post_id
        FROM
            $wpdb->postmeta
        WHERE
            meta_key = 'date_review'
                AND CAST(meta_value AS DATETIME) < CAST('$now' AS DATETIME))
    AND
    pts.ID IN ($articles) 
    AND pts.post_type = 'fz_product'
    AND pts.post_status = 'publish'
LIMIT $length OFFSET $offset
CODE;

            $post_products = $wpdb->get_results($sql);
            $count_sql = "SELECT FOUND_ROWS()";
            $total = $wpdb->get_var($count_sql);

            foreach ( $post_products as $_post ) {
                $fzProducts[] = new \classes\fzSupplierArticle($_post->ID);
            }
        }
    }
}

function fz_reload_header ()
{
    $current_url = get_the_permalink();
    setcookie('freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + 1800);
    setcookie('__freezone_ua', isset($_GET['articles']) ? $_GET['articles'] : '', time() + 1800);


    wp_redirect(add_query_arg([
        //'articles' => isset($_GET['articles']) ? $_GET['articles'] : '',
        'fznonce' => isset($_GET['fznonce']) ? $_GET['fznonce'] : '',
        'email' => isset($_GET['email']) ? $_GET['email'] : '',
        'e' => isset($_GET['e']) ? $_GET['e'] : '',
    ], $current_url));
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

        .price, .stock, .designation, .reference {
            position: relative;
        }

        .price::before {
            content: 'MGA';
        }

        .stock::before {
            content: 'Qté';
        }

        .reference::before {
            content: 'Date de revision';
            font-size:11px;
        }

        .designation::before {
            content: '';
        }

        .price::before,
        .stock::before,
        .reference::before,
        .designation::before {
            display: block;
            position: absolute;
            bottom: -14px;
            left: 0;
            font-size: 12px;
            background-color: #5aa90c;
            color: white;
            padding-left: 10px;
            padding-right: 10px;
            font-weight: bold;
        }
    </style>
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
                        if (!empty($fzProducts)) :
                            foreach ( $fzProducts as $article ): ?>
                                <form method="POST" name="form_<?= $article->ID ?>" class="updated-form">
                                    <table class="table table-striped">
                                        <tbody>
                                        <tr>
                                            <th scope="row">
                                                <div class="form-row form-row-wide designation"
                                                     style="font-weight: lighter">
                                                    <?= $article->name ?>
                                                </div>
                                            </th>
                                            <td width="15%">
                                                <div class="form-row form-row-wide reference"
                                                     style="font-weight: lighter">
                                                    <?php
                                                    echo $article->date_review;
                                                    ?>
                                                </div>
                                            </td>
                                            <td width="15%">
                                                <div class="stock">
                                                    <input type="number" name="stock" style="width: 100%;"
                                                           id="reg_stock"
                                                           value="<?= $article->total_sales ?>"/>
                                                </div>
                                            </td>
                                            <td width="15%">
                                                <div class="price">
                                                    <input type="number" name="price" style="width: 100%;"
                                                           id="reg_price"
                                                           value="<?= $article->regular_price ?>"/>
                                                </div>
                                            </td>
                                            <td width="25">
                                                <div class="form-row">
                                                    <?php
                                                    // @link https://codex.wordpress.org/Function_Reference/wp_nonce_field
                                                    wp_nonce_field('freezone-updated', 'updated-nonce');
                                                    ?>
                                                    <input type="hidden" class="input-text" name="article_id"
                                                           id="article_id"
                                                           value="<?= $article->ID ?>"/>

                                                    <input type="submit" class="btn btn-theme radius-0"
                                                           style="margin: auto;display: table;"
                                                           value="Mettre à jour"/>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </form>
                            <?php
                            endforeach;


                            $pagination = '<div class="apus-pagination"><ul class="page-numbers">';
                            $pagination .= paginate_links([
                                'base' => @add_query_arg('pa_', '%#%'),
                                'format' => '?pa_=%#%',
                                'current' => max(1, get_query_var('pa_')),
                                'type' => 'list',
                                'current' => $paged,
                                'total' => round($total / $length)

                            ]);
                            $pagination .= '</ul></div>';

                            echo $pagination;
                        else:
                            echo "Vous n'avez aucun produit en attente de révision";
                        endif;
                        ?>
                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
        </div>
    </section>
<?php get_footer(); ?>