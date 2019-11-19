$(function () {
    loadSeccode();
    $("#refreshcode").bind("click", function () {
        loadSeccode()
    });
    $.sValid.init({
        rules: {usermobile: {required: true, mobile: true}},
        messages: {usermobile: {required: "请填写手机号！", mobile: "手机号码不正确"}},
        callback: function (e, i, r) {
            if (e.length > 0) {
                var l = "";
                $.map(i, function (e, i) {
                    l += "<p>" + e + "</p>";
                });
                errorTipsShow(l);
            } else {
                errorTipsHide();
            }
        }
    });
    $("#find_password_btn").click(function () {
        if (!$(this).parent().hasClass("ok")) {
            return false;
        }
        if ($.sValid()) {
            var a = $.trim($("#captcha").val());
            $.ajax({type: "post", url: ApiUrl + "/Seccode/check.html", data: {captcha: a}, dataType: "json", success: function(e) {
                if(e.code == 200) {
                    setTimeout(location.href = WapSiteUrl+'/tmpl/member/find_password_code.html?mobile='+ $("#usermobile").val(), 1e3);
                }else {
                    errorTipsShow("<p>" + e.message + "</p>");
                    $("#codeimage").attr("src", ApiUrl + "/Seccode/makecode.html?k=" + $("#codekey").val() + "&t=" + Math.random());
                    $("#captcha").val("");
                }
            }});
        } else {
            return false;
        };
    });
});