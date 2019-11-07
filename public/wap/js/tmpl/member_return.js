$(function() {
    var e = getCookie("key");
    var r = new ncScrollLoad;
    r.loadInit({url: ApiUrl + "/Memberreturn/get_return_list.html", getparam: {key: e}, tmplid: "return-list-tmpl", containerobj: $("#return-list"), iIntervalId: true, data: {WapSiteUrl: WapSiteUrl}, callback: function() {
            $(".delay-btn").click(function() {
                return_id = $(this).attr("return_id");
                $.getJSON(ApiUrl + "/Memberreturn/delay_form.html", {key: e, return_id: return_id}, function(r) {
                    checkLogin(r.login);
                    $.sDialog({skin: "red", content: '发货 <span id="delayDay">' + r.result.return_delay + '</span> 天后，当商家选择未收到则要进行延迟时间操作；<br> 如果超过 <span id="confirmDay">' + r.result.return_confirm + "</span> 天不处理按弃货处理，直接由管理员确认退款。", okFn: function() {
                            $.ajax({type: "post", url: ApiUrl + "/Memberreturn/delay_post.html", data: {key: e, return_id: return_id}, dataType: "json", success: function(e) {
                                    checkLogin(e.login);
                                    if (e.dcode==100) {
                                        $.sDialog({skin: "red", content: e.message, okBtn: false, cancelBtn: false});
                                        return false
                                    }
                                    window.location.href = WapSiteUrl + "/tmpl/member/member_return.html"
                                }})
                        }, cancelFn: function() {
                            window.location.href = WapSiteUrl + "/tmpl/member/member_return.html"
                        }});
                    return false
                })
            })
        }})
});