<?php
get_header();
$size = "woocommerce_thumbnail";
$attr = [];

 do_action( 'yozi_woo_template_main_before' ); 
?>
<section id="main-container" class="main-container container-fluid">
    <div class="products products-grid">

        <?php if (have_posts()) { ?>
        <div class="row">
            <?php while ( have_posts() ) : the_post(); 
                $featured_image_id = get_post_thumbnail_id(get_the_ID());
                $image = wp_get_attachment_image( $featured_image_id, $size, false, $attr );
                $categories = wp_get_post_terms( get_the_ID(), 'product_cat', [] );
                $categories = array_map(function ($categorie) { return $categorie->name; }, $categories);
            ?>
            <div class="col-md-cl-5 col-md-3 col-sm-4 col-xs-6  product type-product status-publish first product_cat-computer
             product_tag-accessories product_tag-computer product_tag-pc has-post-thumbnail  product-type-simple">
                <div class="product-block grid" data-product-id="775">
                    <div class="grid-inner">

                        <div class="block-title">
                            <div class="product-cats uppercase"><?= implode(', ', $categories) ?> </div>
                            <h3 class="name"><a
                                    href="<?= get_the_permalink() ?>"><?= the_title() ?></a></h3>
                        </div>

                        <div class="block-inner">
                            <figure class="image">


                                <a title="Desktop Supply Charger 5V 2A US Sliver"
                                    href="<?= get_the_permalink() ?>"
                                    class="product-image image-loaded">
                                    <?= $image ?> 
                                </a>


                            </figure>
                            <div class="quick-view">
                                <a href="<?= get_the_permalink() ?>" class=" btn btn-dark btn-block radius-3x" >
                                    Voir l'annonce </a>
                            </div>
                        </div>
                        <div class="metas clearfix">
                            <!-- Afficher ici les prix -->
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