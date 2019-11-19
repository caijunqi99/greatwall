var local;
var map;
(function($) {
    $.extend($, {
    	positionSelected: function(options) {
            var defaults = {
                success: function(e) {}
            };
            var options = $.extend({},
            defaults, options);
            var MAP='';
            var LOCAL='';
            var ADDRESS = "";
            var LNG = "";
            var LAT = '';
            function _init() {
                if ($("#positionSelected").length > 0) {
                    $("#positionSelected").remove()
                }
                var pos_area=getCookie('pos_area');
                var pos_address=getCookie('pos_address');
                if(!pos_area){
                	pos_area='地区选择';
                }
                if(!pos_address){
                	pos_address='地址选择';
                }
                var e = '<div id="positionSelected">' + '<div class="nctouch-full-mask left">' + '<div class="nctouch-full-mask-bg"></div>' + '<div class="nctouch-full-mask-block">' + '<div class="header">' + '<div class="header-wrap">' + '<div class="header-l"><a href="javascript:void(0);"><i class="back"></i></a></div>' + '<div class="header-title">' + "<h1>选择位置</h1>" + "</div>" + '<div class="header-r"><a href="javascript:void(0);"><i class="close"></i></a></div>' + "</div>" + "</div>"+ '<div class="pos_content">'+"<div class='list_item' onclick='area_click()'><h4 id='pos_area'>"+pos_area+"<i class='arrow_right'></i></h4></div>"+"<div class='list_item' onclick='address_click()'><h4 id='pos_address'>"+pos_address+"<i class='arrow_right'></i></h4></div>"+ "</div>" + "</div>" + "</div>" + "</div>";
                $("body").append(e);
                _getAddress();
                _bindEvent();
                _close()
            }
            function _getAddress() {
                
                return false
            }
            function _bindEvent() {
                $("#positionSelected").find(".location_name").off("keyup", "#map_keywords");
                $("#positionSelected").find(".location_name").on("keyup", "#map_keywords",
                function() {
                    LOCAL.search($('#map_keywords').val());
                });
                $("#positionSelected").find("#r-result").off("click", ".address_list_wrap");
                $("#positionSelected").find("#r-result").on("click", ".address_list_wrap",
                function() {
                    var e = {
                        address: $(this).attr('data-address'),
                        lng: $(this).attr('data-lng'),
                        lat: $(this).attr('data-lat')
                    };
                    options.success.call("success", e);
                    $("#positionSelected").find(".nctouch-full-mask").addClass("right").removeClass("left")
                    return false
                });
            }
            function _close() {
                $("#positionSelected").find(".header-l").off("click", "a");
                $("#positionSelected").find(".header-l").on("click", "a",
                function() {
                    $("#positionSelected").find(".nctouch-full-mask").addClass("right").removeClass("left")
                });
                return false
            }
            return this.each(function() {
                return _init()
            })()
        },
        
    })})(Zepto);
