(function ($) {
    $(document).ready(function () {
        new Vue({
            el: '#app-update-articles',
            data: {
                articles: [],
                condition_product: [
                    { key: 0, value: "Disponible" },
                    { key: 1, value: "Rupture" },
                    { key: 2, value: "Obsolète" },
                    { key: 3, value: "Commande" }
                ]
            },
            methods: {
                addArticlesItem: function (args) {
                    if (_.isEmpty(args)) return false;
                    args.forEach(arg => {
                        this.articles.push({
                            id: arg.ID,
                            designation: arg.name,
                            qty_disp: 0, // args.total_sales
                            qty_ask: arg.quantity,
                            cost: _.isNaN(parseInt(arg.regular_price)) ? 0 : parseInt(arg.regular_price),
                            garentee: _.isNaN(parseInt(arg.garentee)) ? '0' : parseInt(arg.garentee), // Nombre de mois de garentie
                            condition: 0,
                            date_review: moment().format('YYYY-MM-DD HH:mm:ss')
                        });
                    });
                },
                onChangeQty: function(e, index) {
                    e.preventDefault();
                    var element = e.currentTarget;
                    if (0 === parseInt(element.value)) {
                        this.verifyQtyValue(index);
                    }
                },
                verifyQtyValue: function (index) {
                    return new Promise((resolve, reject) => {
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
                                // Rendre l'article en rupture de stock si la quantite est egale a 0
                                this.articles[index].condition = 1;
                                resolve(true);
                            } else {
                                resolve(false);
                            }
                        });
                    });
                },
                submitForm: function(e) {
                    e.preventDefault();
                    var btnSubmit = document.querySelector('#submit-update-form');
                    var deferreds = [];
                    this.articles.forEach((article, index) => {
                        // Verifier si la quantite est egale a Zero (0)
                        var qty = parseInt(article.qty_disp);
                        if (0 === qty) this.verifyQtyValue(index).then(res => {
                            if (!res) throw new Error('La valeur du quantite requis.');
                        });
                        var query = $.ajax({
                            method: "POST",
                            data: {
                                id: article.id,
                                price: parseInt(article.cost),
                                total_sales: qty,
                                garentee: _.isNaN(parseInt(article.garentee)) ? 0 : parseInt(article.garentee),
                                date_review: article.date_review,
                                condition: parseInt(article.condition)
                            },
                            url: `${rest_api.rest_url}wp/v2/fz_product/${article.id}`,
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', rest_api.nonce);
                            },
                            complete: function () { }
                        });
                        deferreds.push(query)
                    });
                    btnSubmit.nodeValue = "Chargement...";
                    btnSubmit.setAttribute('disabled', 'disabled');
                    $.when.apply($, deferreds).done(function() {
                        console.log(arguments);
                        btnSubmit.nodeValue = "Enregistrer";
                        btnSubmit.removeAttribute('disabled');
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
                    return false;
                }
            },
            created: function () {
                // https://vuejs.org/v2/guide/instance.html
                const self = this;
                return new Promise(resolve => {
                    $.ajax({
                        method: "GET",
                        url: rest_api.ajax_url,
                        data: { 'action': 'get_review_articles' },
                        success: (resp, status, xhr) => {
                            var articles = resp.data;
                            if (_.isEmpty(articles)) {
                                Swal.fire('Message', "Vous n'avez aucun articles en attente", "info");
                            }
                            this.addArticlesItem(articles);
                            resolve(true);
                        },
                        complete: function () {

                        }
                    });
                });

            },
        })
    });
})(jQuery);