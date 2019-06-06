<?php

get_header();

$sidebar_configs = yozi_get_blog_layout_configs();
yozi_render_breadcrumbs();
?>

    <section id="main-container" class="main-content <?php echo apply_filters( 'yozi_blog_content_class', 'container' ); ?> inner">
        <?php yozi_before_content( $sidebar_configs ); ?>
        <div class="row">
            <div id="main-content" class="col-xs-12 ">
                <div id="primary" class="content-area">
                    <div id="content" class="site-content detail-post" role="main">
                        <?php
                        // Start the Loop.
                        while ( have_posts() ) : the_post();
                            $gd = new \classes\fzGoodDeal(get_the_ID());
                            print_r($gd);

                            /*
                             * Include the post format-specific template for the content. If you want to
                             * use this in a child theme, then include a file called called content-___.php
                             * (where ___ is the post format) and that will be used instead.
                             */
                            get_template_part( 'template-posts/single/inner' );

                            get_template_part( 'template-parts/author-bio' );
                            if ( yozi_get_config('show_blog_releated', false) ): ?>
                                <?php get_template_part( 'template-parts/posts-releated' ); ?>
                            <?php

                            endif;
                            // End the loop.
                        endwhile;
                        ?>
                    </div><!-- #content -->
                </div><!-- #primary -->
            </div>
            <?php yozi_display_sidebar_right( $sidebar_configs ); ?>
        </div>
    </section>
<?php get_footer(); ?>