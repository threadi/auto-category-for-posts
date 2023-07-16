<?php
/**
 * Run uninstall tasks for this Plugin, e.g.:
 * - remove options-entry
 *
 * @package auto-category-for-posts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if file is not called during uninstall-process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// delete the options we set.
delete_option( 'auto_category_id' );
delete_option( 'auto_category_db_version' );
