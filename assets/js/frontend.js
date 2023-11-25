jQuery(document).ready(function($){
    "use scrict";
    
    $('.ta-tab-item').click(function(){

        let layout = $(this).closest('.ta-filter-tabs').attr('data-layout');
        $('.ta-tab-item').removeClass('active-tab');
        $(this).addClass('active-tab');
        let cWrap = $(this).closest('.filter-tab-wraper');
        let catSlug = $(this).attr('data-id');
        let loaded = false;
        if( catSlug ){
            
            $('.ta-filter-content').each(function(){
                if( $(this).hasClass('filter-content-'+catSlug ) ){
                    loaded = true;
                }
            });


            if( !loaded ){
                ajaxurl = zegal_extension_frontend_script.ajax_url;
                var data = {
                    'action': 'zegal_extension_filter_posts_by_category',
                    '_wpnonce': zegal_extension_frontend_script.ajax_nonce,
                    'catSlug': catSlug,
                };

                $.post(ajaxurl, data, function (response) {

                    console.log( response );
                    if( response.success ){
                        $(cWrap).find('.ta-filter-content').hide();
                        $(cWrap).find('.ta-filter-contents-wrap').append(response.data.content);
                        $(cWrap).find('.filter-content-'+catSlug).show();
                    }

                });

            }else{
                $(cWrap).find('.ta-filter-content').hide();
                $(cWrap).find('.filter-content-'+catSlug).show();
            }

        }
    });

});