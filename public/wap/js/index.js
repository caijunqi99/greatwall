$(function() {

    var headerClone = $('#header').clone();
    $(window).scroll(function(){
        if ($(window).scrollTop() <= $('#main-container1').height()) {
            headerClone = $('#header').clone();
            $('#header').remove();
            headerClone.addClass('transparent').removeClass('');
            headerClone.prependTo('.nctouch-home-top');
        } else {
            headerClone = $('#header').clone();
            $('#header').remove();
            headerClone.addClass('').removeClass('transparent');
            headerClone.prependTo('body');
        }
    });
    $.ajax({
        url: ApiUrl + "/index",
        type: 'get',
        dataType: 'json',
        success: function(result) {
            var data = result.result;
            var html = '';
            $.each(data,function(k, v) {
                $.each(v,function(kk, vv) {
                    console.log(kk);
                    if (k == 'adv_list') {
                        $("#main-container1").html(template.render(k, data));
                    }
                    else {
                        html += template.render(k, data);
                    }
                    return false;
                });
            });

            $("#main-container2").html(html);

            $('.adv_list').each(function() {
                if ($(this).find('.item').length < 2) {
                    return;
                }

                Swipe(this, {
                    startSlide: 2,
                    speed: 400,
                    auto: 3000,
                    continuous: true,
                    disableScroll: false,
                    stopPropagation: false,
                    callback: function(index, elem) {},
                    transitionEnd: function(index, elem) {}
                });
            });

        }
    });

});

//v4 返利
var uid = window.location.href.split("#V3");
var  fragment = uid[1];
if(fragment){
	if (fragment.indexOf("V3") == 0) {document.cookie='uid=0';}
		else {document.cookie='uid='+uid[1];}
	}
