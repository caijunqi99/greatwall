$(function() {
    loadSeccode();
    $("#refreshcode").bind("click",
            function() {
                loadSeccode()
            });
    var e = getQueryString("mobile");
    var c = getQueryString("captcha");
    $("#usermobile").html(e);
    send_sms(e, c);
    $("#again").click(function() {
        c = $("#captcha").val();
        a = $("#codekey").val();
        send_sms(e, c)
    });
    $("#register_mobile_password").click(function() {
        if (!$(this).parent().hasClass("ok")) {
            return false
        }
        var c = $("#mobilecode").val();
        if (c.length == 0) {
            errorTipsShow("<p>请填写验证码<p>")
        }
        check_sms_captcha(e, c);
        return false
    })
});
function send_sms(e) {
    $.getJSON(ApiUrl + "/Connect/get_sms_captcha.html", {
        type: 1,
        phone: e
    },
    function(e) {
        if (e.code==200 ) {
            $.sDialog({
                skin: "green",
                content: "发送成功",
                okBtn: false,
                cancelBtn: false
            });
            $(".code-again").hide();
            $(".code-countdown").show().find("em").html(e.result.sms_time);
            var c = setInterval(function() {
                var e = $(".code-countdown").find("em");
                var a = parseInt(e.html() - 1);
                if (a == 0) {
                    $(".code-again").show();
                    $(".code-countdown").hide();
                    clearInterval(c)
                } else {
                    e.html(a)
                }
            },
                    1e3)
        } else {
            loadSeccode();
            errorTipsShow("<p>" + e.message + "<p>")
        }
    })
}
function check_sms_captcha(e, c) {
    $.getJSON(ApiUrl + "/Connect/check_sms_captcha.html", {
        type: 1,
        phone: e,
        captcha: c
    },
    function(a) {
        if (a.code==200) {
            window.location.href = "register_mobile_password.html?mobile=" + e + "&captcha=" + c
        } else {
            loadSeccode();
            errorTipsShow("<p>" + a.message + "<p>")
        }
    })
}