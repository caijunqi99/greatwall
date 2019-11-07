//v3-b11
$(function() {
    var ac_id = getQueryString('ac_id')
    if (ac_id == '') {
        window.location.href = WapSiteUrl + '/index.html';
        return;
    }
    else {
        $.ajax({
            url: ApiUrl + "/Articleclass/index.html",
            type: 'get',
            data: {ac_id: ac_id},
            jsonp: 'callback',
            dataType: 'jsonp',
            success: function(result) {
                var data = result.result;
                data.WapSiteUrl = WapSiteUrl;
                var html = template.render('article-class', data);
                $("#article-content").html(html);
            }
        });
    }
});