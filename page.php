<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Yozi
 */
/*
 *Template Name: Page Default
 */


get_header();
$sidebar_configs = yozi_get_page_layout_configs();

yozi_render_breadcrumbs();

wc_print_notices();
?>

    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container');?> inner">
        <?php yozi_before_content( $sidebar_configs ); ?>
        <div class="row">
            <?php yozi_display_sidebar_left( $sidebar_configs ); ?>
            <div id="main-content" class="main-page <?php echo esc_attr($sidebar_configs['main']['class']); ?>">
                <main id="main" class="site-main clearfix" role="main">

                    <?php
                    // Start the loop.
                    while ( have_posts() ) : the_post();

                        // Include the page content template.
                        the_content();

                        // If comments are open or we have at least one comment, load up the comment template.
                        if ( comments_open() || get_comments_number() ) :
                            comments_template();
                        endif;

                        // End the loop.
                    endwhile;
                    ?>
                </main><!-- .site-main -->
                <?php
                wp_link_pages( array(
                    'before'      => '<div class="page-links"><span class="page-links-title">' . esc_html__( 'Pages:', 'yozi' ) . '</span>',
                    'after'       => '</div>',
                    'link_before' => '<span>',
                    'link_after'  => '</span>',
                    'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'yozi' ) . ' </span>%',
                    'separator'   => '',
                ) );
                ?>
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right( $sidebar_configs ); ?>
        </div>
    </section>
<?php get_footer(); ?>