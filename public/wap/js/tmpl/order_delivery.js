$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    var r = getQueryString("order_id");
    $.ajax({type: "post", url: ApiUrl + "/Memberorder/search_deliver.html", data: {key: e, order_id: r}, dataType: "json", success: function(e) {
            checkLogin(e.login);
            var r = e && e.result;
            if (!r) {
                r = {};
                r.err = "暂无物流信息"
            }
            var t = template.render("order-delivery-tmpl", r);
            $("#order-delivery").html(t)
        }})
});