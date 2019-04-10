(function ($) {
    $(document).ready(function () {
        var containerCompanyName = $('#form-company-name');
        var inputCompanyName = $('#reg_company_name');
        $('#reg_role').on('change', function (e) {
            e.preventDefault();
            var role = e.currentTarget;
            var roleValue = $(role).val();
            if (roleValue === "supplier") {
                containerCompanyName.show('slow');
                inputCompanyName.attr('required', true);
            } else {
                containerCompanyName.hide('slow');
                inputCompanyName.attr('required', false);
            }
        });
    });
})(jQuery);