jQuery(function ($) {
// multiple select with AJAX search
    $('.select2').select2({
        ajax: {
            url: fzOptions.admin_ajax, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            method: 'POST',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: function (params) {
                return {
                    q: params.term, // search query
                    action: 'searchproducts' // AJAX action for admin-ajax.php
                };
            },
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', fzOptions.nonce );
            },
            processResults: function (data) {
                var options = [];
                if (data) {
                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each(data.data, function (index, text) { // do not forget that "index" is just auto incremented value
                        options.push({id: text.ID, text: text.post_title});
                    });

                }
                return {
                    results: options
                };
            },
            cache: true
        },
        minimumInputLength: 3 // the minimum of symbols to input before perform a search
    });
});