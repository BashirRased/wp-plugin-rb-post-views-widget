<?php
/**
 * RB Post Views Widget Class.
 *
 * @package RB_Plugins
 * @subpackage RB_Post_Views_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core widget class for displaying most viewed posts.
 */
class RBPVW_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$options = array(
			'classname'                   => 'widget_rbpvw',
			'description'                 => esc_html__( 'Display most viewed posts.', 'rb-post-views-widget' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);

		parent::__construct(
			'RBPVW_Widget',
			esc_html__( 'RB Post Views Widget', 'rb-post-views-widget' ),
			$options
		);
	}

	/**
	 * Output widget content.
	 *
	 * @param array $args Display arguments.
	 * @param array $instance Widget instance settings.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		$title     = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$post_type = ! empty( $instance['post_type'] ) ? sanitize_key( $instance['post_type'] ) : 'post';
		$total     = ! empty( $instance['total'] ) ? absint( $instance['total'] ) : 5;

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore
		}

		$query = new WP_Query(
			array(
				'post_type'           => $post_type,
				'posts_per_page'      => $total,
				'meta_query'          => array(
					array(
						'key'     => 'rbpvw_count',
						'type'    => 'NUMERIC',
						'compare' => 'EXISTS',
					),
				),
				'orderby'             => array(
					'meta_value_num' => 'DESC',
				),
				'ignore_sticky_posts' => true,
			)
		);

		if ( $query->have_posts() ) {
			echo '<ul>';

			while ( $query->have_posts() ) {
				$query->the_post();

				$count = (int) get_post_meta( get_the_ID(), 'rbpvw_count', true );
				?>
				<li>
					<a href="<?php echo esc_url( get_permalink() ); ?>" class="rb-post-view-link">
						<?php the_title(); ?>
					</a>
					<span class="rbpvw-num">(<?php echo esc_html( $count ); ?>)</span>
				</li>
				<?php
			}

			echo '</ul>';
		}

		wp_reset_postdata();

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Update widget instance.
	 *
	 * @param array $new_instance New widget values.
	 * @param array $old_instance Old widget values.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance              = array();
		$instance['title']     = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['post_type'] = isset( $new_instance['post_type'] ) ? sanitize_key( $new_instance['post_type'] ) : 'post';
		$instance['total']     = isset( $new_instance['total'] ) ? absint( $new_instance['total'] ) : 5;

		return $instance;
	}

	/**
	 * Form fields inside the widget admin form.
	 *
	 * @param array $instance Saved settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {

		$title = isset( $instance['title'] ) ? $instance['title'] : esc_html__( 'Most Viewed Posts', 'rb-post-views-widget' );
		$type  = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
		$total = isset( $instance['total'] ) ? absint( $instance['total'] ) : 5;

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'rb-post-views-widget' ); ?>
			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>">
				<?php esc_html_e( 'Post Type:', 'rb-post-views-widget' ); ?>
			</label>

			<select class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'post_type' ) ); ?>">
				<?php foreach ( $post_types as $pt ) : ?>
					<option value="<?php echo esc_attr( $pt->name ); ?>" <?php selected( $type, $pt->name ); ?>>
						<?php echo esc_html( $pt->labels->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'total' ) ); ?>">
				<?php esc_html_e( 'Number of Posts:', 'rb-post-views-widget' ); ?>
			</label>
			<input type="number"
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'total' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'total' ) ); ?>"
				value="<?php echo esc_attr( $total ); ?>">
		</p>

		<?php
	}
}
