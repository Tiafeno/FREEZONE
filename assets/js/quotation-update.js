(function($) {
    $(document).ready(function() {
        $('.sub-quotation-update').on('click', function(ev) {
            ev.preventDefault();
            var el = ev.currentTarget;
            var messageHtml = "<p>Nous vous remercions pour la validation de votre devis, deux cas possibles pour confirmer votre commande : <br> Soit" + 
                "<ul> <li>vous téléchargez le devis et vous y apposez le cachet de votre entreprise (obligatoire)</li><li> signature du responsable (obligatoire) </li><li> " +
                "la mention « bon pour accord » (obligatoire) </li><li> Soit vous nous envoyez votre propre bon de commande.</li></ul> " + 
                "Une fois tout cela fait nous vous demandons de nous envoyer la version scannée par email à commercial @freezonemada.com " +
                "<br><br>Remerciements <br>Le service commercial</p>";
            Swal.fire({
                title: '<strong>Cher client,</strong>',
                icon: 'info',
                html: messageHtml,
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: 'Accepter',
                cancelButtonText: 'Annuler',
                width: "50em"
            }).then(result => {
                if (result.value) {
                    var link = $(el).attr('href');
                    var location = window.location;
                    window.location.href = `${location.origin}/${location.pathname}${link}`;
                }
            })
        });
    });
})(jQuery);