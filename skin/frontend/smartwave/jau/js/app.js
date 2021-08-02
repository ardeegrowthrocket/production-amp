(function($){
    "use strict";
    var bp = {
        xlarge: 1170,
        large: 996,
        medium: 768,
    };
    function useFluidContainer(){
        $('#featured-product-container').removeClass('container');
        $('#featured-product-container').addClass('container-fluid');
    }
    function useFixedContainer(){
        $('#featured-product-container').removeClass('container-fluid');
        $('#featured-product-container').addClass('container');
    }
    $(document).ready(function(){
        enquire.register('(max-width: '+bp.xlarge+'px)',{
            setup: function(){
                var windowWidth = $(window).width();
                if(windowWidth > bp.xlarge){
                    useFixedContainer();
                }else{
                    useFluidContainer();
                }
            },
            match: function(){
                useFluidContainer();
                console.log("Matches");
            },
            unmatch: function(){
                useFixedContainer();
                console.log("unmatches");
            }
          
        });

        function Banner(){

            this.img = $('#spp-banner .responsimg');
            this.container = $('div#spp-banner');
            this.text = $('.spp-banner-text');


            this.getImgHeight = function(){
                return $(this.img).height();
            }
            this.adjustContainerHeight = function(){
                var height = this.getImgHeight();
                $(this.container).css('height',height-1);
                this.adjustTextPosition();
            }
            this.adjustTextPosition = function(){
                var offset = $(this.text).offset();
                var imageHeight = this.getImgHeight();
                var elementHeight = $(this.text).height();
                var desiredHeight = 180;

                var excess = imageHeight - elementHeight;

                if( excess < desiredHeight){
                    desiredHeight = 50;
                }
                if( excess <= 245){
                    desiredHeight = 125;
                }
                if( excess < 200 ){
                    desiredHeight = 50;
                }

                $(this.text).css('top', desiredHeight);
                console.log(excess);
            }
        }
        var banner = new Banner();
        var adjust = function(){
            var windowWidth = $(window).width();
            if(windowWidth > bp.medium) {
                banner.adjustContainerHeight();
            }
        };
        enquire.register('(max-width: '+bp.xlarge+'px)',{
            setup: function(){
                var windowWidth = $(window).width();
                if(windowWidth > bp.medium){
                    banner.adjustContainerHeight();
                    $(window).on('resize',adjust);
                }else{
                    $(window).off('resize', window, adjust);
                }

            },
            unmatch: function(){
                banner.adjustContainerHeight();
                $(window).on('resize',adjust);
            },
            match: function(){
                $(window).off('resize', window, adjust);
            }
        });
        setInterval(function(){
            adjust();
            console.log("Adjust");
        }, 2000);

        jQuery(window).load(function() {
            if (/Mobi/.test(navigator.userAgent)) {
             jQuery("#slideshow").after(jQuery(".top-banner"));
            }
        });        
    });
})(jQuery);