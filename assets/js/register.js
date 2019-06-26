(function ($) {
    $(document).ready(function () {
        $('#reg_role').change(function() {
            var sectionCompany = $('#section-company');
            var roleValue = $(this).val().trim();
            if (roleValue === 'supplier') {
                sectionCompany.show();
            } else {
                sectionCompany.hide();
            }
        });
    });
})(jQuery);