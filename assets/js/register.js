(function ($) {
    $(document).ready(function () {
        $('#reg_role').change(function() {
            var sectionCompany = $('#section-company');
            var sectionParticular = $('#section-particular');
            var company = $('#reg_company');
            var stat = $('#reg_stat');
            var nif = $('#reg_nif');
            var rc = $('#reg_rc');
            var cif = $('#reg_cif');
            var cin = $('#reg_cin');
            var sectorActivity = $('#reg_sector_activity');
            var dateCin = $('#reg_date_cin');
            var roleValue = $(this).val().trim();
            if (roleValue === 'company') {
                sectionCompany.show();
                sectionParticular.hide();
                company.attr('required', true);
                sectorActivity.attr('required', true);
                stat.attr('required', true);
                nif.attr('required', true);
                rc.attr('required', true);
                cif.attr('required', true);
                cin.attr('required', false);
                dateCin.attr('required', false);
            } else if (roleValue === 'particular') {
                sectionCompany.hide();
                sectionParticular.show();
                company.attr('required', false);
                sectorActivity.attr('required', false);
                stat.attr('required', false);
                nif.attr('required', false);
                rc.attr('required', false);
                cif.attr('required', false);
                cin.attr('required', true);
                dateCin.attr('required', true);
            } else {
                sectionCompany.hide();
                sectionParticular.hide();
            }
        });
    });
})(jQuery);