
$(function() {
    var url = location.search; 
    var theRequest = new Object();
    if (url.indexOf("?") != -1) {
        var str = url.substr(1);
        strs = str.split("&");
        for(var i = 0; i < strs.length; i ++) {
            theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
        }
    }
    var e = theRequest['key'];
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