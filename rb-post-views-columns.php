<?php
/**
 * Plugin Name:       RB Post Views Columns
 * Plugin URI:        https://github.com/BashirRased/wp-plugin-rb-post-views-columns
 * Description:       Display post view counts in WordPress admin columns.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * PHP Version:       8.2
 * Author:            Bashir Rased
 * Author URI:        https://bashir-rased.com/
 * Text Domain:       rb-post-views-columns
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package    RB_Plugins
 * @subpackage RB_Post_Views_Columns
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin textdomain.
 */
function rbpvc_load_textdomain() {
	load_plugin_textdomain(
		'rb-post-views-columns',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'rbpvc_load_textdomain' );

/**
 * Add a GitHub "Fork on GitHub" link to the plugin row meta.
 *
 * @param array  $links  An array of existing plugin row meta links.
 * @param string $plugin The plugin file path relative to the plugins directory.
 *
 * @return array Modified array including the GitHub link.
 */
function rbpvc_plugin_row_meta( $links, $plugin ) {

	if ( plugin_basename( __FILE__ ) === $plugin ) {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( 'https://github.com/BashirRased/wp-plugin-rb-post-views-columns' ),
			esc_html__( 'Fork on GitHub', 'rb-post-views-columns' )
		);
	}

	return $links;
}
add_filter( 'plugin_row_meta', 'rbpvc_plugin_row_meta', 10, 2 );

/**
 * Count post views when viewing a single post.
 */
function rbpvc_track_post_views() {
	if ( is_singular() ) {
		$meta_key   = 'rbpvc_post_view';
		$post_id    = get_the_ID();
		$view_count = (int) get_post_meta( $post_id, $meta_key, true );

		update_post_meta( $post_id, $meta_key, $view_count + 1 );
	}
}
add_action( 'wp_head', 'rbpvc_track_post_views' );

/**
 * Add a custom "Post Views Count" column to supported post types.
 *
 * @param array $columns Existing columns in the posts list table.
 *
 * @return array Modified columns including the new views column.
 */
function rbpvc_add_custom_columns( $columns ) {
	$columns['rbpvc_views_count'] = esc_html__( 'Post Views Count', 'rb-post-views-columns' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'rbpvc_add_custom_columns', 20 );
add_filter( 'manage_page_posts_columns', 'rbpvc_add_custom_columns', 20 );
add_filter( 'manage_product_posts_columns', 'rbpvc_add_custom_columns', 20 );

/**
 * Populate the custom "Post Views Count" column with view data.
 *
 * @param string $column  The name of the current column.
 * @param int    $post_id The ID of the current post.
 *
 * @return void
 */
function rbpvc_custom_columns_value( $column, $post_id ) {
	if ( 'rbpvc_views_count' === $column ) {
		$count = get_post_meta( $post_id, 'rbpvc_post_view', true );
		echo esc_html( $count ? $count : 0 );
	}
}
add_action( 'manage_posts_custom_column', 'rbpvc_custom_columns_value', 10, 2 );
add_action( 'manage_pages_custom_column', 'rbpvc_custom_columns_value', 10, 2 );

/**
 * Register the "Post Views Count" column as sortable.
 *
 * @param array $columns An array of sortable columns.
 *
 * @return array Modified array including the sortable views column.
 */
function rbpvc_sortable_column( $columns ) {
	$columns['rbpvc_views_count'] = 'rbpvc_post_view';
	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'rbpvc_sortable_column' );

/**
 * Filter dropdown for posts with/without views.
 *
 * @return void
 */
function rbpvc_filter_dropdown() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$selected = isset( $_GET['RBPVC'] ) ? sanitize_text_field( wp_unslash( $_GET['RBPVC'] ) ) : '';

	$options = array(
		0 => __( 'All Posts', 'rb-post-views-columns' ),
		1 => __( 'View Posts', 'rb-post-views-columns' ),
		2 => __( 'No View Posts', 'rb-post-views-columns' ),
	);
	?>
	<select name="RBPVC">
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'rbpvc_filter_dropdown' );

/**
 * Modify the main admin query to apply custom filtering and sorting
 * for the "Post Views Count" column.
 *
 * @param WP_Query $query The current WP_Query instance (admin list table).
 *
 * @return void
 */
function rbpvc_filter_query( $query ) {

	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Read-only GET parameter – safe, does not require nonce verification.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$filter = isset( $_GET['RBPVC'] ) ? sanitize_text_field( wp_unslash( $_GET['RBPVC'] ) ) : '';

	// Filter posts based on view meta existence.
	if ( '1' === $filter ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => 'rbpvc_post_view',
					'compare' => 'EXISTS',
				),
			)
		);
	} elseif ( '2' === $filter ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => 'rbpvc_post_view',
					'compare' => 'NOT EXISTS',
				),
			)
		);
	}

	// Ensure proper numeric sorting.
	if ( 'rbpvc_post_view' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'rbpvc_post_view' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'rbpvc_filter_query' );
