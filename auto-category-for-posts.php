<?php
/**
 * Plugin Name:       Auto Category for Posts
 * Description:       Automatically add a default-category to each new post before it is first saved.
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Version:           1.0.2
 * Author:            Thomas Zwirner
 * Author URI:		  https://www.thomaszwirner.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-category-for-posts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const AUTOCATEGORY_OPTIONNAME = 'default_category';
const AUTOCATEGORY_VERSION = '1.0.2';

register_activation_hook( __FILE__, 'auto_category_activation');
register_deactivation_hook(__FILE__, 'auto_category_deactivation');

/**
 * Initialize the plugin.
 *
 * @return void
 */
function auto_category_init() {
    load_plugin_textdomain( 'auto-category-for-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'auto_category_init' );

/**
 * Check for necessary steps after plugin-updates.
 *
 * @return void
 */
function auto_category_update_check() {
    if( is_admin() && is_user_logged_in() ) {
        if (get_option('auto_category_db_version') != AUTOCATEGORY_VERSION) {
            // remove old option used in version 1.0.0
            delete_option('auto_category_id');
        }
        update_option('auto_category_db_version', AUTOCATEGORY_VERSION);
    }
}
add_action( 'plugins_loaded', 'auto_category_update_check' );

/**
 * Set the default category on save_post.
 * This way the category is already set before the editor outputs this assignment.
 * According to wp-standard this is only visible after saving by the user.
 *
 * @param $post_id
 * @param $post
 * @param $update
 * @return void
 */
function auto_category_save_post( $post_id, $post, $update ) {
    // Only for new posts
    if ( $update ){
        return;
    }

    // Only for post
    if ( 'post' !== $post->post_type ) {
        return;
    }

    // Get the default setting for category
    $defaultCatId = get_option(AUTOCATEGORY_OPTIONNAME, true);

    // Get the default term by its id
    $term = get_term_by( 'id', $defaultCatId, 'category' );

    // Save the default term to the post
    if( !empty($term) ) {
        wp_set_post_terms($post_id, $term->term_id, 'category');
    }
}
add_action( 'save_post', 'auto_category_save_post', 10, 3 );

/**
 * Add action to set a category as default-category in taxonomy-table.
 *
 * @param $actions
 * @param $tag
 * @return mixed
 */
function auto_category_add_term_action( $actions, $tag ){
    if( $tag->taxonomy == 'category' ):
        $tax = get_taxonomy('category');
        if( current_user_can( $tax->cap->manage_terms) ) {
            $text = __('Set as default', 'auto-category-for-posts');
            $value = '';
            if ($tag->term_id === (int)get_option(AUTOCATEGORY_OPTIONNAME)) {
                $text = __('Default category', 'auto-category-for-posts');
                $value = ' class="default_category"';
            }
            $actions['auto_category'] = '<a href="#"' . $value . ' data-termid="' . $tag->term_id . '" data-nonce="'.wp_create_nonce( 'auto_category_change_state' ).'">' . $text . '</a>';
        }
    endif;
    return $actions;
}
add_filter( 'tag_row_actions', 'auto_category_add_term_action', 10, 2 );

/**
 * Actions on plugin activation.
 *
 * @return void
 */
function auto_category_activation() {
    // nothing
}

/**
 * Action on plugin deactivation.
 *
 * @return void
 */
function auto_category_deactivation() {
    // nothing
}

/**
 * Include js-file in admin.
 *
 * @return void
 */
function auto_category_load_ajax() {
    // only include the file on category-table
    global $current_screen;
    if ( $current_screen->id != 'edit-category' )
        return;

    wp_enqueue_style( 'admin_css_foo', plugin_dir_url(__FILE__) . '/admin/style.css', false, '1.0.0' );
    wp_register_script( 'auto_category_handle', plugins_url( '/admin/js.js', __FILE__ ), array( 'wp-i18n' ) );
    wp_enqueue_script( 'auto_category_handle', plugin_dir_url(__FILE__) . '/admin/js.js', false, '1.0.0' );
    wp_set_script_translations( 'auto_category_handle', 'auto-category-for-posts', plugin_dir_path( __FILE__ ) . 'languages' );
}
add_action( 'admin_enqueue_scripts', 'auto_category_load_ajax' );

/**
 * Set new auto category by AJAX.
 *
 * @return void
 */
function auto_category_ajax(){
    $result = [
        'error' => __('Error on saving new settings for default category.', 'auto-category-for-posts')
    ];
    if( is_user_logged_in() ) {
        $tax = get_taxonomy('category');
        if( !empty($_GET['nonce'])
            && wp_verify_nonce( $_GET['nonce'], 'auto_category_change_state' )
            && current_user_can( $tax->cap->manage_terms)
            && !empty($_GET['term_id'])
        ) {
            // get new term_id-value from request
            $term_id = absint(sanitize_text_field($_GET['term_id']));

            // create array for the resulting data
            $result = [];
            // -> set result to false
            $result['result'] = false;
            // -> get old default term_id
            $result['old_default_category_id'] = (int)get_option(AUTOCATEGORY_OPTIONNAME, true);
            // -> initialize new cat id
            $result['new_default_category_id'] = 0;
            if( $term_id > 0 ) {
                // check if given id exists as category
                if( category_exists($term_id) ) {

                    // return the new term_id
                    $result['new_default_category_id'] = $term_id;

                    // update term_id
                    $result['result'] = update_option(AUTOCATEGORY_OPTIONNAME, $term_id);
                }
                else {
                    $result['error'] = __('Given category does not exists.', 'auto-category-for-posts');
                }
            }
            else {
                $result['error'] = __('No new category-id given.', 'auto-category-for-posts');
            }
        }
    }
    // return the result
    echo json_encode($result);
    exit;
}
add_action( 'wp_ajax_auto_category_change_state', 'auto_category_ajax' );