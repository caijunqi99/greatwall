$(function() {
    var e = getCookie("key");
    var t = new ncScrollLoad;
    t.loadInit({url: ApiUrl + "/Memberrefund/get_refund_list.html", getparam: {key: e}, tmplid: "refund-list-tmpl", containerobj: $("#refund-list"), iIntervalId: true, data: {WapSiteUrl: WapSiteUrl}})
});