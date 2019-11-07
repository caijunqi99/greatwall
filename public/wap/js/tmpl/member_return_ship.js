$(function() {
    var e = getCookie("key");
    var r = getQueryString("refund_id");
    $.getJSON(ApiUrl + "/Memberreturn/ship_form.html", {key: e, return_id: r}, function(a) {
        checkLogin(a.login);
        $("#delayDay").html(a.result.return_delay);
        $("#confirmDay").html(a.result.return_confirm);
        for (var n = 0; n < a.result.express_list.length; n++) {
            $("#express").append('<option value="' + a.result.express_list[n].express_id + '">' + a.result.express_list[n].express_name + "</option>")
        }
        $(".btn-l").click(function() {
            var a = $("form").serializeArray();
            var n = {};
            n.key = e;
            n.return_id = r;
            for (var t = 0; t < a.length; t++) {
                n[a[t].name] = a[t].value
            }
            if (n.invoice_no == "") {
                $.sDialog({skin: "red", content: "请填写快递单号", okBtn: false, cancelBtn: false});
                return false
            }
            $.ajax({type: "post", url: ApiUrl + "/Memberreturn/ship_post.html", data: n, dataType: "json", async: false, success: function(e) {
                    checkLogin(e.login);
                    if (e.code==100) {
                        $.sDialog({skin: "red", content: e.message, okBtn: false, cancelBtn: false});
                        return false
                    }
                    window.location.href = WapSiteUrl + "/tmpl/member/member_return.html"
                }})
        })
    })
});