$(function() {
    var e = getCookie("key");
    if (e === null) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    $("#feedbackbtn").click(function() {
        var a = $("#feedback").val();
        if (a == "") {
            $.sDialog({skin: "red", content: "请填写反馈内容", okBtn: false, cancelBtn: false});
            return false
        }
        $.ajax({url: ApiUrl + "/Memberfeedback/feedback_add.html", type: "post", dataType: "json", data: {key: e, feedback: a}, success: function(e) {
                if (checkLogin(e.login)) {
                    if (e.code==200) {
                        errorTipsShow("<p>提交成功</p>");
                        setTimeout(function() {
                            window.location.href = WapSiteUrl + "/tmpl/member/member.html"
                        }, 3e3)
                    } else {
                        errorTipsShow("<p>" + e.message + "</p>")
                    }
                }
            }})
    })
});