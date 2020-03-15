<?php
namespace classes;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

class fzGoodDeal
{
    public $ID;
    public $price = 0; // post meta: gd_price
    public $gallery = []; // post meta: gd_gallery
    public $post_author_annonce = 0; // post meta: gd_author
    public $categorie = null;

    public function __construct ($post_id) {
        $post = \WP_Post::get_instance($post_id);
        foreach (get_object_vars($post) as $key => $value)
            $this->$key = $value;

        $this->price  = get_post_meta($post_id, 'gd_price', true);
        $this->gallery = get_post_meta($post_id, 'gd_gallery', true); // return string
        if (!is_array($this->gallery)) {
            $this->gallery = json_decode($this->gallery);
        }
        $this->post_author_annonce = (int) get_post_meta($post_id, 'gd_author', true);
        $this->categorie = wp_get_post_terms( $this->ID, 'product_cat', [] );
    }

    public function set_title($title) {
        if (empty($title)) {
            throw new \Exception("Le champ 'Titre' ne peut pas etre vide");
            return false;
        }
        $postarr = ['ID' => $this->ID, 'post_title' => $title];
        $postupdate = wp_update_post($postarr, true);
        if (is_wp_error($postupdate) || 0 === $postupdate) {
            throw new \Exception($postupdate->get_error_message());
            return false;
        }
        return true;
    }

    public function set_description($text) {
        $postarr = ['ID' => $this->ID, 'post_content' => $text];
        $postupdate = wp_update_post($postarr, true);
        if (is_wp_error($postupdate) || 0 === $postupdate) {
            throw new \Exception($postupdate->get_error_message());
            return false;
        }
        return true;
    }

    public function set_price($price) {
        return update_post_meta($this->ID, "gd_price", $price);
    }

    public function set_categorie($ctg_id) {
        if (!is_numeric($ctg_id)) {
            throw new \Exception("La categorie est de type numeric");
            return false;
        }
        return wp_set_post_terms($this->ID, [$ctg_id], 'product_cat');
    }

    public function set_gallery($gallery) {
        if (!is_array($gallery)) {
            throw new \Exceptio("Le parametre est de type tableau.");
            return false;
        }
        return update_post_meta($this->ID, 'gd_gallery', json_encode($gallery));
    }

    public function get_author() {
        return new \WP_User($this->post_author_annonce);
    }

    public function get_categorie () {
        if (isset($this->categorie)) {
            return is_array($this->categorie) ? $this->categorie[0] : null;
        }
        return null;
    }

    public function get_categorie_id() {
        $categorie = $this->get_categorie();
        return is_null($categorie) ? 0 : $categorie->term_id;
    }

    public function get_gallery_thumbnail() {
        $attachment = [];
        if (!is_array($this->gallery)) return [];
        foreach ($this->gallery as $gallery)
            $attachment[] = wp_get_attachment_url( intval($gallery) );
        return $attachment;
    }

}

add_action('init', function () {
    register_post_type('good-deal', [
        'label' => "Les bonnes affaires",
        'labels' => [
            'name' => "Les bonnes affaires",
            'singular_name' => "Bonne affaire",
            'add_new' => 'Ajouter',
            'add_new_item' => "Ajouter un nouveau",
            'edit_item' => 'Modifier',
            'view_item' => 'Voir',
            'search_items' => "Trouver",
            'all_items' => "Tous les bonnes affaires",
            'not_found' => "Aucun",
            'not_found_in_trash' => "La corbeille est vide"
        ],
        'public' => true,
        'hierarchical'  => false,
        'menu_position' => null,
        'show_ui' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'bonne-affaires'],
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'menu_icon' => 'dashicons-archive',
        'supports'  => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'query_var'    => true
    ]);
    // Register taxonomy for this post type in fzPTFreezone.php
}, 10);