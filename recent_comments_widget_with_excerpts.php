<?php
/**
 * Plugin Name: Recent Comments Widget with Excerpts
 * Plugin URI: https://coreysalzano.com/wordpress/recent-comments-widget-with-excerpts/
 * Description: Duplicates the built-in recent comments widget to show excerpts instead of post titles.
 * Author: Corey Salzano
 * Author URI: https://github.com/csalzano
 * Version: 1.0.0
 * License: GPLv2
 * Text Domain: recent-comments-widget-with-excerpts
 *
 * @package recent_comments_widget_with_excerpts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recent_Comments widget class
 *
 * @since 2.8.0
 */
class WP_Widget_Recent_Comments_Excerpts extends WP_Widget {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_recent_comments',
			'description' => __( 'The most recent comments' ),
		);
		parent::__construct( 'recent-comments-excerpts', __( 'Recent Comments + Excerpts' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_comments';

		add_action( 'comment_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'transition_comment_status', array( $this, 'flush_widget_cache' ) );
	}

	/**
	 * flush_widget_cache
	 *
	 * @return void
	 */
	public function flush_widget_cache() {
		wp_cache_delete( 'recent_comments', 'widget' );
	}

	/**
	 * widget
	 *
	 * @param  mixed $args
	 * @param  mixed $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ?? __( 'Recent Comments', 'recent_comments_widget_with_excerpts' ) );

		$number = (int) $instance['number'];
		if ( 0 === $number ) {
			$number = 5;
		} elseif ( $number < 1 ) {
			$number = 1;
		} elseif ( $number > 150 ) {
			$number = 150;
		}
		$excerpt_len = (int) $instance['excerptLen'];
		if ( 0 === $excerpt_len ) {
			$excerpt_len = 50;
		} elseif ( $excerpt_len < 1 ) {
			$excerpt_len = 1;
		}

		global $wpdb;
		$comments = wp_cache_get( 'recent_comments', 'widget' );
		if ( ! $comments ) {
			$comments = get_comments(
				array(
					'number'      => $number,
					'orderby'     => 'comment_date_gmt',
					'order'       => 'DESC',
					'post_status' => 'publish',
					'status'      => 'approve',
				)
			);
			wp_cache_add( 'recent_comments', $comments, 'widget' );
		}

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		?>
		<ul id="recentcomments">
		<?php
		if ( $comments ) {
			foreach ( (array) $comments as $comment ) {
				$excerpt = trim( mb_substr( wp_strip_all_tags( apply_filters( 'comment_text', $comment->comment_content ) ), 0, $excerpt_len ) );
				if ( strlen( $comment->comment_content ) > $excerpt_len ) {
					$excerpt .= '...';
				}
				printf(
					'<li class="recentcomments">'
					/* translators: comments widget: 1: comment author, 2: post link */
					. esc_html_x( '%1$s said %2$s', 'recent_comments_widget_with_excerpts' ),
					get_comment_author_link( $comment ),
					'<a href="' . esc_url( get_comment_link( $comment->comment_ID ) ) . '">' . esc_html( $excerpt ) . '</a></li>'
				);
			}
		}
		?>
			</ul>
		<?php echo $args['after_widget']; ?>
		<?php
	}

	/**
	 * update
	 *
	 * @param  mixed $new_instance
	 * @param  mixed $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['title']      = wp_strip_all_tags( $new_instance['title'] );
		$instance['number']     = (int) $new_instance['number'];
		$instance['excerptLen'] = (int) $new_instance['excerptLen'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_recent_comments'] ) ) {
			delete_option( 'widget_recent_comments' );
		}

		return $instance;
	}

	/**
	 * form
	 *
	 * @param  mixed $instance
	 * @return void
	 */
	public function form( $instance ) {
		$title       = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : __( 'Recent Comments', 'recent_comments_widget_with_excerpts' );
		$number      = isset( $instance['number'] ) ? absint( $instance['number'] ) : 8;
		$excerpt_len = isset( $instance['excerptLen'] ) ? absint( $instance['excerptLen'] ) : 50;
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'recent_comments_widget_with_excerpts' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of comments to show (at most 150):', 'recent_comments_widget_with_excerpts' ); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" size="3" /><br />
		</p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'excerptLen' ) ); ?>"><?php esc_html_e( 'Character length of excerpt:', 'recent_comments_widget_with_excerpts' ); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id( 'excerptLen' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'excerptLen' ) ); ?>" type="text" value="<?php echo esc_attr( $excerpt_len ); ?>" size="3" /><br /></p>

		<?php
	}
}

/**
 * WP_Widget_Recent_Comments_Excerpts_Init
 *
 * @return void
 */
function WP_Widget_Recent_Comments_Excerpts_Init() {
	register_widget( 'WP_Widget_Recent_Comments_Excerpts' );
}
add_action( 'widgets_init', 'WP_Widget_Recent_Comments_Excerpts_Init' );
