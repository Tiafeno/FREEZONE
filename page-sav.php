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

// récuperer le type du client
if (is_user_logged_in(  )) {
    $client_type = in_array('fz-company', $User->roles) ? 2 : 1;
}

// Ajouter dans la balise <body>
acf_enqueue_uploader();
$sidebar_configs = yozi_get_page_layout_configs();
$updated = isset($_GET['updated']) ? boolval($_GET['updated']) : false;
yozi_render_breadcrumbs();
?>

    <style type="text/css">
        #status_product, #product_provider, #delais_garentee {
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

        .swal2-content {
            font-size: 18px;
        }

    </style>
    <script type="text/javascript">
        (function ($) {
            // Type de client (e.g: 1: Particulier, 2: Entreprise)
            var __CLIENT__ = <?= $client_type ?>;

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
                        status_product: 1, // Statut du produit (e.g: 1: Sous garantie, 2: Hors garantie)
                        product_provider: 1, // Fournisseur du produit (e.g: 1: freezone, 2: Autre fournisseur)
                        date_purchase: '', // Date d'achat
                        bill: '', // Numéro de la facture
                        serial_number: '', // Numéro de serie,
                        description: '', // Identification de la demande
                        delais_garentee: '', // Delais de la garantie

                        // Cette variable controle la visibilité des champs dans le formulaire
                        ck_bill: true,
                        ck_date_purchase: true,
                        ck_serial_number: true,
                        ck_garentee_freezone: true,
                    },
                    methods: {
                        statusHandler: function (evt) {
                            let element = evt.currentTarget;
                            if (this.status_product == 1 && this.product_provider == 1) {
                                this.ck_date_purchase = this.ck_bill = this.ck_serial_number = true;
                            } else {
                                this.ck_date_purchase = this.ck_bill = this.ck_serial_number = false;
                            }
                            this.ck_garentee_freezone = this.status_product == 1 ? true : false;

                            // hors garantie
                            if (this.status_product == 2) {
                                this.message = "Votre demande sera étudiée sous 24 heures jours ouvrables, " +
                                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier car " +
                                    "nous ne réparons pas chez le client. Une fois le matériel en notre possession le " +
                                    "technicien donnera son diagnostic qui vous sera envoyé par email. <br>Soit vous acceptez " +
                                    "que votre matériel soit réparé au cout indiqué et à ce moment-là vous ne paierez pas " +
                                    "le diagnostic soit vous refusez de réparer le matériel vous aurez à vous acquitter " +
                                    "du cout du diagnostic qui varie entre 30.000 et 50.000 HT ";
                            }

                            // Sous garantie & freezone
                            if (this.status_product == 1 && this.product_provider == 1) {
                                this.message = "Votre demande sera étudiée sous 24 heures jours ouvrables, " +
                                    "nous vous demanderons de nous déposer le matériel à réparer dans notre atelier " +
                                    "car nous ne réparons pas chez le client.";
                            }

                            // Sous garantie & autre fournisseur
                            if (this.status_product == 1 && this.product_provider == 2) {
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
                            if (this.status_product == 1 && this.product_provider == 1) {
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

                            // Sous garentie et autre fournisseurs
                            if (this.status_product == 1 && this.product_provider == 1) {
                                if (_.isEqual(this.delais_garentee, '')) {
                                    this.errors.push('Veuillez vérifier le délais de garantie');
                                }
                            }

                            if (this.errors.length) {
                                window.scrollTo(0, 0);
                                return true;
                            }

                            this.loading = true;
                            $('button[type="submit"]').text('Chargement ...');
                            $.ajax({
                                method: "POST",
                                url: rest_api.rest_url + 'wp/v2/fz_sav',
                                data: {
                                    title: this.product,
                                    content: this.description,
                                    status: 'publish',
                                    bill: this.bill,
                                    client: __CLIENT__,
                                    date_purchase: this.date_purchase,
                                    description: this.description,
                                    mark: this.mark,
                                    product: this.product,
                                    product_provider: this.product_provider,
                                    status_product: this.status_product,
                                    serial_number: this.serial_number,
                                    garentee: this.delais_garentee,
                                    auctor: rest_api.user_id
                                },
                                beforeSend: function (xhr) {
                                    xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                                },
                                success: newSav => {
                                    var savId = newSav.id;
                                    $.ajax({
                                        method: "POST",
                                        url: rest_api.admin_url ,
                                        data: {
                                            action: 'new_sav',
                                            post_id: savId
                                        },
                                        success: resp => {
                                            $('button[type="submit"]').text('Validé');
                                            this.loading = false;
                                            Swal.fire({
                                                title: 'Cher client',
                                                html: this.message,
                                                type: 'info',
                                                showCancelButton: false,
                                                width: "60rem"
                                            }).then(result => {
                                                if (result.value) {
                                                    window.location.href = rest_api.redirect_url;
                                                }
                                            });
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
                        <form
                                id="app-form-sav"
                                @submit="checkForm"
                                action=""
                                method="post"
                        >

                            <p v-if="errors.length">
                                <b>Veuillez corriger les erreurs suivantes:</b>
                            <ul style="margin-bottom: 20px;font-size: 14px">
                                <li class="error" v-for="error in errors">{{ error }}</li>
                            </ul>
                            </p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="product">Produit</label>
                                        <input type="text" v-model="product"
                                               class=" form-control tt-input"
                                               id="product"
                                               placeholder="Produit">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="mark">Marque</label>
                                        <input type="text" v-model="mark" class="form-control" id="mark"
                                               placeholder="Marque">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="status_product">Statut du produit</label>
                                        <select name="status_product" v-model="status_product" id="status_product"
                                                v-on:change="statusHandler">
                                            <option value="">Aucun</option>
                                            <option value="1">Sous garantie</option>
                                            <option value="2">Hors garantie</option>
                                        </select>
                                    </div>
                                </div>
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
                                <div class="col-sm-4" v-if="ck_garentee_freezone">
                                    <div class="form-group">
                                        <label for="delais_garentee">Délais de garantie</label>
                                        <select name="delais_garentee"  v-model="delais_garentee" id="delais_garentee">
                                            <option value="">Aucun</option>
                                            <option :value="value" v-for="(value, index) in delais_range"> {{ value }} mois</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4" v-if="ck_date_purchase">
                                    <div class="form-group">
                                        <label for="date_purchase">Date d'achat</label>
                                        <input type="date" v-model="date_purchase" class="form-control"
                                               id="date_purchase" placeholder="">
                                    </div>
                                </div>
                                <div class="col-sm-4" v-if="ck_bill">
                                    <div class="form-group">
                                        <label for="bill">Numéro de la facture</label>
                                        <input type="text" v-model="bill" class="form-control" id="bill"
                                               placeholder="Veuillez saisir le numéro de la facture">
                                    </div>
                                </div>
                                <div class="col-sm-4" v-if="ck_serial_number">
                                    <div class="form-group">
                                        <label for="serial_number">Numéro de série</label>
                                        <input type="text" v-model="serial_number" class="form-control"
                                               id="serial_number"
                                               placeholder="Veuillez saisir le numéro de série">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="description">Probème rencontré</label>
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