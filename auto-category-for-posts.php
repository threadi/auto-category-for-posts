<?php
/**
 * Plugin Name:       Auto Category for Posts
 * Description:       Automatically add a default-category to each new post before it is first saved.
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Version:           @@VersionNumber@@
 * Author:            Thomas Zwirner
 * Author URI:        https://www.thomaszwirner.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-category-for-posts
 *
 * @package auto-category-for-posts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// set option name.
const AUTOCATEGORY_OPTIONNAME = 'default_category';

// set version of this plugin.
const AUTOCATEGORY_VERSION = '@@VersionNumber@@';

/**
 * Check for necessary steps after plugin-updates.
 *
 * @return void
 * @noinspection PhpUnused
 */
function auto_category_update_check(): void {
	if ( is_admin() && is_user_logged_in() ) {
		if ( AUTOCATEGORY_VERSION !== get_option( 'auto_category_db_version' ) ) {
			// remove old option used in version 1.0.0.
			delete_option( 'auto_category_id' );
		}
		update_option( 'auto_category_db_version', AUTOCATEGORY_VERSION );
	}
}
add_action( 'plugins_loaded', 'auto_category_update_check' );

/**
 * Set the default category on save_post.
 * This way the category is already set before the editor outputs this assignment.
 * According to wp-standard this is only visible after saving by the user.
 *
 * @param int     $post_id The Post-ID.
 * @param WP_Post $post The Post-object.
 * @param bool    $update Whether the post has been updated (true) or added/inserts (false).
 * @return void
 */
function auto_category_save_post( int $post_id, WP_Post $post, bool $update ): void {
	// Only for new posts.
	if ( $update ) {
		return;
	}

	// Only for post.
	if ( 'post' !== $post->post_type ) {
		return;
	}

	// Get the default setting for category.
	$default_cat_id = get_option( AUTOCATEGORY_OPTIONNAME, true );

	// Get the default term by its id.
	$term = get_term_by( 'id', $default_cat_id, 'category' );

	// Save the default term to the post.
	if ( ! empty( $term ) ) {
		wp_set_post_terms( $post_id, $term->term_id, 'category' );
	}
}
add_action( 'save_post', 'auto_category_save_post', 10, 3 );

/**
 * Add action to set a category as default-category in taxonomy-table.
 *
 * @param array   $actions List of actions.
 * @param WP_Term $tag The term.
 * @return array
 */
function auto_category_add_term_action( array $actions, WP_Term $tag ): array {
    // bail if it is not the category taxonomy.
	if ( 'category' !== $tag->taxonomy ) {
        return $actions;
    }

    // get taxonomy as object.
    $tax = get_taxonomy('category');
    if (current_user_can($tax->cap->manage_terms)) {
        $text = __('Set as default', 'auto-category-for-posts');
        $value = '';
        if (absint(get_option(AUTOCATEGORY_OPTIONNAME)) === $tag->term_id) {
            $text = __('Default category', 'auto-category-for-posts');
            $value = ' class="default_category"';
        }
        $actions['auto_category'] = '<a href="#"' . $value . ' data-termid="' . $tag->term_id . '" data-nonce="' . wp_create_nonce('auto_category_change_state') . '">' . esc_html( $text ) . '</a>';
    }
	return $actions;
}
add_filter( 'tag_row_actions', 'auto_category_add_term_action', 10, 2 );

/**
 * Include custom css- and js-files in admin.
 *
 * @return void
 * @noinspection PhpUnused
 */
function auto_category_load_ajax(): void {
	// only include the file on category-table.
	global $current_screen;
	if ( 'edit-category' !== $current_screen->id ) {
		return;
	}

	// add custom style.
	wp_enqueue_style(
		'auto_category',
		plugin_dir_url( __FILE__ ) . '/admin/style.css',
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . 'admin/style.css' )
	);

	// add custom script.
	wp_register_script(
		'auto_category',
		plugins_url( '/admin/js.js', __FILE__ ),
		array( 'wp-i18n' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'admin/js.js' ),
		true
	);
	wp_enqueue_script( 'auto_category' );

	// set translations.
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'auto_category', 'auto-category-for-posts', plugin_dir_path( __FILE__ ) . 'languages' );
	}
}
add_action( 'admin_enqueue_scripts', 'auto_category_load_ajax' );

/**
 * Set new auto category by AJAX.
 *
 * @return void
 */
function auto_category_ajax(): void {
	$result = array(
		'error' => __( 'Error on saving new settings for default category.', 'auto-category-for-posts' ),
	);
	if ( is_user_logged_in() ) {
		$tax     = get_taxonomy( 'category' );
		$nonce   = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		$term_id = isset( $_GET['term_id'] ) ? absint( $_GET['term_id'] ) : 0;
		if ( ! empty( $nonce )
			&& wp_verify_nonce( $nonce, 'auto_category_change_state' )
			&& current_user_can( $tax->cap->manage_terms )
			&& ! empty( $term_id )
		) {
			// get new term_id-value from request.
			$term_id = absint( sanitize_text_field( $term_id ) );

			// create array for the resulting data.
			$result = array();
			// -> set result to false.
			$result['result'] = false;
			// -> get old default term_id.
			$result['old_default_category_id'] = absint( get_option( AUTOCATEGORY_OPTIONNAME, true ) );
			// -> initialize new cat id.
			$result['new_default_category_id'] = 0;
			if ( $term_id > 0 ) {
				// check if given id exists as category.
				if ( category_exists( $term_id ) ) {

					// return the new term_id.
					$result['new_default_category_id'] = $term_id;

					// update term_id.
					$result['result'] = update_option( AUTOCATEGORY_OPTIONNAME, $term_id );
				} else {
					$result['error'] = __( 'Given category does not exists.', 'auto-category-for-posts' );
				}
			} else {
				$result['error'] = __( 'No new category-id given.', 'auto-category-for-posts' );
			}
		}
	}
	// return the result.
	wp_send_json( $result );
}
add_action( 'wp_ajax_auto_category_change_state', 'auto_category_ajax' );
