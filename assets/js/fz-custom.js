(function ($) {
    "use strict";

    $(document).ready(function () {
        var $item = $("ul.product-categories").find('.current-cat-parent');
        if ($item.length > 0) {
            $item.find('i').trigger("click");
        }
    });

})(jQuery);