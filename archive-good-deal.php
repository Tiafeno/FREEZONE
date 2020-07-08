<?php
get_header();
$size = "woocommerce_thumbnail";
$attr = [];

do_action('yozi_woo_template_main_before');
?>
    <section id="main-container" class="main-container container-fluid">
        <div class="products products-grid">
            <style type="text/css">
            .metas .meta-meta-price > span {
                color: #b11339;
                font-size: 20px;
            }
            </style>

            <?php if (have_posts()) { ?>
                <div class="row">
                    <?php while (have_posts()) : the_post();
                        $featured_image_id = get_post_thumbnail_id(get_the_ID());
                        $image = wp_get_attachment_image($featured_image_id, $size, false, $attr);
                        $price = get_post_meta(get_the_ID(), 'gd_price', true);
                        $categories = wp_get_post_terms(get_the_ID(), 'product_cat', []);
                        $categories = array_map(function ($categorie) { return $categorie->name; }, $categories);
                        ?>
                        <div class="col-md-cl-5 col-md-3 col-sm-4 col-xs-6 product type-product has-post-thumbnail product-type-simple">
                            <div class="product-block grid" data-product-id="<?= get_the_ID() ?>">
                                <div class="grid-inner">
                                    <div class="block-inner">
                                        <figure class="image">
                                            <a title="<?= the_title() ?>" href="<?= get_the_permalink() ?>" class="product-image image-loaded">
                                                <?= $image ?>
                                            </a>
                                        </figure>
                                        <div class="quick-view">
                                            <a href="<?= get_the_permalink() ?>" class=" btn btn-dark btn-block radius-3x"> Voir l'annonce </a>
                                        </div>
                                    </div>
                                    <div class="block-title">
                                        <div style="display: inline-block; text-transform: uppercase">
                                        <?php if (empty($categories)) { echo 'Aucun'; } else echo implode(', ', $categories); ?> 
                                        </div>
                                        <h3 class="name" style="margin: 0"><a href="<?= get_the_permalink() ?>"><?= the_title() ?></a></h3>
                                    </div>
                                    <div class="metas clearfix" style="padding-top: 0">
                                        <!-- Afficher ici les prix -->
                                        <div class="meta-price">
                                            <span class="price" style="padding-right: 5px"><?= $price ?></span>
                                            <span class="currency-country">MGA</span>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                </div>
            <?php } ?>

        </div>
    </section>
<?php
get_footer();