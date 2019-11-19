$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    loadSeccode();
    $("#refreshcode").bind("click", function() {
        loadSeccode();
    });
    $.ajax({type: "get", url: ApiUrl + "/Memberaccount/get_mobile_info.html", data: {key: e}, dataType: "json", success: function(e) {
            if (e.code == 200) {
                if (e.result.state) {
                    $("#mobile").html(e.result.mobile)
                } else {
                    location.href = WapSiteUrl + "/tmpl/member/member_mobile_bind.html"
                }
            }
        }});
    $.sValid.init({rules: {captcha: {required: true, minlength: 4}}, messages: {captcha: {required: "请填写图形验证码", minlength: "图形验证码不正确"}}, callback: function(e, a, t) {
            if (e.length > 0) {
                var o = "";
                $.map(a, function(e, a) {
                    o += "<p>" + e + "</p>"
                });
                errorTipsShow(o);
            } else {
                errorTipsHide();
            }
        }});
    $("#send").click(function() {
        if ($.sValid()) {
            var a = $.trim($("#captcha").val());
            var t = $.trim($("#codekey").val());
            $.ajax({type: "post", url: ApiUrl + "/Memberaccount/modify_password_step2.html", data: {key: e, captcha: a, codekey: t}, dataType: "json", success: function(e) {
                    if (e.code == 200) {
                            $("#send").hide();
                            $(".code-countdown").show().find("em").html(e.result.sms_time);
                            $.sDialog({skin: "block", content: "短信验证码已发出", okBtn: false, cancelBtn: false});
                        var a = setInterval(function() {
                            var e = $(".code-countdown").find("em");
                            var t = parseInt(e.html() - 1);
                            if (t == 0) {
                                $("#send").show();
                                $(".code-countdown").hide();
                                clearInterval(a);
                                $("#codeimage").attr("src", ApiUrl + "/Seccode/makecode.html?k=" + $("#codekey").val() + "&t=" + Math.random())
                            } else {
                                e.html(t);
                            }
                        }, 1e3);
                    } else {
                        errorTipsShow("<p>" + e.message + "</p>");
                        $("#codeimage").attr("src", ApiUrl + "/Seccode/makecode.html?k=" + $("#codekey").val() + "&t=" + Math.random());
                        $("#captcha").val("");
                    }
                }});
        }
    });
    $("#nextform").click(function() {
        if (!$(this).parent().hasClass("ok")) {
            return false;
        }
        var a = $.trim($("#auth_code").val());
        if (a) {
            $.ajax({type: "post", url: ApiUrl + "/Memberaccount/modify_password_step3.html", data: {key: e, auth_code: a}, dataType: "json", success: function(e) {
                    if (e.code == 200) {
                        $.sDialog({skin: "block", content: "手机验证成功，正在跳转", okBtn: false, cancelBtn: false});
                        setTimeout("location.href = WapSiteUrl+'/tmpl/member/member_password_step2.html'", 1e3);
                    } else {
                        errorTipsShow("<p>" + e.message + "</p>");
                    }
                }});
        }
    });
});