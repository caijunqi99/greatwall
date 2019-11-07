var order_id, order_goods_id, goods_pay_price, goods_num;
$(function() {
    var e = getCookie("key");
    if (!e) {
        window.location.href = WapSiteUrl + "/tmpl/member/login.html"
    }
    $.getJSON(ApiUrl + "/Memberrefund/refund_form.html", {key: e, order_id: getQueryString("order_id"), order_goods_id: getQueryString("order_goods_id")}, function(o) {
        o.result.WapSiteUrl = WapSiteUrl;
        $("#order-info-container").html(template.render("order-info-tmpl", o.result));
        order_id = o.result.order.order_id;
        order_goods_id = o.result.goods.order_goods_id;
        var a = "";
        for (var r in o.result.reason_list) {
            a += '<option value="' + r + '">' + o.result.reason_list[r].reason_info + "</option>"
        }
        $("#refundReason").append(a);
        goods_pay_price = o.result.goods.goods_pay_price;
        $('input[name="refund_amount"]').val(goods_pay_price);
        $("#returnAble").html("￥" + goods_pay_price);
        goods_num = o.result.goods.goods_num;
        $('input[name="goods_num"]').val(goods_num);
        $("#goodsNum").html("最多" + goods_num + "件");
        $('input[name="refund_pic"]').ajaxUploadImage({url: ApiUrl + "/Memberrefund/upload_pic.html", data: {key: e}, start: function(e) {
                e.parent().after('<div class="upload-loading"><i></i></div>');
                e.parent().siblings(".pic-thumb").remove()
            }, success: function(e, o) {
                checkLogin(o.login);
                if (o.code==100) {
                    e.parent().siblings(".upload-loading").remove();
                    $.sDialog({skin: "red", content: "图片尺寸过大！", okBtn: false, cancelBtn: false});
                    return false
                }
                e.parent().after('<div class="pic-thumb"><img src="' + o.result.pic + '"/></div>');
                e.parent().siblings(".upload-loading").remove();
                e.parents("a").next().val(o.result.file_name)
            }});
        $(".btn-l").click(function() {
            var o = $("form").serializeArray();
            var a = {};
            a.key = e;
            a.order_id = order_id;
            a.order_goods_id = order_goods_id;
            a.refund_type = 2;
            for (var r = 0; r < o.length; r++) {
                a[o[r].name] = o[r].value
            }
            if (isNaN(parseFloat(a.refund_amount)) || parseFloat(a.refund_amount) > parseFloat(goods_pay_price) || parseFloat(a.refund_amount) == 0) {
                $.sDialog({skin: "red", content: "退款金额不能为空，或不能超过可退金额", okBtn: false, cancelBtn: false});
                return false
            }
            if (a.buyer_message.length == 0) {
                $.sDialog({skin: "red", content: "请填写退款说明", okBtn: false, cancelBtn: false});
                return false
            }
            if (isNaN(a.goods_num) || parseInt(a.goods_num) == 0 || parseInt(a.goods_num) > parseInt(goods_num)) {
                $.sDialog({skin: "red", content: "退货数据不能为空，或不能超过可退数量", okBtn: false, cancelBtn: false});
                return false
            }
            $.ajax({type: "post", url: ApiUrl + "/Memberrefund/refund_post.html", data: a, dataType: "json", async: false, success: function(e) {
                    checkLogin(e.login);
                    if (e.code==100) {
                        $.sDialog({skin: "red", content: e.message, okBtn: false, cancelBtn: false});
                        return false
                    }
                    window.location.href = WapSiteUrl + "/tmpl/member/member_return.html"
                }})
        })
    })
});