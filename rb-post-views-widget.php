<?php
/**
 * Plugin Name:       RB Post Views Widget
 * Plugin URI:        https://github.com/BashirRased/wp-plugin-rb-post-views-widget
 * Description:       Count and display the most viewed posts in a widget.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * PHP Version:       8.2
 * Author:            Bashir Rased
 * Author URI:        https://bashir-rased.com/
 * Text Domain:       rb-post-views-widget
 * Domain Path:       /languages
 *
 * @package RB_Plugins
 * @subpackage RB_Post_Views_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin text domain.
 *
 * @return void
 */
function rbpvw_load_textdomain() {
	load_plugin_textdomain( 'rb-post-views-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'rbpvw_load_textdomain' );

/**
 * Redirect to Widgets page after activation.
 *
 * @param string $plugin Activated plugin name.
 * @return void
 */
function rbpvw_redirect_after_activation( $plugin ) {
	if ( plugin_basename( __FILE__ ) === $plugin ) {
		wp_safe_redirect( admin_url( 'widgets.php' ) );
		exit;
	}
}
add_action( 'activated_plugin', 'rbpvw_redirect_after_activation' );

/**
 * Add settings link in plugin list.
 *
 * @param array $links Existing links.
 * @return array
 */
function rbpvw_plugin_action_links( $links ) {

	$links[] = sprintf(
		'<a href="%s" style="color:#b32d2e;">%s</a>',
		esc_url( admin_url( 'widgets.php' ) ),
		esc_html__( 'Settings', 'rb-post-views-widget' )
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rbpvw_plugin_action_links' );

/**
 * Add GitHub link in plugin row meta.
 *
 * @param array  $links  Meta links.
 * @param string $plugin Plugin file path.
 *
 * @return array
 */
function rbpvw_plugin_row_meta( $links, $plugin ) {

	if ( plugin_basename( __FILE__ ) === $plugin ) {
		$links[] = sprintf(
			'<a href="%s" style="color:#b32d2e;">%s</a>',
			esc_url( 'https://github.com/BashirRased/wp-plugin-rb-post-views-widget' ),
			esc_html__( 'Fork on GitHub', 'rb-post-views-widget' )
		);
	}

	return $links;
}
add_filter( 'plugin_row_meta', 'rbpvw_plugin_row_meta', 10, 2 );

/**
 * Count post views.
 *
 * @return void
 */
function rbpvw_count_post_views() {

	if ( is_singular() ) {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$meta_key = 'rbpvw_count';
		$count    = (int) get_post_meta( $post_id, $meta_key, true );
		++$count;

		update_post_meta( $post_id, $meta_key, absint( $count ) );
	}
}
add_action( 'wp_head', 'rbpvw_count_post_views' );

/**
 * Include the widget class.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-rbpvw-widget.php';

/**
 * Register the widget.
 *
 * @return void
 */
function rbpvw_register_widget() {
	register_widget( 'RBPVW_Widget' );
}
add_action( 'widgets_init', 'rbpvw_register_widget' );
