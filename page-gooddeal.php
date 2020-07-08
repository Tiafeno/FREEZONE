<?php
/*
 * Template Name: Page Good Deal
 */

$User = wp_get_current_user();
wp_enqueue_media();
wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);

// https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js
wp_enqueue_script('vue', "https://cdn.jsdelivr.net/npm/vue/dist/vue.js", ['jquery', 'underscore']);
wp_localize_script('vue', 'rest_api', [
    'rest_url' => esc_url_raw(rest_url()),
    'nonce' => wp_create_nonce('wp_rest'),
    'user_id' => $User->ID,
    'admin_url' => admin_url('admin-ajax.php'),
    'redirect_url' => wc_get_page_permalink('myaccount')
]);

get_header();

$sidebar_configs = yozi_get_page_layout_configs();
yozi_render_breadcrumbs();
?>

    <style type="text/css">

        #app-form-gd input,
        #app-form-gd textarea {
            border-radius: 0px !important;
            border-color: #aaaaaa;
        }

        .error {
            color: red;
        }

        .swal2-content {
            font-size: 18px;
        }

    </style>
    <script type="text/javascript">
        (function ($) {
            $(document).ready(function() {
                new Vue({
                    el: '#app-form-gd',
                    data: {
                        errors: [],
                        categories: [],
                        loading: false,
                        photos: [], // [{ index: 1, file: ...}]
                        title: '',
                        categorie: '',
                        price: 0,
                        description: ''
                    },
                    methods: {
                        handleFileChange: function (event, index) {
                            const files = event.target.files;
                            let result = _.find(this.photos, function(photo) {
                                if (_.isObject(photo)) {
                                    return photo.index === index;
                                } else {
                                    return false;
                                }
                            });
                            if (_.isUndefined(result)) {
                                this.photos.push({index: index, file: files[0]});
                            } else {
                                this.photos = _.map(this.photos, function(photo) {
                                    if (photo.index === index) {
                                        photo.file = files[0];
                                    }
                                    return photo;
                                });
                            }
                        },
                        fnAjax: function (data) {
                            return $.ajax({
                                url: rest_api.rest_url + 'wp/v2/media/',
                                method: 'POST',
                                processData: false,
                                contentType: false,
                                beforeSend: function (xhr) {
                                    xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                                },
                                data: data,
                            });
                        },
                        fnGetCategories() {
                            return $.ajax({
                                url: rest_api.rest_url + 'wp/v2/product_cat/',
                                method: 'GET',
                                beforeSend: function (xhr) {
                                    xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                                },
                                data: {
                                    per_page: 100
                                },
                            });
                        },
                        submitForm: function (e) {
                            var self = this;
                            e.preventDefault();
                            this.errors = [];
                            if (_.isEmpty(this.title)) {
                                this.errors.push('Le titre est obligatoire');
                            }
                            if (_.isEmpty(this.description)) {
                                this.errors.push('Veuillez decrire votre matériel pour plus de chance d\'être vendue');
                            }
                            if (this.errors.length) {
                                window.scrollTo(0, 0);
                                return true;
                            }

                            this.loading = true;
                            $('button[type="submit"]').text('Chargement ...');
                            var upload = {};
                            for (var item of this.photos) {
                                var form = new FormData();
                                form.append('file', item.file);
                                form.append('title', this.title);
                                upload['f' + item.index] = this.fnAjax(form);
                            }
                            $.when(upload).done(function(results) {
                                async function createGd(responses) {
                                    var ids = [];
                                    for (var key of Object.keys(responses)) {
                                        var gDeal = await responses[key];
                                        ids.push(gDeal.id);
                                    }
                                    $.ajax({
                                        method: "POST",
                                        url: rest_api.rest_url + 'wp/v2/good-deal',
                                        data: {
                                            title: self.title,
                                            content: self.description,
                                            status: 'pending', // Mettre l'annonce en attente par default
                                            featured_media: _.isEmpty(ids) ? 0 : ids[0].toString(),
                                            categorie: self.categorie,
                                            meta: {
                                                gd_gallery: JSON.stringify(ids),
                                                gd_price: self.price,
                                                gd_author: rest_api.user_id
                                            }
                                        },
                                        beforeSend: function (xhr) {
                                            xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                                        },
                                        success: newGd => {
                                            var gdId = newGd.id;
                                            $('button[type="submit"]').text('Envoyer');
                                            this.loading = false;
                                            Swal.fire({
                                                title: 'Cher client',
                                                html: 'Votre annonce a bien été envoyée. Merci',
                                                type: 'info',
                                                showCancelButton: false,
                                                width: "60rem"
                                            }).then(result => {
                                                if (result.value) {
                                                    window.location.href = rest_api.redirect_url;
                                                }
                                            });
                                        },
                                        error: function (err) {
                                            console.log('Error:', err);
                                        }
                                    });
                                }

                                createGd(results)
                            })
                                .fail(resp => {
                                    Swal.fire("Error", "Une erreur c'est produit pendant l'envoie des images", 'warning');
                                });


                        }
                    },
                    created: function () {
                        // Component ready!
                        this.fnGetCategories().then(resp => {
                            this.categories = _.clone(resp);
                        })
                    }
                });
            });
        })(jQuery);
    </script>
    <section id="main-container" class="<?php echo apply_filters('yozi_page_content_class', 'container'); ?> inner">

        <?php wc_print_notices(); ?>
        <?php yozi_before_content($sidebar_configs); ?>
        <div class="row">
            <?php yozi_display_sidebar_left($sidebar_configs); ?>
            <div id="main-content" class="main-page <?php echo esc_attr($sidebar_configs['main']['class']); ?>">
                <main id="main" class="site-main clearfix" role="main" style="margin-bottom: 15px">

                    <?php
                    if (!is_user_logged_in()) {
                        wc_get_template('woocommerce/myaccount/form-login.php');
                    } else {
                        ?>
                        <div style="display: flex;">
                            <div style="margin: auto; min-width: 650px">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <form
                                                id="app-form-gd"
                                                @submit="submitForm"
                                                action=""
                                                method="post">
                                            <p>Publiez votre annonce gratuitement en 2 minutes !</p>

                                            <p v-if="errors.length" style="color: red">
                                                <b>Veuillez corriger les erreurs suivantes:</b>
                                            <ul style="margin-bottom: 20px;font-size: 12px">
                                                <li class="error" v-for="error in errors">{{ error }}</li>
                                            </ul>
                                            </p>


                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label for="title">Titre <span
                                                                    style="color:red">*</span></label>
                                                        <input type="text" autocomplete="off" :required="true"
                                                               v-model="title" class="form-control" id="title"
                                                               placeholder="Titre de votre annonce">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label for="categorie">Categorie <span
                                                                    style="color:red">*</span></label>
                                                        <select name="categorie" id="categorie" :required="true"
                                                                v-model="categorie" class="form-control radius-0">
                                                            <option value="">Selectionner une categorie</option>
                                                            <option :value="ctg.id" v-for="(ctg, index) in categories">
                                                                {{ctg.name}}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label for="price">Prix de vente (AR) <span
                                                                    style="color:red">*</span></label>
                                                        <input min="0" type="number" autocomplete="off" v-model="price"
                                                               class="form-control" id="price"
                                                               placeholder="Prix">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label for="featured badge">Joindre des photos</label>
                                                        <input id="featured" type="file"
                                                               @change="handleFileChange($event, 1)">
                                                    </div>

                                                    <div class="form-group">
                                                        <input type="file"
                                                               @change="handleFileChange($event, 2)">
                                                    </div>

                                                    <div class="form-group">
                                                        <input c type="file"
                                                               @change="handleFileChange($event, 3)">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label for="description">Description <span
                                                                    style="color:red">*</span></label>
                                                        <textarea v-model="description" class="form-control" rows="8"
                                                                  id="description"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-sm-2">
                                                    <button type="submit" class="btn btn-primary" :disabled="loading">
                                                        Envoyer
                                                    </button>
                                                </div>
                                            </div>


                                        </form>
                                    </div>
                                    <div class="col-sm-6">
                                        <p style="text-align: center; font-weight: bold; padding: 5px 10px; background-color: #59618e; color: white">
                                            3 règles pour publier votre annonce
                                        </p>
                                        <ul>
                                            <li>N'écrivez pas le prix dans le titre</li>
                                            <li>Ne publiez pas la même annonce plusieurs fois</li>
                                            <li>Ne vendez pas d'objets ou de services illégaux</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>


                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right($sidebar_configs); ?>
        </div>
    </section>
<?php get_footer(); ?>