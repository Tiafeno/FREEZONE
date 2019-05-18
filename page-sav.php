<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package FreeZone
 * @subpackage Yozi
 */
/*
 *Template Name: Page SAV
 */

wp_enqueue_style("datepicker", get_stylesheet_directory_uri() . '/assets/css/bootstrap-datepicker.min.css');
wp_enqueue_script('datepicker', get_stylesheet_directory_uri() . '/assets/js/bootstrap-datepicker.js', ['jquery'] );
wp_enqueue_script('datepicker-fr', get_stylesheet_directory_uri() . '/assets/locales/bootstrap-datepicker.fr.min.js');

// summernote
wp_enqueue_style('summernote', '//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.css');
wp_enqueue_script('summernote', '//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.js', ['jquery']);

if ($_POST){
    if (isset( $_POST['sav-nonce'] ) || wp_verify_nonce( $_POST['sav-nonce'], 'freezone-action' )) {
        global $wpdb;
        $insert = \model\fzModel::getInstance()->set_sav($_POST);
        if ($insert) {
            do_action('fz_insert_sav', $wpdb->insert_id);
            wc_add_notice("Demande de service envoyer avec succès", 'success');
        }
    }
}

get_header();
$sidebar_configs = yozi_get_page_layout_configs();

yozi_render_breadcrumbs();
?>

    <script type="text/javascript">
        (function($){
            $(document).ready( function () {
                $(".datepicker").datepicker({
                    language: 'fr',
                    startDate: '+1d',
                    autoclose: true,
                    daysOfWeekDisabled: [0,6]
                });
                $('.summernote').summernote({
                    height: 300,
                    toolbar: [
                        [ 'style', [ 'style' ] ],
                        [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
                        [ 'fontname', [ 'fontname' ] ],
                        [ 'fontsize', [ 'fontsize' ] ],
                        [ 'color', [ 'color' ] ],
                        [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
                        [ 'table',[ 'table' ] ],
                        [ 'view', [ 'undo', 'redo', 'fullscreen', 'help' ] ]
                    ]
                });
            });
        })(jQuery);
    </script>
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
                            <form method="post" class="sav widget" role="form">
                                <div style="margin-bottom: 14px">
                                    <?php
                                    while ( have_posts() ) : the_post();
                                        the_content();
                                    endwhile;
                                    ?>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="form-row form-row-wide">
                                            <label for="reg_mark">Marque <span class="required">*</span></label>
                                            <input type="text" class="input-text" name="mark"
                                                   id="reg_mark"
                                                   value="" />
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class=" form-row form-row-wide">
                                            <label for="reg_type">Type <span class="required">*</span></label>
                                            <input type="text" class="input-text" placeholder="" name="type"
                                                   id="reg_type"
                                                   required
                                                   value="" />
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="form-row form-row-wide">
                                            <label for="reg_reference">Reference </label>
                                            <input type="text" class="input-text" placeholder="" name="reference"
                                                   id="reg_reference"
                                                   value="" />
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="form-row form-row-wide">
                                            <label for="reg_product_number">Numero du produit</label>
                                            <input type="text" placeholder="" class="input-text" name="product_number"
                                                   id="reg_product_number"
                                                   value="" />
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="form-row form-row-wide">
                                            <label for="reg_serial_number">Numéro de serie </label>
                                            <input type="text" placeholder="" class="input-text" name="serial_number"
                                                   id="reg_serial_number"
                                                   value="" />
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="form-row form-row-wide">
                                            <label for="reg_date_appointment">Date rendez-vous <span class="required">*</span></label>
                                            <input type="text" autocomplete="off" class="input-text datepicker"
                                                   onkeydown="event.preventDefault()"
                                                   name="date_appointment" id="reg_date_appointment"
                                                   value="" />
                                        </p>
                                    </div>
                                </div>

                                <p class=" form-row form-row-wide">
                                    <label for="reg_description">Description <span class="required">*</span></label>
                                    <textarea  class="summernote" name="description" id="reg_description"></textarea>
                                </p>

                                <div class="row">
                                    <div class="col-sm-4">
                                        <p class="form-row">
                                            <?php
                                            // @link https://codex.wordpress.org/Function_Reference/wp_nonce_field
                                            wp_nonce_field( 'freezone-sav', 'sav-nonce' );
                                            ?>
                                            <input type="submit" class="btn btn-success btn-block" name="register"
                                                   value="Envoyer" />
                                        </p>
                                    </div>
                                </div>
                            </form>
                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right( $sidebar_configs ); ?>
        </div>
    </section>
<?php get_footer(); ?>