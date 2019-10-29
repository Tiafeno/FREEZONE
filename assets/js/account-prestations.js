(function ($) {
    $(document).ready(function () {
        new Vue({
            el: '#app-prestations',
            data: {
                pageIndex: 0, // 0 default page. https://mail.google.com/mail/u/0/#inbox/FMfcgxwDrHspMhGPsmTbwtrqRXFsLpst?projector=1&messagePartId=0.2
                categories: [
                    {
                        key: 1,
                        name: "PC"
                    },
                    {
                        key: 2,
                        name: "Laptop"
                    },
                    {
                        key: 3,
                        name: "Tous les plates-formes"
                    }
                ],
                catalogues: [],
                platform: '',
                categorie: ''
            },
            methods: {
                onChangePlatform: ($event) => {
                    console.log($event);
                }
            },
            created: function () {
                // https://vuejs.org/v2/guide/instance.html
                const self = this;
                return new Promise(resolve => {
                    $.ajax({
                        method: "GET",
                        url: account_opt.rest_url + 'wp/v2/catalog?per_page=100',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', account_opt.nonce);
                        },
                        success: catalogs => {
                            self.catalogues = _.clone(catalogs);
                            resolve(true);
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