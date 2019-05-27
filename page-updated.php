<?php
/**
 * The template for displaying pages
 * Cette page permet de mettre à jours les articles en attente d'un fournisseur
 *
 */

/**
 * Template Name: Page Updated Review
 */
get_header();
$sidebar_configs = yozi_get_page_layout_configs();

yozi_render_breadcrumbs();
?>
    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container');?> inner">
        <?php wc_print_notices(); ?>
        <?php yozi_before_content( $sidebar_configs ); ?>
        <div class="row">
            <?php yozi_display_sidebar_left( $sidebar_configs ); ?>
            <div id="main-content" class="main-page <?php echo esc_attr($sidebar_configs['main']['class']); ?>">
                <main id="main" class="site-main clearfix" role="main">

                    <?php
                        if ( ! is_user_logged_in()) {
                            wc_get_template('woocommerce/myaccount/form-login.php');
                        } else {
                            ?>
                            <div style="margin-bottom: 14px">
                                <?php
                                while ( have_posts() ) : the_post();
                                    the_content();
                                endwhile;
                                ?>
                            </div>
                                <form method="POST" name="form_">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <p class="form-row form-row-wide">

                                            </p>
                                        </div>
                                        <div class="col-sm-4">
                                            <p class="form-row form-row-wide">
                                                <label for="reg_stock">Stock <span class="required">*</span></label>
                                                <input type="text" class="input-text" name="stock"
                                                    id="reg_stock"
                                                    value="" />
                                            </p>
                                        </div>
                                        <div class="col-sm-4">
                                            <p class="form-row form-row-wide">
                                                <label for="reg_mark">Prix <span class="required">*</span></label>
                                                <input type="text" class="input-text" name="stock"
                                                    id="reg_prix"
                                                    value="" />
                                            </p>
                                        </div>

                                        <div class="col-sm-4">
                                            <p class="form-row">
                                                <?php
                                                // @link https://codex.wordpress.org/Function_Reference/wp_nonce_field
                                                wp_nonce_field( 'freezone-updated', 'updated-nonce' );
                                                ?>
                                                <input type="hidden" class="input-text" name="stock"  id="article_id" value="" />
                                                <input type="submit" class="btn btn-success btn-block" name="register"
                                                    value="Envoyer" />
                                            </p>
                                    </div>
                                </form>
                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right( $sidebar_configs ); ?>
        </div>
    </section>
<?php get_footer(); ?>