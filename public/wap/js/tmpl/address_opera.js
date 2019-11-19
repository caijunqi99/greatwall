var local;
var map;
$(function() {
    var a = getCookie("key");
    $.sValid.init({rules: {true_name: "required", mob_phone: "required", area_info: "required", address: "required"}, messages: {true_name: "姓名必填！", mob_phone: "手机号必填！", area_info: "地区必填！", address: "街道必填！"}, callback: function(a, e, r) {
            if (a.length > 0) {
                var i = "";
                $.map(e, function(a, e) {
                    i += "<p>" + a + "</p>"
                });
                errorTipsShow(i)
            } else {
                errorTipsHide()
            }
        }});
    $("#header-nav").click(function() {
        $(".btn").click()
    });
    $(".btn").click(function() {
        if ($.sValid()) {
            var e = $("#true_name").val();
            var r = $("#mob_phone").val();
            var i = $("#address").val();
            var d = $("#area_info").attr("data-areaid2");
            var t = $("#area_info").attr("data-areaid");
            var n = $("#area_info").val();
            var o = $("#is_default").attr("checked") ? 1 : 0;
            var lat = $("#latitude").val();
            var lng = $("#longitude").val();
            $.ajax({type: "post", url: ApiUrl + "/Memberaddress/address_add.html", data: {key: a, true_name: e, mob_phone: r, city_id: d, area_id: t, address: i, area_info: n, is_default: o,latitude:lat,longitude:lng}, dataType: "json", success: function(a) {
                    if (a) {
                        location.href = WapSiteUrl + "/tmpl/member/address_list.html"
                    } else {
                        location.href = WapSiteUrl
                    }
                }})
        }
    });
    $("#area_info").on("click", function() {
        $.areaSelected({success: function(a) {
                $("#area_info").val(a.area_info).attr({"data-areaid": a.area_id, "data-areaid2": a.area_id_2 == 0 ? a.area_id_1 : a.area_id_2});
                change_map(a.area_info);
            }})
    })
    $(".public-pos").on("click", function() {
        $.addressSelected({success: function(a) {
                $('#latitude').val(a.lat);
                $('#longitude').val(a.lng);
                $('#address').val(a.address);
            }})
    })
    map = new BMap.Map('mymap');
    var lng=$('#longitude').val();
    var lat=$('#latitude').val();
    if(lng=='' && lat==''){
    var geolocation = new BMap.Geolocation();
    geolocation.getCurrentPosition(function (r) {
        if (this.getStatus() == BMAP_STATUS_SUCCESS) {
            var lng = r.point.lng;
            var lat = r.point.lat;
            var point = new BMap.Point(lng, lat);
            map.centerAndZoom(point, 16);
            document.getElementById("longitude").value = lng;
            document.getElementById("latitude").value = lat;

        } else {
            alert('failed' + this.getStatus());
        }
    }, {enableHighAccuracy: true})
    }else{
        var point = new BMap.Point(lng, lat);
        map.centerAndZoom(point, 16);
    }
    var options = {
        onSearchComplete: function (results) {
            // 判断状态是否正确
            if (local.getStatus() == BMAP_STATUS_SUCCESS) {
                if (results.getCurrentNumPois() > 0) {
                    document.getElementById("longitude").value = results.getPoi(0).point.lng;
                    document.getElementById("latitude").value = results.getPoi(0).point.lat;

                }
            }
        }
    };
    local = new BMap.LocalSearch(map, options);
    
});
    function change_map(name){
            if(name!=''){
                map.centerAndZoom(name,16);
                map.setCurrentCity(name);
                local.search(name);
//                var point=map.getCenter();
//                document.getElementById("longitude").value = point.lng;
//                document.getElementById("latitude").value = point.lat;
            }
            
    }