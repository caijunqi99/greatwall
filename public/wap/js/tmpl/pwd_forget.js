$(function() {
    var tel = "";
    var ckcode = "";
    function getCkCode() {
        tel = $("#telephone").val();
        $.valid(["telephone"], function(err) {//验证手机号
            if (FL.isEmptyObject(err)) {//验证通过
                $.ajax({
                    type: 'post',
                    url: ApiUrl + "/Commonmember/get_phone_code.html",
                    data: {number: tel, reset: true},
                    dataType: 'json',
                    success: function(result) {
                        if (result.code === 200) {
                            $("#getckcode").hide();
                            $(".getckcode2").css({display: "inline-block"});
                            var j = 120;
                            $(".getckcode2").html(j + "秒");
                            var myinterval = setInterval(function() {
                                j = j - 1;
                                $(".getckcode2").html(j + "秒");
                                if (j < 0) {
                                    clearInterval(myinterval);
                                    j = 120;
                                    $("#getckcode").css({display: "inline-block"});
                                    $(".getckcode2").hide();
                                }
                            }, 1000);
                            $("#ckcode").removeAttr("disabled");
                        } else {
                            //alert(result.error);
                            $.sDialog({
                                skin: "red",
                                content: result.message,
                                okBtn: false,
                                cancelBtn: false
                            });
                        }
                    }
                });

            } else {
                var errTip = "";
                for (var i in err) {
                    errTip += "<p>" + err[i] + "</p>";
                }
                console.log(err);
                $.sDialog({
                    skin: "red",
                    content: errTip,
                    okBtn: false,
                    cancelBtn: false
                });
            }
        });
    }

    //填完验证码，点击下一步
    function nextPage() {
        ckcode = $("#ckcode").val();
        tel = $("#telephone").val();
        FL.valid(["ckcode"], function(err) {//验证 验证码
            if (FL.isEmptyObject(err)) {//验证通过
                $.ajax({
                    type: 'post',
                    url: ApiUrl + "/Commonindex/check_code.html",
                    data: {number: tel, code: ckcode},
                    dataType: 'json',
                    success: function(result) {
                        if (result.code==200) {
                            $("div.item1").hide();
                            $("div.item2").show();
                        } else {
                            $.sDialog({
                                skin: "red",
                                content: result.message,
                                okBtn: false,
                                cancelBtn: false
                            });

                        }
                    }
                });
            } else {
                var errTip = "";
                for (var i in err) {
                    errTip += "<p>" + err[i] + "</p>";
                }
                $.sDialog({
                    skin: "red",
                    content: errTip,
                    okBtn: false,
                    cancelBtn: false
                });
            }
        });

    }

    //修改密码按钮点击
    function editPwd() {
        var password = $("#password").val();
        FL.valid(["password", "repassword"], function(err) {//验证 验证码
            if (FL.isEmptyObject(err)) {//验证通过
                $.ajax({
                    type: 'post',
                    url: ApiUrl + "/Commonindex/reset_pwd.html",
                    data: {number: tel, code: ckcode, pwd: password},
                    dataType: 'json',
                    success: function(result) {
                        if (result.code==100) {
                            $.sDialog({
                                skin: "red",
                                content: result.message,
                                okBtn: false,
                                cancelBtn: false
                            });
                        } else {
                            $.sDialog({
                                skin: "green",
                                content: "修改密码成功",
                                okBtn: false,
                                cancelBtn: false
                            });
                            setTimeout(function() {
                                delCookie('username');
                                delCookie('userid');
                                delCookie('key');
                                location.href = WapSiteUrl + "/tmpl/member/member.html";
                            }, 1000);
                        }
                    }
                });
            } else {
                var errTip = "";
                for (var i in err) {
                    errTip += "<p>" + err[i] + "</p>";
                }
                $.sDialog({
                    skin: "red",
                    content: errTip,
                    okBtn: false,
                    cancelBtn: false,
                    autoTime: 2000
                });

            }
        });
    }

    //点击获取验证码按钮
    $("#getckcode").click(function() {
        getCkCode();
    });

    //点击下一步
    $(".pwd_forgetwarp .next").click(function() {
        nextPage();
    });

    //修改密码
    $("#btn_editpwd").click(function() {
        editPwd();
    });
});