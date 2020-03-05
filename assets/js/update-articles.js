(function ($) {
    $(document).ready(function () {
        new Vue({
            el: '#app-update-articles',
            data: {
                loading: false,
                articles: [],
                condition_product: [{
                        key: 0,
                        value: "Disponible"
                    },
                    {
                        key: 1,
                        value: "Rupture"
                    },
                    {
                        key: 2,
                        value: "Obsolète"
                    },
                    {
                        key: 3,
                        value: "Commande"
                    }
                ]
            },
            methods: {
                addArticlesItem: function (args) {
                    if (_.isEmpty(args)) return false;
                    args.forEach(arg => {
                        this.articles.push({
                            id: arg.fzproduct.ID,
                            designation: arg.fzproduct.name,
                            qty_disp: 0, // args.total_sales
                            qty_ask: arg.quantity,
                            cost: _.isNaN(parseInt(arg.fzproduct.regular_price)) ? 0 : parseInt(arg.fzproduct.regular_price),
                            garentee: _.isNaN(parseInt(arg.fzproduct.garentee)) ? '0' : parseInt(arg.fzproduct.garentee), // Nombre de mois de garentie
                            condition: 0,
                            date_review: moment().format('YYYY-MM-DD HH:mm:ss')
                        });
                    });
                },
                getCookie: function (cname) {
                    var name = cname + "=";
                    var decodedCookie = decodeURIComponent(document.cookie);
                    var ca = decodedCookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') {
                            c = c.substring(1);
                        }
                        if (c.indexOf(name) == 0) {
                            return c.substring(name.length, c.length);
                        }
                    }
                    return "";
                },
                verifyQtyValue: function (index) {
                    Swal.fire({
                        title: 'confirmation',
                        html: "Cet article est en rupture de stock chez vous ?",
                        type: 'info',
                        width: "60rem",
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Oui',
                        cancelButtonText: 'Non'
                    }).then(result => {
                        if (result.value) {
                            this.articles[index].condition = 1;
                        }
                    });
                },
                onChangeQty: function (e, index) {
                    e.preventDefault();
                    var element = e.currentTarget;
                    if (0 === parseInt(element.value)) {
                        this.verifyQtyValue(index);
                    }
                },
                onChangeCondition: function (e, index) {
                    var el = e.currentTarget;
                    if (el.value == 1 || el.value == 2) {
                        // On remet le quantité egale 0 si la condition est en rupture ou obsolete
                        this.articles[index].qty_disp = 0;
                    }
                },
                submitForm: function (e) {
                    e.preventDefault();
                    for (let [index, article] of this.articles.entries()) {
                        if (0 === parseInt(article.qty_disp) && !_.contains([1, 2], parseInt(article.condition))) {
                            var name = article.designation;
                            Swal.fire({
                                title: 'confirmation',
                                html: `L'article <b>${name}</b> en rupture de stock chez vous ?`,
                                type: 'info',
                                width: "60rem",
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Oui',
                                cancelButtonText: 'Non'
                            }).then(result => {
                                if (result.value) {
                                    this.articles[index].condition = 1;
                                }
                            });
                            return false;
                        }
                    }
                    this.updatePost();
                },
                updatePost: function () {
                    var self = this;
                    var btnSubmit = document.querySelector('#submit-update-form');
                    var deferreds = [];
                    this.articles.forEach((article, index) => {
                        var formData = new FormData();
                        // formData.append('price', parseInt(article.cost));
                        // formData.append('total_sales', article.qty_disp);
                        // formData.append('garentee', _.isNaN(parseInt(article.garentee)) ? 0 : parseInt(article.garentee));
                        // formData.append('date_review',article.date_review);
                        // formData.append('condition', parseInt(article.condition));
                        var query = $.ajax({
                            url: rest_api.ajax_url,
                            method: "POST",
                            data: {
                                id: article.id,
                                action: "update_fz_product",
                                price: parseInt(article.cost),
                                total_sales: article.qty_disp,
                                garentee: _.isNaN(parseInt(article.garentee)) ? 0 : parseInt(article.garentee),
                                condition: parseInt(article.condition),
                            },
                            beforeSend: function (xhr) {
                                //xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                            }
                        });
                        deferreds.push(query)
                    });
                    btnSubmit.nodeValue = "Chargement...";
                    btnSubmit.setAttribute('disabled', 'disabled');
                    this.loading = true;
                    Swal.fire("", "Chargement en cours. Veuillez ne pas quitter ou fermer cette page.", 'info');
                    $.when.apply($, deferreds).done(function () {
                        // View response: https://pasteboard.co/IUazdBZ.png
                        var updateResp = arguments; // Array of HTTPResponse
                        var articleIds = _.map(updateResp, function (article) {
                            return article[0].ID;
                        })
                        console.log(updateResp);
                        var mailSuccessUpdate = $.ajax({
                            url: rest_api.ajax_url,
                            method: "GET",
                            data: {
                                action: "mail_succeffuly_update",
                                ids: JSON.stringify(articleIds)
                            }
                        });
                        mailSuccessUpdate.done(function () {
                            console.log(arguments);
                            Swal.fire({
                                title: 'Succes',
                                html: "Mise a jour effectuer avec succes",
                                type: 'success',
                                showCancelButton: false,
                                width: "20rem"
                            }).then(result => {
                                if (result.value) {
                                    window.location.href = rest_api.account_url
                                }
                            });
                        });

                    }).fail(er => {
                        Swal.fire("Erreur", "Une erreur s'est produit", 'error');
                    }).always(function () {
                        self.loading = false;
                        btnSubmit.nodeValue = "Enregistrer";
                        btnSubmit.removeAttribute('disabled');
                    });
                    return false;
                }
            },
            created: function () {
                // https://vuejs.org/v2/guide/instance.html
                const self = this;
                return new Promise(resolve => {
                    var articleIds = self.getCookie('freezone_ua');
                    self.loading = true;
                    $.ajax({
                        method: "GET",
                        url: rest_api.ajax_url,
                        data: {
                            'action': 'get_review_articles',
                            articles: articleIds
                        },
                        success: (resp, status, xhr) => {
                            var articles = resp.data;
                            if (_.isEmpty(articles)) {
                                Swal.fire('Message', "Vous n'avez aucun articles en attente", "info");
                            }
                            this.addArticlesItem(articles);
                            resolve(true);
                        },
                        complete: function () {
                            self.loading = false;
                        }
                    });
                });

            },
        })
    });
})(jQuery);