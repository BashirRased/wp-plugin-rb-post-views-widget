<?php
/**
 * RB Post Views Widget
 * @package 		  WordPress
 * @subpackage 		  RB Free Plugin
 * @author            Bashir Rased
 * @copyright         2022 Bashir Rased
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       RB Post Views Widget
 * Plugin URI:        https://github.com/BashirRased/wp-plugin-rb-post-views-widget
 * Description:       RB Post Views Widget plugin use for your posts visit count.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Tested up to:      5.9
 * Requires PHP:      5.6
 * Author:            Bashir Rased
 * Author URI:        https://profiles.wordpress.org/bashirrased2017/
 * Text Domain:       rb-post-views-widget
 * Domain Path: 	  /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/BashirRased/wp-plugin-rb-post-views-widget
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Text domain loaded
function rb_plugin_textdomain() {
    load_plugin_textdomain('rb-post-views-widget', false, dirname(plugin_basename(__FILE__)).'/languages'); 
}
add_action('plugins_loaded', 'rb_plugin_textdomain');

// Redirect Page Link Activated
add_action('activated_plugin', function ($plugin) {
	if (plugin_basename(__FILE__) == $plugin) {
		wp_redirect(admin_url('widgets.php'));
		die();
	}
});

// Settings Page Link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
	$link = sprintf("<a href='%s' style='color:#b32d2e;'>%s</a>", admin_url('widgets.php'), __('Settings', 'rb-post-views-widget'));
	array_push($links, $link);
	return $links;
});

// Github Page Link
add_filter('plugin_row_meta', function ($links, $plugin) {
	if (plugin_basename(__FILE__) == $plugin) {
		$link = sprintf("<a href='%s' style='color:#b32d2e;'>%s</a>", esc_url('https://github.com/BashirRased/wp-plugin-rb-post-views-widget'), __('Fork on Github', 'rb-post-views-widget'));
		array_push($links, $link);
	}
	return $links;
}, 10, 2);

// RB Post Views Meta Key
function rb_post_views_count() {	
	if(is_singular()){
		$rb_post_view_meta = 'rb_post_views_count';
		$rb_post_count = get_post_meta(get_the_ID(), $rb_post_view_meta, true);
		$rb_post_count++;
		update_post_meta(get_the_ID(), $rb_post_view_meta, $rb_post_count);
	}
}
add_action('wp_head', 'rb_post_views_count');


/**
 * Widget API: RB_Post_Views_Widget class
 *
 * @package WordPress
 * @subpackage RB Free Plugin
 * @since 1.0.0
 */

/**
 * Core class used to implement RB Post Views Widget.
 *
 * @since 1.0.0
 *
 * @see WP_Widget
 */
class RB_Post_Views_Widget extends WP_Widget {

	/**
	 * Sets up a new RB Post Views Widget.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$widget_options = array(
		'name' => __('RB Post Views Widget','rb-post-views-widget'),
		'classname' => 'widget_rb_post_views',
		'description' => __('RB Post Views Widget plugin is counting every post visiting times.','rb-post-views-widget'),
		'customize_selective_refresh' => true,
		'show_instance_in_rest'       => true,
		);
		parent::__construct( 'RB_Post_Views_Widget', __('RB Post Views Widget','rb-post-views-widget'), $widget_options);
	}

	/**
	 * Outputs the content for the current RB Post Views Widget.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title',
	 * 'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current RB Post Views Widget.
	 */
	public function widget($args, $instance) {
		$display_total_posts = absint($instance['total']);
		$post_type = $instance['post_type'];
		
		$posts_args = array(
		'post_type' => $post_type,
		'posts_per_page' => $display_total_posts,
		'meta_key' => 'rb_post_views_count',
		'orderby' => 'meta_value_num',
		'order' => 'DESC'
		);
		
		$posts_query = new WP_Query($posts_args);
		
		echo $args['before_widget'];
		if(!empty($instance['title'])):
		
		echo $args['before_title'].apply_filters('widget_title', $instance['title']).$args['after_title'];
		endif;
		

		if($posts_query->have_posts()): ?>
		<ul>

			<?php
			while($posts_query->have_posts()): $posts_query->the_post();	
			
			$rb_post_view_meta = 'rb_post_views_count';
			$rb_post_count = get_post_meta(get_the_ID(), $rb_post_view_meta, true);
			?>

			<li>

				<a href="<?php echo esc_url(get_permalink()); ?>" class="rb-post-view-link">
				<?php esc_html_e(get_the_title(),'rb-post-views-widget'); ?>
				</a>

				<span class="rb-post-view-num">
					<?php
					printf(
						/* translators: %s: RB Post Count. */
						esc_html('(%s)','rb-post-views-widget'),
						$rb_post_count
					);
					?>
				</span>

			</li>		

			<?php endwhile;	?>

		</ul>
		<?php endif;

		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Navigation Menu widget instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 * RB_Post_Views_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = (!empty($new_instance['title']) ? sanitize_text_field($new_instance['title']):'');
		$instance['post_type'] = (!empty($new_instance['post_type']) ? strip_tags($new_instance['post_type']):'');
		$instance['total'] = (!empty($new_instance['total']) ? (int)$new_instance['total']:0);

		return $instance;		
	}

