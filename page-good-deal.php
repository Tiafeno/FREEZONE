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
 *Template Name: Good Deal Form
 */

wp_enqueue_style('select2', get_stylesheet_directory_uri() . '/assets/css/select2.css');
wp_enqueue_script('select2', get_stylesheet_directory_uri() . '/assets/js/select2.full.js', ['jquery']);

acf_enqueue_uploader();
acf_form_head();

get_header();
$sidebar_configs = yozi_get_page_layout_configs();

yozi_render_breadcrumbs();
?>
    <style type="text/css">
        .acf-field select {
            height: 40px;
            padding-left: 15px;
        }

        .acf-field input[type="text"],
        .acf-field input[type="password"],
        .acf-field input[type="number"],
        .acf-field input[type="search"],
        .acf-field input[type="email"],
        .acf-field input[type="url"],
        .acf-field textarea,
        .acf-field select {
            height: 40px;
        }

        #wp-acf-editor-47-media-buttons {
            display: none;
        }
    </style>
    <script type="text/javascript">
        (function ($) {
            $(document).ready(function () {

            });
        })(jQuery);
    </script>
    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container'); ?> inner">
        <?php wc_print_notices(); ?>
        <?php yozi_before_content($sidebar_configs); ?>
        <div class="row">
            <?php yozi_display_sidebar_left($sidebar_configs); ?>
            <div id="main-content" class="main-page <?php echo esc_attr($sidebar_configs['main']['class']); ?>">
                <main id="main" class="site-main clearfix" role="main">

                    <?php
                    if (!is_user_logged_in()) {
                        wc_get_template('woocommerce/myaccount/form-login.php');
                    } else {
                        acf_form(array(
                            'post_id'		=> 'new_post',
                            'new_post'		=> array(
                                'post_type'		=> 'good-deal',
                                'post_status'   => 'publish'
                            ),
                            'submit_value'		=> 'Validé'
                        ));

                        ?>
                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right($sidebar_configs); ?>
        </div>
    </section>
<?php get_footer(); ?>