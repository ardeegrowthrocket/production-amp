(function ($){

    var app = {
        'viewPort' : 1183,
        'mustHaveCarousel' : {
            'init' : function(wrapper) {
                let $mustHaveWrapper = $(wrapper);
                if($mustHaveWrapper.length) {

                    var initCarousel = function() {
                        if($(window).width() <= app.viewPort) {
                            $mustHaveWrapper.owlCarousel({
                                loop:false,
                                items:5,
                                autoPlay: false,
                                pagination: true,
                                navigation : false,
                                margin: 10
                            });
                        }else {
                            $mustHaveWrapper.addClass('off');
                        }
                    };
                    initCarousel();
                    $(window).resize(function() {
                        initCarousel();
                    });
                }
            }
        },
        'bestsellerCarousel' : function(wrapper) {
            let $bestSellerWrapper = $(wrapper);
            let pagination = false;
            let navigation = true;
            let touchDrag = false;
            let mouseDrag = false;
            if($(window).width() <= app.viewPort) {
                pagination = true;
                navigation = false;
                touchDrag = true;
                mouseDrag = true;
            }
            if($bestSellerWrapper.length) {
                $bestSellerWrapper.owlCarousel({
                    loop:true,
                    items:5,
                    autoPlay: true,
                    pagination: pagination,
                    navigation : navigation,
                    touchDrag  : touchDrag,
                    mouseDrag  : mouseDrag
                })
            }

        },
        //
        'testCarousel' : function(wrapper) {
            let $testCarouselWrapper = $(wrapper);
            let pagination = false;
            let navigation = true;
            let touchDrag = false;
            let mouseDrag = false;
            
            if($testCarouselWrapper.length) {
                $testCarouselWrapper.owlCarousel({
                    loop:true,
                    items:5,
                    autoPlay: true,
                    pagination: pagination,
                    navigation : navigation,
                    touchDrag  : touchDrag,
                    mouseDrag  : mouseDrag,
                    navigationText: ["<i class='fa fa-caret-left'></i>","<i class='fa fa-caret-right'></i>"]

                })
                $('.owl-carousel-default img').on("error", function() {
                  $(this).attr('src', '/media/catalog/product/n/o/noproductimage_9.jpg');
                });
            }

        },
        //
        'init' : function() {
            app.bestsellerCarousel('.owl-carousel-bestseller');
            app.testCarousel('.owl-carousel-default');
            app.mustHaveCarousel.init('.owl-carousel-must-have')
        }
    };

    app.init();
})(jQuery);