(function ($) {
    var order_id = Generator.order_id;
    $(document).ready(function () {
        var element = document.getElementById('pdf-document');
        var btn = document.getElementById("download-btn");
        btn.addEventListener('click', function () {
            var opt = {
                margin: 10,
                filename: 'Devis-DW' + order_id + '-Freezone.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            // New Promise-based usage:
            html2pdf().set(opt).from(element).save();
        }, false);

    });
})(jQuery);