	/**
	 * Outputs the settings form for the RB Post Views Widget.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {

		$title = isset($instance['title'])? $instance['title']:esc_html('Most Views Post','rb-post-views-widget');
		$post_type = isset($instance['post_type'])? strip_tags($instance['post_type']):'post';
		$display_total_posts = isset($instance['total'])? $instance['total']:5;

		// Get post types
		$post_type_list = array(
			'public' => true,
		);
		$post_types = get_post_types($post_type_list, 'objects');
		unset($post_types['attachment']);
		?>
		
		<!-- Widget Form Title Field -->
		<p>
			
			<!-- Widget Form Title Field Label -->
			<label for="<?php echo esc_attr($this->get_field_id("title")); ?>">
				<?php esc_html_e('Title:','rb-post-views-widget'); ?>
			</label>
		
			<!-- Widget Form Title Field Input -->
			<input type="text" class="widefat" id="<?php echo esc_attr($this->get_field_id("title")); ?>" name="<?php echo esc_attr($this->get_field_name("title")); ?>" value ="<?php echo esc_attr(($title)); ?>">

		</p>
		
		<!-- Widget Form Post Type Field -->
		<p>
			
			<!-- Widget Form Post Type Field Label -->
			<label for="<?php echo esc_attr($this->get_field_id("post_type"));?>">
				<?php esc_html_e('Post Type:','rb-post-views-widget'); ?>
			</label>
			
			<!-- Widget Form Post Type Field Select -->
			<select id="<?php echo esc_attr($this->get_field_id("post_type")); ?>" name="<?php echo esc_attr($this->get_field_name("post_type")); ?>">

			<?php
			foreach ($post_types as $post_type_obj):

			$post_type_obj_labels = get_post_type_labels($post_type_obj);
			$post_type_id = $post_type_obj->name;
			$post_type_name = $post_type_obj_labels->name;

			if($post_type == $post_type_id){
				$post_type_select = sprintf(
					/* translators: %s: RB Post Type Selected. */
					'selected="%s"',
					esc_attr('selected', 'rb-post-views-widget')
				);
			}else{
				$post_type_select = '';
			}
			?>

				<!-- Widget Form Post Type Field Option -->
				<option value="<?php echo esc_attr($post_type_id); ?>" <?php echo $post_type_select; ?>>
					<?php esc_html_e($post_type_name,'rb-post-views-widget'); ?>
				</option>

			<?php endforeach; ?>

			</select>

		</p>
		
		<!-- Widget Form Display Total Posts Field -->
		<p>
			
			<!-- Widget Form Display Total Posts Field Label -->
			<label for="<?php echo esc_attr($this->get_field_id("total")); ?>">
				<?php esc_html_e('Display Total Posts:','rb-post-views-widget'); ?>
			</label>
		
			<!-- Widget Form Display Total Posts Field Input -->
			<input type="number" class="widefat" id="<?php echo esc_attr($this->get_field_id("total")); ?>" name="<?php echo esc_attr($this->get_field_name("total")); ?>" value ="<?php echo esc_attr(($display_total_posts)); ?>">
			
		</p>

		<?php
	}
}

// RB Post Views Widget Register
function rb_post_views_widget() {
	register_widget('RB_Post_Views_Widget');
}
add_action('widgets_init', 'rb_post_views_widget');