jQuery.noConflict();
(function($){
    $(document).ready(function(){
        var data = [
            {
                name: 'node1',
                children: [
                    { name: 'child1' },
                    { name: 'child2' }
                ]
            },
            {
                name: 'node2',
                children: [
                    { name: 'child3' }
                ]
            }
        ];
        // $('#web-attr-configuration-container').tree({
        //     data: data
        // });
    });
})(jQuery);