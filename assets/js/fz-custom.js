(function ($) {
    "use strict";

    $(document).ready(function () {
        var $item = $("ul.product-categories").find('.current-cat-parent');
        var $itemCurrentParent = $("ul.product-categories").find('.current-cat.cat-parent');
        if ($item.length > 0)  $item.find('i').trigger("click");
        if ($itemCurrentParent.length > 0)  $itemCurrentParent.find('i').trigger("click");

    });

})(jQuery);