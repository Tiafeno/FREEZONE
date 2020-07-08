<?php
add_action('init', function () {
    add_shortcode('fz_carousel', 'fn_carousel');

    // Stop all if VC is not enabled
    if (!defined('WPB_VC_VERSION')) {
        return;
    }
    vc_map(
        [
            'name' => 'owl Carousel',
            'base' => 'fz_carousel',
            'description' => 'Afficher un carousel',
            'category' => 'Freezone',
            'params' => [
                [
                    'type' => 'textfield',
                    'holder' => 'h3',
                    'class' => 'vc-ij-title',
                    'heading' => 'Ajouter un titre',
                    'param_name' => 'title',
                    'value' => '',
                    'admin_label' => false,
                    'weight' => 0
                ],
            ]
        ]
    );
});

function fn_carousel ($attrs, $content = '')
{
    extract(shortcode_atts(['ids' => [], 'title' => 'Home slider'], $attrs), EXTR_OVERWRITE);

    /** @var array $ids */
    /** @var string $title */
    $attach_ids = empty($ids) ? get_option('medias_carousel') : $ids;
    if (false === $attach_ids || empty($attach_ids) || is_null($attach_ids)) {
        $attach_ids = [];
    };

    $html = '<div class="owl-carousel owl-theme" >';
    if ($attach_ids) {
        foreach ( $attach_ids as $attachment_id ) {
            $full_size_image = wp_get_attachment_image_src($attachment_id, 'full');
            $thumbnail = wp_get_attachment_image_src($attachment_id, 'shop_thumbnail');
            if (!$thumbnail) continue;

            $html .= '<div class="woocommerce-product-gallery__image">';
            $html .= wp_get_attachment_image($attachment_id, 'shop_single', false);
            $html .= '</div>';
        }
    }
    $html .= '</div>';

    wp_enqueue_style('owlCarousel');
    wp_enqueue_style('owlCarousel-green');
    wp_enqueue_script('owlCarousel');
    wp_enqueue_script('carousel', get_stylesheet_directory_uri() . '/assets/js/shortcodes/carousel.js', ['owlCarousel'], '1.0.1', true);

    return $html;

}