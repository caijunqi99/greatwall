
$(function() {
    var e = getParam('key');
    $.getJSON(ApiUrl + "/memberinviter/index.html?key="+e, function(e) {
        //checkLogin(e.login);
        if (e.result.refer_qrcode_logo == null) {
                    return false
                }
        var t = e.result;
        $('#foo').val(t.inviter_url);
        t.WapSiteUrl = WapSiteUrl;
        var r = template.render("member_poster", t);
        $("#poster").html(r);
    })
});