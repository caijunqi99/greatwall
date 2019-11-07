$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    var r = getQueryString("order_id");
    $.getJSON(ApiUrl + "/Memberevaluate/vr.html", {
        key: e,
        order_id: r
    }, function(a) {
        if (a.code==100) {
            $.sDialog({
                skin: "red",
                content: a.message,
                okBtn: false,
                cancelBtn: false
            });
            return false
        }
        var t = template.render("member-evaluation-script", a.result);
        $("#member-evaluation-div").html(t);
        $(".star-level").find("i").click(function() {
            var e = $(this).index();
            for (var r = 0; r < 5; r++) {
                var a = $(this).parent().find("i").eq(r);
                if (r <= e) {
                    a.removeClass("star-level-hollow").addClass("star-level-solid")
                } else {
                    a.removeClass("star-level-solid").addClass("star-level-hollow")
                }
            }
            $(this).parent().next().val(e + 1)
        });
        $(".btn-l").click(function() {
            var a = $("form").serializeArray();
            var t = {};
            t.key = e;
            t.order_id = r;
            for (var l = 0; l < a.length; l++) {
                t[a[l].name] = a[l].value
            }
            $.ajax({
                type: "post",
                url: ApiUrl + "/Memberevaluate/save_vr.html",
                data: t,
                dataType: "json",
                async: false,
                success: function(e) {
                    checkLogin(e.login);
                    if (e.code==100) {
                        $.sDialog({
                            skin: "red",
                            content: e.message,
                            okBtn: false,
                            cancelBtn: false
                        });
                        return false
                    }
                    window.location.href = WapSiteUrl + "/tmpl/member/vr_order_list.html"
                }
            })
        })
    })
});