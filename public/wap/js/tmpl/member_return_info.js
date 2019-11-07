$(function() {
    var e = getCookie("key");
    var r = getQueryString("refund_id");
    template.helper("isEmpty", function(e) {
        for (var r in e) {
            return false
        }
        return true
    });
    $.getJSON(ApiUrl + "/Memberreturn/get_return_info.html", {key: e, return_id: r}, function(e) {
        $("#return-info-div").html(template.render("return-info-script", e.result))
    })
});