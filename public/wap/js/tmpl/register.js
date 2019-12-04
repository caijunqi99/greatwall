$(function() {
    //var e = getCookie("key");
    //if (e) {
    //    window.location.href = WapSiteUrl + "/tmpl/member/member.html";
    //    return
    //}
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
    $.sValid.init({rules: {username: "required", userpwd: "required", password_confirm: "required", email: {required: true, email: true}}, messages: {username: "用户名必须填写！", userpwd: "密码必填!", password_confirm: "确认密码必填!", email: {required: "邮件必填!", email: "邮件格式不正确"}}, callback: function(e, r, a) {
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
        var i = $("input[name=email]").val();
        var inviter_id = $("input[name=inviter_id]").val();
        var t = "wap";
        if ($.sValid()) {
            $.ajax({type: "post", url: ApiUrl + "/Login/register.html", data: {username: e, password: r, password_confirm: a, email: i, client: t,inviter_id:inviter_id}, dataType: "json", success: function(e) {
                    if (e.code==200) {
                        if (typeof e.result.key == "undefined") {
                            return false
                        } else {
                            updateCookieCart(e.result.key);
                            addCookie("username", e.result.username);
                            addCookie("key", e.result.key);
                            location.href = WapSiteUrl + "/tmpl/member/member.html"
                        }
                        errorTipsHide()
                    } else {
                        errorTipsShow("<p>" + e.message + "</p>")
                    }
                }})
        }
    })
});