function area_click(){
    $.areaSelected({success: function (a) {
        $("#pos_area").text(a.area_info);
        
        $(".pos_area").text(a.area_info);
        $(".pos_address").text('');
        setCookie('pos_area', a.area_info, 30);
        
        change_map(a.area_info);
    }})
}
function address_click(){
    $.addressSelected({success: function (a) {
        $("#pos_address").text(a.address);
        
        $(".pos_address").text(a.address);
        setCookie('pos_address', a.address, 30);
        setCookie('longitude', a.lng, 30);
        setCookie('latitude', a.lat, 30);
        get_store();
    }})
}
$(function () {

    $("#pos_info").on("click", function () {
        $.positionSelected({success: function (a) {
                $("#pos_area").text(a.area_info);
                $("#pos_address").text(a.address);
                $(".pos_area").text(a.area_info);
                $(".pos_address").text(a.address);
                setCookie('pos_area', a.area_info, 30);
                setCookie('pos_address', a.address, 30);
                get_store();
            }})
    })

    map = new BMap.Map('mymap');
    var lng = getCookie("longitude");
    var lat = getCookie("latitude");
    if (!lng && !lat) {
        var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function (r) {
            if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                var lng = r.point.lng;
                var lat = r.point.lat;
                
                var point = new BMap.Point(lng, lat);
                map.centerAndZoom(point, 16);
                setCookie('longitude', lng, 30);
                setCookie('latitude', lat, 30);
                get_store();
                var gc = new BMap.Geocoder();  //初始化，Geocoder类
                
                gc.getLocation(point, function (rs) {
                    var addComp = rs.addressComponents;
                    var province = addComp.province;//获取省份
                    var city = addComp.city;//获取城市
                    var district = addComp.district;//区
                    var street = addComp.street;//街
                    setCookie('pos_area', province + ' ' + city + ' ' + district, 30);
                    setCookie('pos_address', street, 30);
                    $(".pos_area").text(province + ' ' + city + ' ' + district);
                    $(".pos_address").text(street);
                    $("#pos_area").text(province + ' ' + city + ' ' + district);
                    $("#pos_address").text(street);
                });
            } else {
                alert('failed' + this.getStatus());
            }
        }, {enableHighAccuracy: true})
    } else {
        get_store();
        var point = new BMap.Point(lng, lat);
        map.centerAndZoom(point, 16);
    }
    var options = {
            onSearchComplete: function(results){
                    // 判断状态是否正确
                    if (local.getStatus() == BMAP_STATUS_SUCCESS){
                            var s = [];
                            for (var i = 0; i < results.getCurrentNumPois(); i ++){
                                    s.push('<p class="address_list_wrap" data-lng="'+results.getPoi(i).point.lng+'" data-lat="'+results.getPoi(i).point.lat+'" data-address="'+results.getPoi(i).address+'"><span class="address_mt">'+results.getPoi(i).title + "</span><br><span class='address_mc'> " + results.getPoi(i).address+'</span></p>');
                            }
                            if(results.getCurrentNumPois()>0){

                                map.clearOverlays();  //清除标注  或者可以把market 放入数组
                                var point = new BMap.Point(results.getPoi(0).point.lng , results.getPoi(0).point.lat);
                                var marker = new BMap.Marker(point);
                                map.centerAndZoom(point, 16);
                                map.addOverlay(marker);
                                marker.setAnimation(BMAP_ANIMATION_BOUNCE); //跳动的动画
                                setCookie('longitude', point.lng, 30);
                                setCookie('latitude', point.lat, 30);
                                

                            }
                            
                    }
            }
    };
    local = new BMap.LocalSearch(map, options);
    var pos_area = getCookie("pos_area");
    var pos_address = getCookie("pos_address");
    if (pos_area) {
        $("#pos_area").text(pos_area);
        $(".pos_area").text(pos_area);
    } else {
        $("#pos_area").text('请选择地区');
    }
    if (pos_address) {
        $(".pos_address").text(pos_address);
        $("#pos_address").text(pos_address);
    } else {
        $("#pos_address").text('请选择地址');
    }

});
function get_store(){
	var keyword=$('[name=keyword]').val();
	var lng=getCookie("longitude");
	var lat=getCookie("latitude");
    $.getJSON(ApiUrl + "/shopnearby/index.html?longitude="+lng+"&latitude="+lat+"&keyword="+keyword, function(e) {
        var t = e.result;
        t.WapSiteUrl = WapSiteUrl;
        var r = template.render("shop-list", t);
        $("#shop-nearby").html(r);
    })
}
function change_map(name) {
    if (name != '') {
        map.centerAndZoom(name, 16);
        map.setCurrentCity(name);
     
        local.search(name);
     
        get_store();
        $("#pos_address").text('请选择地址');
        $(".pos_address").text('');
        setCookie('pos_address', '', 30);
    }

}