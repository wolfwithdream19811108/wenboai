
    var GV = {    
        ROOT: "__ROOT__/",    
        WEB_ROOT: "__WEB_ROOT__/",    
        JS_ROOT: "static/js/"    
    };    



    $(function () {    
        $("#main-menu a").each(function () {    
            if ($(this)[0].href == String(window.location)) {    
                $(this).parents("ul.navbar-nav>li").addClass("active");    
            }    
        });    
    });   
    
    
    wow = new WOW({    
        boxClass: 'wow',    
        animateClass: 'animated',    
        offset: 0    
    });    
    wow.init();  