$(function() {
    var a = getCookie("key");
    var e = '<div class="nctouch-footer-wrap posr">' + '<div class="nav-text">';
    if (a) {
        e += '<a href="' + WapSiteUrl + '/tmpl/member/member.html">我的商城</a>' + '<a id="logoutbtn" href="javascript:void(0);">注销</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/member_feedback.html">反馈</a>'
    } else {
        e += '<a href="' + WapSiteUrl + '/tmpl/member/login.html">登录</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/register.html">注册</a>' + '<a href="' + WapSiteUrl + '/tmpl/member/login.html">反馈</a>'
    }
    e += '<a href="javascript:void(0);" class="gotop">返回顶部</a>' + "</div>" + "</div>";
    
    str = '<div class="footer-nav"><ul>';
    str += '<li class="current"><a href="' + WapSiteUrl + '/index.html"><i class="ico-1"></i><span>首页</span></a></li>';
    str += '<li><a href="' + WapSiteUrl + '/tmpl/product_first_categroy.html"><i class="ico-2"></i><span>分类</span></a></li>';
    str += '<li><a href="' + WapSiteUrl + '/tmpl/store_nearby.html"><i class="ico-3"></i><span>附近店铺</span></a></li>';
    str += '<li><a href="' + WapSiteUrl + '/tmpl/cart_list.html"><i class="ico-4"></i><span>购物车</span></a></li>';
    str += '<li><a href="' + WapSiteUrl + '/tmpl/member/member.html"><i class="ico-5"></i><span>我的</span></a></li></ul></div>';
    $("#footer").html(str);
    
    //$("#footer").html(e);
    $("#logoutbtn").click(function() {
        var a = getCookie("username");
        var e = getCookie("key");
        var i = "wap";
        $.ajax({
            type: "get",
            url: ApiUrl + "/Logout/index.html",
            data: {
                username: a,
                key: e,
                client: i
            },
            success: function(a) {
                if (a) {
                    delCookie("username");
                    delCookie("key");
                    location.href = WapSiteUrl
                }
            }
        })
    })
});
if(WeiXinOauth){
    var key = getCookie('key');
    if(key==null){
        var ua = window.navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i) == 'micromessenger'){
            window.location.href=ApiUrl+"/Wxauto/login.html?ref="+encodeURIComponent(window.location.href);
        }
    }
}