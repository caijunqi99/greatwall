$(function() {
    $.ajax({
        url: ApiUrl + "/Goodsclass/index.html?gc_id=" + getQueryString("gc_id"),
        type: 'get',
        dataType: 'json',
        success: function(result) {
            var html = template.render('category2', result.result);
            $("#content").append(html);
            var category_item = new Array();
            $(".category-seciond-item").click(function() {
                var gc_id = $(this).attr('gc_id');
                var self = this;
                if (contains(category_item, gc_id)) {
                    $(this).toggleClass("open-sitem");
                    return false;
                }

                $.ajax({
                    url: ApiUrl + "/Goodsclass/index.html?gc_id=" + gc_id,
                    type: 'get',
                    dataType: 'json',
                    success: function(result) {
                        category_item.push(gc_id);
                        if (result) {
                            result.result.gc_id = gc_id;
                            var html = template.render('category3', result.result);
                            $(self).append(html);
                            $(self).addClass('open-sitem');

                            $('.product_list').click(function() {
                                location.href = WapSiteUrl + "/tmpl/product_list.html?gc_id=" + $(this).attr('gc_id');
                            });
                        } else {
                            location.href = WapSiteUrl + "/tmpl/product_list.html?gc_id=" + gc_id;
                        }
                    }
                });
            });

        }
    });
});