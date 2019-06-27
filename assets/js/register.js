(function ($) {
    $(document).ready(function () {
        $('#reg_client_status').change(function() {
            var sectionCompany = $('#section-company');
            var roleValue = $(this).val().trim();
            if (roleValue === 'company') {
                sectionCompany.show();
            } else {
                sectionCompany.hide();
            }
        });
    });
})(jQuery);