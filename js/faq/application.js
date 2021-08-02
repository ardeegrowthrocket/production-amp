(function($){

    var app = {
        'init': function () {

            let pageType = $('#page_type');
            if(pageType.length){

                let requestPartName = function (pageType) {
                    $.ajax({
                        dataType: "html",
                        url: grFaqUrl,
                        data: { page_type : pageType, page_id: pageId},
                        beforeSend: function () {

                        }
                    }).done(function (data) {
                        $('#parent').html(data);
                    });
                };

                pageType.on('change',function () {
                    let $this = $(this);
                    requestPartName($this.val());
                });

                if(pageType.val()){
                    requestPartName(pageType.val());
                }
            }
        }
    };

    app.init();
})(jQuery);