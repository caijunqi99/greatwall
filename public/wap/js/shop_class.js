/**
 * 店铺分类
 */

$(function() {
    $.ajax({
        url:ApiUrl+"/shopclass",
        type:'get',
        jsonp:'callback',
        dataType:'jsonp',
        success:function(result){
            var data = result.result;
            data.WapSiteUrl = WapSiteUrl;
            var html = template.render('category-one', data);
            $("#categroy-cnt").html(html);
        }
    });
});