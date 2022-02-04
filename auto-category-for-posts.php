<?php
/**
 * Plugin Name:       Auto Category for Posts
 * Description:       Automatically add a default-category to each new post.
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Thomas Zwirner
 * Author URI:		  https://www.thomaszwirner.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-category-for-posts
 *
 * @package           create-block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const AUTOCATEGORY_OPTIONNAME = 'auto_category_id';

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
 * Add category on save_post.
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
    if($tag->taxonomy == 'category'):
        $text = __('Set as default', 'auto-category-for-posts');
        $value = '';
        if( $tag->term_id == get_option( AUTOCATEGORY_OPTIONNAME, true ) ) {
            $text = __('Default category', 'auto-category-for-posts');
            $value = ' class="default_category"';
        }
        $actions['auto_category'] = '<a href="#"'.$value.' data-termid="'.$tag->term_id.'">'.$text.'</a>';
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
    // add option for default category if not set
    add_option(AUTOCATEGORY_OPTIONNAME, 0, '', true);
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
    // get old default term_id
    $result['old_default_category_id'] = get_option( AUTOCATEGORY_OPTIONNAME, true );

    // return the new term_id
    $result['new_default_category_id'] = $_GET['term_id'];

    // update term_id
    $result['result'] = update_option( AUTOCATEGORY_OPTIONNAME, $_GET['term_id'] );
    echo json_encode($result);
    exit();
}
add_action( 'wp_ajax_auto_category_change_state', 'auto_category_ajax' );