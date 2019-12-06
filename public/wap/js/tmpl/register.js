$(function() {
    //var e = getCookie("key");
    //if (e) {
    //    window.location.href = WapSiteUrl + "/tmpl/member/member.html";
    //    return
    //}
    loadSeccode();
    $("#refreshcode").bind("click", function () {
        loadSeccode()
    });
    if(getQueryString("inviter_id")){
        $.getJSON(ApiUrl + "/Login/get_inviter/index.html?inviter_id="+getQueryString("inviter_id"), function(e) {
            var t = e.result;
            if(t.member){
            t.WapSiteUrl = WapSiteUrl;
            var r = template.render("inviter", t);
            $("ul.form-box").prepend(r);
            }

        })  
    }
    $.getJSON(ApiUrl + "/Connect/get_state.html?t=connect_sms_reg", function(e) {
        if (e.result == "0") {
            $(".register-tab").hide()
        }
    });
    $.sValid.init({
        rules: {username: {required: true, mobile: true}, userpwd: "required", password_confirm: "required", email: {required: true, email: true}},
        messages: {username:{required: "请填写手机号！", mobile: "手机号码不正确"}, userpwd: "密码必填!", password_confirm: "确认密码必填!", email: {required: "邮件必填!", email: "邮件格式不正确"}},
        callback: function(e, r, a) {
            if (e.length > 0) {
                var i = "";
                $.map(r, function(e, r) {
                    i += "<p>" + e + "</p>"
                });
                errorTipsShow(i)
            } else {
                errorTipsHide()
            }
        }});
    $("#registerbtn").click(function() {
        if (!$(this).parent().hasClass("ok")) {
            return false
        }
        var e = $("input[name=username]").val();
        var r = $("input[name=pwd]").val();
        var a = $("input[name=password_confirm]").val();
        var inviter_code = $("input[name=inviter_code]").val();
        var mobilecode = $("input[name=mobilecode]").val();
        var t = "wap";
        var log_type=1;
        if ($.sValid()) {
            $.ajax({type: "post", url: ApiUrl + "/Login/register.html", data: {username: e, password: r, password_confirm: a, client: t,inviter_code:inviter_code,sms_captcha:mobilecode,log_type:log_type}, dataType: "json", success: function(e) {
                    if (e.code==200) {
                        if (typeof e.result.key == "undefined") {
                            return false
                        } else {
                            updateCookieCart(e.result.key);
                            addCookie("username", e.result.username);
                            addCookie("key", e.result.key);
                            location.href = WapSiteUrl + "/erweima.html"
                        }
                        errorTipsHide()
                    } else {
                        errorTipsShow("<p>" + e.message + "</p>")
                    }
                }})
        }
    });

    var c = getQueryString("captcha");
    $("#again").click(function() {
        c = $("#captcha").val();
        a = $("#codekey").val();
        e = $("#username").val();
        send_sms(e, c)
    });
});
function send_sms(e) {
    $.getJSON(ApiUrl + "/Connect/get_sms_captcha.html", {
            type: 1,
            member_mobile: e
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