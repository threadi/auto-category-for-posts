const { __ } = wp.i18n;

jQuery(document).ready(function(){
    jQuery(".auto_category a.default_category").parents("tr").addClass('default_category');
    jQuery( document ).on( "ajaxComplete", function( event, xhr, settings ) {
        autocategory_set_event();
    });
    autocategory_set_event();
});

/**
 * Set event for table.
 */
function autocategory_set_event() {
    jQuery(".auto_category a").click(function(){
        autocategory_change_publish_states(jQuery(this));
    });
}

/**
 * Change the states.
 *
 * @param el
 */
function autocategory_change_publish_states(el){
    jQuery.getJSON(ajaxurl,
        {
            term_id: el.data("termid"),
            action: "auto_category_change_state",
            nonce: el.data("nonce")
        },
        function(data) {
            if (data.error){
                alert(data.error);
            } else {
                if( data.result ) {
                    let oldDefault = jQuery("#tag-" + data.old_default_category_id + " .auto_category > a.default_category");
                    oldDefault.parents('tr').removeClass('default_category');
                    oldDefault.removeClass('default_category').text(__('Set as default', 'auto-category-for-posts'));
                    let newDefault = jQuery("#tag-" + data.new_default_category_id + " .auto_category > a");
                    newDefault.addClass('default_category').text(__('Default category', 'auto-category-for-posts'));
                    newDefault.parents('tr').addClass('default_category');
                }
            }
        });
}