(function ($) {
    $(document).ready(function () {
        new Vue({
            el: '#app-prestations',
            data: {
                pageIndex: 0, // 0 default page. https://mail.google.com/mail/u/0/#inbox/FMfcgxwDrHspMhGPsmTbwtrqRXFsLpst?projector=1&messagePartId=0.2
                platformes: [{
                        key: 1,
                        name: "PC"
                    },
                    {
                        key: 2,
                        name: "Laptop"
                    },
                    {
                        key: 3,
                        name: "PC / Laptop"
                    }
                ],
                catalogues: [],
                platform: '',
                categorie: ''
            },
            methods: {
                onChangePlatform: function($event) {
                    $event.preventDefault();
                    const self = this;
                    let data = {};
                    data.per_page = 100;
                    if (!_.isEqual(this.platform, '')) {
                        data.meta_key = "ctg_platform";
                        data.meta_value = this.platform != 3 ? [this.platform, 3] : this.platform;
                        data.meta_compare = 'IN';
                    }
                    
                    self.changeStatus('Chargement en cours...');
                    self.queryCatalogues(data).then(response => {
                        self.changeStatus('');
                        self.catalogues = _.clone(response);
                    });

                },
                queryCatalogues: function (queryData) {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            method: 'GET',
                            url: account_opt.rest_url + 'wp/v2/catalog',
                            data: queryData,
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', account_opt.nonce);
                            },
                            success: (catalogs, status, xhr) => {
                                resolve(catalogs);
                            },
                            error: function() {
                                reject("Une erreur s'est produit");
                            }
                        })
                    })
                },
                changeStatus: function (status) {
                    let element = document.querySelector('#status-catalog');
                    element.innerHTML = status;
                }
            },
            filters: {
                platform: function (value, platformes) {
                    value = _.isNaN(parseInt(value)) ? null : parseInt(value);
                    let findPlatforme = _.find(platformes, {key: parseInt(value)});
                    if (_.isUndefined(findPlatforme)) return 'Aucun';
                    return findPlatforme.name;
                },
                currency: function (value) {
                    let price = parseInt(value, 10);
                    if (_.isNaN(price)) return 'Aucun';
                    return new Intl.NumberFormat('de-DE', {
                        style: 'currency',
                        currency: 'MGA',
                        minimumFractionDigits: 0
                    }).format(price);
                }
            },
            created: function () {
                // https://vuejs.org/v2/guide/instance.html
                const self = this;
                return new Promise(resolve => {
                    self.changeStatus('Chagement en cours...');
                    $.ajax({
                        method: "GET",
                        url: account_opt.rest_url + 'wp/v2/catalog',
                        data: {
                            per_page: 100
                        },
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', account_opt.nonce);
                        },
                        success: (catalogs, status, xhr) => {
                            self.catalogues = _.clone(catalogs);
                            resolve(true);
                        },
                        complete: function() {
                            self.changeStatus('');
                        }
                    });
                });

            },
            mounted: function () {
                // https://vuejs.org/v2/guide/instance.html
                console.log('Vue app is created!');
            }
        });
    });

})(jQuery);