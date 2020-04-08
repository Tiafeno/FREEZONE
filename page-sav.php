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

$User = wp_get_current_user();
$client_type = null;
wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);
// https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js
wp_enqueue_script('vue', "https://cdn.jsdelivr.net/npm/vue/dist/vue.js", ['jquery']);
wp_localize_script('vue', 'rest_api', [
    'rest_url' => esc_url_raw(rest_url()),
    'nonce' => wp_create_nonce('wp_rest'),
    'user_id' => $User->ID,
    'admin_url' =>  admin_url('admin-ajax.php'),
    'redirect_url' => wc_get_page_permalink('myaccount')
]);
acf_form_head();
get_header();
// Ajouter dans la balise <body>
acf_enqueue_uploader();
$sidebar_configs = yozi_get_page_layout_configs();
$updated = isset($_GET['updated']) ? boolval($_GET['updated']) : false;
yozi_render_breadcrumbs();
?>
    <style type="text/css">
        #guarentee_product, #product_provider, #delais_garentee {
            height: 40px;
            padding-left: 15px;
            width: 100%;
        }

        #app-form-sav input,
        #app-form-sav textarea {
            border-radius: 0px !important;
            border-color: #aaaaaa;
        }

        .error {
            color: red;
        }

        .form-check-inline {
            display: inline-block;
            margin-right: 15px;
        }

        .swal2-content {
            font-size: 18px;
        }

    </style>
    <script type="text/javascript">
        (function ($) {
            $(document).ready(() => {
                /**
                 * Les champs date_purchase, bill & serial_number
                 * s'affichent si seulement la valeur du status est égale à "Sous garantie" et fournisseur "Freezone"
                 */
                new Vue({
                    el: '#app-form-sav',
                    data: {
                        message: null,
                        loading: false,
                        delais_range: _.range(1, 13, 1),
                        errors: [],
                        product: '', // Produit
                        mark: '', // Marque
                        guarentee_product: 1, // Statut du produit (e.g: 1: Sous garantie, 2: Hors garantie)
                        product_provider: 1, // Fournisseur du produit (e.g: 1: freezone, 2: Autre fournisseur)
                        date_purchase: '', // Date d'achat
                        bill: '', // Numéro de la facture
                        serial_number: '', // Numéro de serie,
                        description: '', // Identification de la demande
                        delais_garentee: 1, // Delais de la garantie (1 mois par default)
                        accessorie: [0],
                        other_accessories_desc: '',
                    },
                    methods: {
                        statusHandler: function (evt) {
                            if (this.guarentee_product == 1) { this.delais_garentee = 1; }
                            // Hors garantie
                            if (this.guarentee_product == 2) {
                                this.message = "Votre demande sera étudiée sous 24 heures jours ouvrables, " +
                                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier car " +
                                    "nous ne réparons pas chez le client. Une fois le matériel en notre possession le " +
                                    "technicien donnera son diagnostic qui vous sera envoyé par email. <br>Soit vous acceptez " +
                                    "que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " +
                                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter " +
                                    "du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
                            }
                            // Sous garantie & freezone
                            if (this.guarentee_product == 1 && this.product_provider == 1) {
                                this.message = "Votre demande sera étudiée sous 24 heures jours ouvrables, " +
                                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " +
                                    "car nous ne réparons pas chez le client.";
                            }
                            // Sous garantie & autre fournisseur
                            if (this.guarentee_product == 1 && this.product_provider == 2) {
                                this.message = "Votre demande sera traitée sous 24 heures jours ouvrables, " +
                                    "Pour votre information sachez qu’en nous confiant un produit qui est sous garantie " +
                                    "chez un autre revendeur vous risquez de perdre votre garantie chez ce revendeur. <br>" +
                                    "Nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " +
                                    "car nous ne réparons pas chez le client. <br> Une fois le matériel en notre possession " +
                                    "le technicien donnera son diagnostic qui vous sera envoyé par email. <br> Soit vous acceptez" +
                                    " que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " +
                                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter" +
                                    " du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
                            }
                        },
                        checkForm: function(e) {
                            e.preventDefault();
                            var self = this;
                            this.errors = [];
                            if (_.isEmpty(this.product)) {
                                this.errors.push('Le champ produit est obligatoire');
                            }
                            if (_.isEmpty(this.mark)) {
                                this.errors.push('Le champ marque est obligatoire');
                            }
                            if (_.isEmpty(this.description)) {
                                this.errors.push('Veuillez decrire le probléme de votre matériel pour mieux diagnostique votre appareil');
                            }
                            // Sous garentie et freezone
                            if (this.guarentee_product == 1 && this.product_provider == 1) {
                                if (_.isEmpty(this.date_purchase)) {
                                    this.errors.push('La date est obligatoire');
                                }

                                if (_.isEmpty(this.bill)) {
                                    this.errors.push('Le numéro de facture est obligatoire');
                                }

                                if (_.isEqual(this.serial_number, '')) {
                                    this.errors.push('Le numéro de série est obligatoire');
                                }
                            }
                            // Sous garentie et autres fournisseurs
                            if (this.guarentee_product == 1 && this.product_provider == 1) {
                                if (_.isEqual(this.delais_garentee, '')) {
                                    this.errors.push('Veuillez vérifier le délais de garantie');
                                }
                            }
                            if (this.errors.length) {
                                window.scrollTo(0, 0);
                                return true;
                            }
                            this.loading = true;
                            this.statusHandler();
                            $('button[type="submit"]').text('Chargement ...');
                            $.ajax({
                                method: "POST",
                                url: rest_api.rest_url + 'wp/v2/fz_sav',
                                data: {
                                    title: this.product,
                                    content: this.description,
                                    status: 'publish',
                                    bill: this.bill,
                                    date_purchase: this.date_purchase,
                                    description: this.description,
                                    mark: this.mark,
                                    product: this.product,
                                    product_provider: this.product_provider,
                                    guarentee_product: this.guarentee_product,
                                    serial_number: this.serial_number,
                                    garentee: this.delais_garentee,
                                    accessorie: this.accessorie,
                                    other_accessories_desc: this.other_accessories_desc,
                                    customer: rest_api.user_id
                                },
                                beforeSend: function (xhr) {
                                    xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                                },
                                success: function(newSav) {
                                    var savId = newSav.id;
                                    $.ajax({
                                        method: "POST",
                                        url: rest_api.admin_url ,
                                        data: {
                                            action: 'new_sav',
                                            post_id: savId
                                        },
                                        success: function(resp) {
                                            $('button[type="submit"]').text('Validé');
                                            self.loading = false;
                                            Swal.fire({
                                                title: 'Cher client',
                                                html: self.message,
                                                type: 'info',
                                                showCancelButton: false,
                                                width: "60rem"
                                            }).then(result => {
                                                if (result.value) {
                                                    window.location.href = rest_api.redirect_url;
                                                }
                                            });
                                        },
                                        error : function(jqXHR, status, errorThrown) {
                                            $('button[type="submit"]').text('Validé');
                                        }
                                    });
                                }
                            });
                        }
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
                    if ($updated) {
                        wc_print_notice("Votre demande a bien été prise en compte par notre équipe, vous serez 
                        bientôt contacte pour la suite. Merci", 'success');
                    }
                    if (!is_user_logged_in()) {
                        wc_get_template('woocommerce/myaccount/form-login.php');
                    } else {
                        ?>
                        <form id="app-form-sav"
                                @submit="checkForm"
                                action=""
                                method="post">

                            <p v-if="errors.length">
                                <b>Veuillez corriger les erreurs suivantes:</b>
                            <ul style="margin-bottom: 20px;font-size: 14px">
                                <li class="error" v-for="error in errors">{{ error }}</li>
                            </ul>
                            </p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="product">Nom du produit</label>
                                        <input type="text" v-model="product"
                                               class=" form-control tt-input"
                                               id="product"
                                               v-bind:required="true"
                                               placeholder="Produit">
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="serial_number">Numéro de série (S/N)</label>
                                        <input type="text" v-model="serial_number" class="form-control"
                                               id="serial_number"
                                               placeholder="Veuillez saisir le numéro de série">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="mark">Marque et Modele</label>
                                        <input type="text" v-model="mark" class="form-control" id="mark"
                                               placeholder="Marque">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="mark">Fournisseur du produit</label>
                                        <select name="product_provider" v-model="product_provider" id="product_provider"
                                                v-on:change="statusHandler">
                                            <option value="1">Freezone</option>
                                            <option value="2">Autre fournisseur</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="guarentee_product">Garantie du produit</label>
                                        <select name="guarentee_product" v-model="guarentee_product"
                                                id="guarentee_product" v-on:change="statusHandler" class="">
                                            <option value="">Aucun</option>
                                            <option value="1">Sous garantie</option>
                                            <option value="2">Hors garantie</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4" v-if="guarentee_product == 1">
                                    <div class="form-group">
                                        <label for="delais_garentee">Délais de garantie</label>
                                        <select name="delais_garentee" v-model="delais_garentee" id="delais_garentee">
                                            <option value="">Aucun</option>
                                            <option :value="value" v-for="(value, index) in delais_range">
                                                {{ value }} mois
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4" v-if="guarentee_product == 1 && product_provider == 1">
                                    <div class="form-group">
                                        <label for="date_purchase">Date d'achat</label>
                                        <input type="date" v-model="date_purchase" v-bind:required="product_provider == 1"
                                               id="date_purchase" style="display: block; line-height: 1.85; padding: 4px; width: 100%">
                                    </div>
                                </div>
                                <div class="col-sm-4" v-if="guarentee_product == 1 && product_provider == 1">
                                    <div class="form-group">
                                        <label for="bill">Numéro de la facture</label>
                                        <input type="text" v-model="bill" v-bind:required="product_provider == 1"
                                               class="form-control" id="bill" placeholder="Veuillez saisir le numéro de la facture">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-8">
                                    <p style="font-size: 14px; font-weight: 700; margin-bottom: 2px;">Accessoires:</p>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="1" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Câble d'alimentation</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="2" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Câble USB</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="3" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Toner réf</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="4" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Cartouche réf</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="5" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Adapter</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" v-model="accessorie" v-bind:value="0" name="accessorie">
                                        <span class="form-check-label" for="inlineCheckbox2">Autres accessoires</span>
                                    </div>
                                </div>
                                <div class="col-sm-6" v-if="_.indexOf(accessorie, 0) > -1">
                                    <div class="form-group">
                                        <label>Autres accessoires:</label>
                                        <textarea v-model="other_accessories_desc" v-bind:required="accessorie == 0" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-8 mt-4">
                                    <div class="form-group">
                                        <label for="description">Probème rencontré ou panne</label>
                                        <textarea v-model="description" class="form-control"
                                                  id="description"></textarea>
                                    </div>
                                </div>
                            </div>

                            <p v-if="message" v-html="message"></p>
                            <div class="row">
                                <div class="col-sm-2">
                                    <button type="submit" class="btn btn-primary" :disabled="loading">
                                        Validé
                                    </button>
                                </div>
                            </div>


                        </form>
                    <?php } ?>
                </main><!-- .site-main -->
            </div><!-- .content-area -->
            <?php yozi_display_sidebar_right($sidebar_configs); ?>
        </div>
    </section>
<?php get_footer(); ?>