<?php


get_header();

wp_enqueue_style('owlCarousel');
wp_enqueue_style('owlCarousel-green');
wp_enqueue_script('owlCarousel');

$sidebar_configs = yozi_get_blog_layout_configs();
?>

<?php do_action('yozi_woo_template_main_before'); ?>
<script type="text/javascript">
    (function ($) {
        $(document).ready(function () {
            $('.owl-carousel').owlCarousel({
                items: 1,
                animateOut: 'fadeOut',
                loop: true,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: false,
                pagination: true,
                dots: true,
                margin: 10,
                autoHeight: true,
            });

            var formatter = new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'MGA',
                minimumFractionDigits: 0
            });

            $('.unit-cost').each((index, element) => {
                var cost = $(element).text();
                cost = parseFloat(cost);
                $(element).text(formatter.format(cost));
            });
        });
    })(jQuery);
</script>
<section id="main-container"
         class="layout-detail-product <?php echo apply_filters('yozi_woocommerce_content_class', 'container'); ?>">
    <?php yozi_before_content($sidebar_configs); ?>
    <div class="row">
        <div id="main-content" class="col-xs-12 archive-shop">
            <div id="primary" class="content-area">
                <div id="content" class="site-content detail-post" role="main">
                    <?php
                    do_action('woocommerce_before_single_product');

                    if (post_password_required()) {
                        echo get_the_password_form();
                        return;
                    }
                    $layout = yozi_get_config('product_single_version', 'v2');

                    ?>
                    <div id="product-<?php the_ID(); ?>" <?php wc_product_class('details-product layout-' . $layout); ?>>

                        <?php
                        // Start the Loop.
                        while (have_posts()) : the_post();
                            $gd = new \classes\fzGoodDeal(get_the_ID());
                            $author = $gd->get_author();

                            $columns = apply_filters('woocommerce_product_thumbnails_columns', 4);
                            $thumbnail_size = apply_filters('woocommerce_product_thumbnails_large_size', 'full');
                            $post_thumbnail_id = get_post_thumbnail_id(get_the_ID());
                            $full_size_image = wp_get_attachment_image_src($post_thumbnail_id, $thumbnail_size);
                            $placeholder = has_post_thumbnail() ? 'with-images' : 'without-images';
                            $wrapper_classes = apply_filters('woocommerce_single_product_image_gallery_classes', [
                                'apus-woocommerce-product-gallery',
                                'woocommerce-product-gallery--' . $placeholder,
                                'woocommerce-product-gallery--columns-' . absint($columns),
                                'images',
                            ]);

                            $thumbs_pos = yozi_get_config('product_thumbs_position', 'thumbnails-left');
                            $number_product_thumbs = yozi_get_config('number_product_thumbs', 4);

                            ?>
                            <div class="row top-content">
                                <div class="col-lg-5 col-md-4 col-xs-12">
                                    <div class="image-mains clearfix <?php echo esc_attr($thumbs_pos); ?>">

                                        <div class="">
                                            <div class="owl-carousel owl-theme">
                                                <?php

                                                $attributes = [
                                                    'title' => get_post_field('post_title', $post_thumbnail_id),
                                                    'data-caption' => get_post_field('post_excerpt', $post_thumbnail_id),
                                                    'data-src' => $full_size_image[0],
                                                    'data-large_image' => $full_size_image[0],
                                                    'data-large_image_width' => $full_size_image[1],
                                                    'data-large_image_height' => $full_size_image[2],
                                                ];

                                                if (has_post_thumbnail()) {
                                                    $html = '<div>';
                                                    $html .= get_the_post_thumbnail(get_the_ID(), 'shop_single', $attributes); //shop_thumbnail
                                                    $html .= '</div>';

                                                    echo $html;
                                                }


                                                $attachment_ids = $gd->gallery;

                                                if ($attachment_ids) {
                                                    foreach ( $attachment_ids as $attachment_id ) {
                                                        $full_size_image = wp_get_attachment_image_src($attachment_id, 'full');
                                                        $thumbnail = wp_get_attachment_image_src($attachment_id, 'shop_thumbnail');

                                                        $html = '<div class="woocommerce-product-gallery__image">';
                                                        $html .= wp_get_attachment_image($attachment_id, 'shop_single', false);
                                                        $html .= '</div>';
                                                    }

                                                    echo $html;
                                                }

                                                ?>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                                <div class="col-lg-7 col-md-8 col-xs-12">
                                    <div class="information">
                                        <div class="row flex-top">
                                            <div class="summary-left col-sm-8">
                                                <div class="summary entry-summary">
                                                    <!--  -->
                                                    <h1 class="product_title entry-title"><?= the_title() ?></h1>
                                                    <div class="woocommerce-product-details__short-description-wrapper">
                                                        <div class="woocommerce-product-details__short-description ">
                                                            <?= the_content() ?>
                                                        </div>
                                                    </div>

                                                    <div class="product_meta">
                                                            <span class="posted_in">Catégorie&nbsp;:
                                                                <?php foreach ( $gd->categorie as $ctg ): ?>
                                                                    <a href="<?= get_term_link($ctg->term_id, 'product_cat') ?>"
                                                                       rel="tag"><?= $ctg->name ?></a>
                                                                <?php endforeach; ?>
                                                                <?php if (empty($gd->categorie)) echo "Aucun"; ?>
                                                            </span>
                                                    </div>

                                                </div>
                                            </div>
                                            <div class="summary-right col-sm-4">
                                                <div class="summary entry-summary">
                                                    <!--  -->
                                                    <p class="price unit-cost"
                                                       style="color: #e23e1d;"><?= $gd->price ?></p>
                                                    <p>Annonce publier
                                                        par <?= $author->last_name ?> <?= $author->first_name ?></p>
                                                    <div class="apus-discounts">
                                                        <h3 class="title"><span class="icon"><i
                                                                        class="ti-email"></i></span> Contact</h3>
                                                        <div class="productinfo-show-discounts">
                                                            <ul>
                                                                <li><?= $author->user_email ?></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div><!-- .summary -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endwhile;
                        ?>
                    </div>

                    <?php do_action('woocommerce_after_single_product'); ?>


                </div><!-- #content -->
            </div><!-- #primary -->
        </div>
        <?php yozi_display_sidebar_right($sidebar_configs); ?>
    </div>
</section>
<?php get_footer(); ?>


