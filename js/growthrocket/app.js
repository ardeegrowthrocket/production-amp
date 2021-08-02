/**
 * Make sure jQuery library is loaded first before this.
 */
(function($j){
    var ProductListingManager = {
        init: function (){
            this.list = $j('.products-grid');
            this.children = $j('.products-grid').children('li');
            if(this.list.length > 0){
                this.rows = [];
                var tempRows = [];
                this.children.each(function(index,el){
                    if($j(el).css('clear') !='none' && index !=0){
                        this.rows.push(tempRows);
                    }
                    tempRows.push(el);
                    if(this.children.length == index + 1){
                        this.rows.push(tempRows);
                    }
                }.bind(this));
            }
        },
        getList: function (){
            return this.list;
        },
        getGridRows: function() {
            return this.rows;
        },
        resize: function(){
            var tallestItemArea = 0;
            var tallestDetailsArea=0;
            var elem = null;
            var rowList = this.getGridRows();
            $j.each(rowList,function(index, element){
                //Resets
                $j.each(rowList,function(){
                    $j(this).find('.details-area').css('height','');
                });
                var itemAreaHeight = $j(this).find('.item-area').height();
                var detailsAreaHeight = $j(this).find('.details-area').height();

                if(itemAreaHeight > tallestItemArea){
                    tallestItemArea = itemAreaHeight;
                }
                if(detailsAreaHeight > tallestDetailsArea){
                    tallestDetailsArea = detailsAreaHeight;
                }
                $j.each(rowList, function(){
                    // $j(this).find('.item-area').css('height',tallestDetailsArea + 'px');
                    $j(this).find('.details-area').css('height',(tallestDetailsArea + 62  )+ 'px');
                });
            });
            return 0;
        },
        reset:function(){
            var rowList = this.getGridRows();
            $j.each(rowList, function(){
                $j(this).find('.details-area').css('height','');
            });
        }
    };
    ProductListingManager.init();
    ProductListingManager.resize();
    window.TEST = ProductListingManager;

    var timer;

    $j(window).resize(function(e){
        clearTimeout(timer);
        timer = setTimeout(function(){
            $j(window).trigger('delayed-resize',e);
        },250);
    });

    $j(window).on('delayed-resize',function(e,evt){
        console.log("RESIZE");
        ProductListingManager.resize();
    });
    $j(window).load(function(){
        ProductListingManager.resize();
    });

})(jQuery);