const { __ } = wp.i18n;

jQuery(document).ready(function(){
    jQuery(".auto_category a").click(function(){
        change_publish_states(jQuery(this));
    });
    jQuery(".auto_category a.default_category").parents("tr").addClass('default_category');
});

function change_publish_states(el){
    jQuery.getJSON(ajaxurl,
        {
            term_id: el.data("termid"),
            action: "auto_category_change_state"
        },
        function(data) {
            if (data.error){
                alert(data.error);
            }else{
                if( data.result ) {
                    var oldDefault = jQuery("#tag-" + data.old_default_category_id + " .auto_category > a.default_category");
                    oldDefault.parents('tr').removeClass('default_category');
                    oldDefault.removeClass('default_category').text(__('Set as default', 'auto-category-for-posts'));
                    var newDefault = jQuery("#tag-" + data.new_default_category_id + " .auto_category > a");
                    newDefault.addClass('default_category').text(__('Default category', 'auto-category-for-posts'));
                    newDefault.parents('tr').addClass('default_category');
                }
            }
        });
}