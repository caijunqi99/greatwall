$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html";
        return
    }
    $.ajax({type: "get", url: ApiUrl + "/Memberaccount/get_mobile_info.html", data: {key: e}, dataType: "json", success: function(e) {
            if (e.code == 200) {
                if (e.result.state) {
                    $("#mobile_link").attr("href", "member_mobile_modify.html");
                    $("#mobile_value").html(e.result.mobile)
                }
            } else {
            }
        }});
    $.ajax({type: "get", url: ApiUrl + "/Memberaccount/get_paypwd_info.html", data: {key: e}, dataType: "json", success: function(e) {
            if (e.code == 200) {
                if (!e.result.state) {
                    $("#paypwd_tips").html("未设置")
                }
            } else {
            }
        }});
    $('#toWxPay').click(function(event) {
        $.ajax({
            url: ApiUrl + "/Memberpayment/PdaddPay",
            type: 'POST',
            dataType: 'json',
            data: {
                'payment_code': 'wxpay_app',
                'pdr_amount' : 0.01,
                'key' : e
            },
            success:function(sb){
                var data  = sb.result;
                var data = JSON.parseJSON(data);
                console.log(data);
            }
        })
    });
});