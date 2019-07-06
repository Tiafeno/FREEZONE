(function ($) {
    $(document).ready(function () {
        $('#reg_client_status').change(function() {
            var sectionCompany = $('#section-company');
            var company = $('#reg_company');
            var stat = $('#reg_stat');
            var nif = $('#reg_nif');
            var rc = $('#reg_rc');
            var cif = $('#reg_cif');
            var roleValue = $(this).val().trim();
            if (roleValue === 'company') {
                sectionCompany.show();
                company.attr('required', true);
                stat.attr('required', true);
                nif.attr('required', true);
                rc.attr('required', true);
                cif.attr('required', true);
            } else {
                sectionCompany.hide();
                company.attr('required', false);
                stat.attr('required', false);
                nif.attr('required', false);
                rc.attr('required', false);
                cif.attr('required', false);
            }
        });
    });
})(jQuery);