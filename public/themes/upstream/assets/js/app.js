/* Theme Name: syousoft.com Website Template
 * Author:WelkinVan
 * Author URI:http://www.wellda.cn
 * Author e-mail:fangwei@syousoft.com
 * Version:1.0.0
 * Created:2017
 * File Description: Initializations of plugins
 */

// Global variables
//-----------------------------------------------
var GV = {
    ROOT: "__ROOT__/",
    WEB_ROOT: "__WEB_ROOT__/",
    JS_ROOT: "static/js/"
};

(function ($) {
    $(document).ready(function () {

        // resize image-box height
        // image size 16:9
        //-----------------------------------------------
        if ($(window).width() > 575) {
            var el = document.querySelector(".image-box");
            $(".image-box").each(function () {
                $(this).height(el.clientWidth / 1.77777777);
            })
        }
        // make dropdowns show on hover for desktop only
        //-----------------------------------------------
        if ($(window).width() < 992) {
            var el = $('.navbar-nav .dropdown-toggle');
            el.attr('data-toggle', 'dropdown');
            el.attr('aria-haspopup', 'true');
            el.attr('aria-expanded', 'false');
        }
        // tooltip init and images change border style
        //-----------------------------------------------
        $('[data-toggle="tooltip"]').tooltip();
        $('#tooltip-img').on('shown.bs.tooltip', function () {
            var tooltipImgId = $(this).attr("aria-describedby");
            $('#' + tooltipImgId + ' .tooltip-inner').css("padding", "3px");
            $(this).tooltip('update');
        })

        // Isotope filters
        //-----------------------------------------------
        if ($('.masonry-grid-fitrows').length > 0) {
            $(window).load(function () {
                $('.masonry-grid-fitrows').isotope({
                    itemSelector: '.masonry-grid-item',
                    layoutMode: 'fitRows'
                });

            });
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $('.tab-pane .masonry-grid-fitrows').isotope({
                    itemSelector: '.masonry-grid-item',
                    layoutMode: 'fitRows'
                });
            });
        }
        //Scroll totop
        //-----------------------------------------------
        $(window).scroll(function () {
            if ($(this).scrollTop() != 0) {
                $(".scrollToTop").addClass("fadeToTop");
                $(".scrollToTop").removeClass("fadeToBottom");
            } else {
                $(".scrollToTop").removeClass("fadeToTop");
                $(".scrollToTop").addClass("fadeToBottom");
            }
        });

        $(".scrollToTop").click(function () {
            $("body,html").animate({scrollTop: 0}, 800);
        });
    });// End document ready
})(this.jQuery);
