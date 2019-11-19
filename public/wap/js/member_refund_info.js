$(function() {
    var e = getCookie("key");
    var r = getQueryString("refund_id");
    template.helper("isEmpty", function(e) {
        for (var r in e) {
            return false
        }
        return true
    });
    $.getJSON(ApiUrl + "/Memberrefund/get_refund_info.html", {key: e, refund_id: r}, function(e) {
        $("#refund-info-div").html(template.render("refund-info-script", e.result))
    })
});