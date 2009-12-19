<?php
/*
Plugin Name: Most Commented Widget
Plugin URI: http://wordpress.org/extend/plugins/most-commented/
Description: Widget to display posts/pages with the most comments. 
Version: 2.0
Author: Nick Momrik
Author URI: http://nickmomrik.com/
*/

class Most_Commented_Widget extends WP_Widget {
    function Most_Commented_Widget() {
        parent::WP_Widget( false, $name = 'Most Commented' );	
    }

    function widget( $args, $instance ) {
        extract( $args );
        $title = apply_filters( 'widget_title', $instance['title'] );
		$show_pass_post = (bool)$instance['show_pass_post'];
		$duration = intval( $instance['duration'] );
		if ( !in_array( $duration, array( 0, 1, 7, 30, 365 ) ) )
			$duration = 0;
		$num_posts = intval( $instance['num_posts'] );
		if ( $num_posts < 1 )
			$num_posts = 5;
		$post_type = $instance['post_type'];
		if ( !in_array( $post_type, array( 'post', 'page', 'both' ) ) )
			$post_type = 'both';

		global $wpdb;

		if ( ! $output = wp_cache_get( $widget_id ) ) {
			$request = "SELECT ID, post_title, comment_count FROM $wpdb->posts WHERE comment_count > 0 AND post_status = 'publish'";
			if ( !$show_pass_post )
				$request .= " AND post_password = ''";
			if ( 'both' != $post_type )
				$request .= " AND post_type = '$post_type'";
			if ( $duration > 0 )
				$request .= " AND DATE_SUB(CURDATE(), INTERVAL $duration DAY) < post_date";
			$request .= " ORDER BY comment_count DESC LIMIT $num_posts";

			$posts = $wpdb->get_results( $request );
	
			$output = '';

			if ( !empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$post_title = apply_filters( 'the_title', $post->post_title );

					$output .= '<li><a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( $post_title ) . '">' . $post_title . '</a> (' . $post->comment_count .')</li>';
				}
			} else {
				$output .= '<li>None found</li>';
			}

			if ( $title )
				$output = $before_title . $title . $after_title . $output;
			$output = $before_widget . '<ul>' . $output . '</ul>' . $after_widget;
		
			wp_cache_set( widget_id, $output, '', 1800 );
		}
	
		echo $output;
		
    }

    function update( $new_instance, $old_instance ) {
		$new_instance['show_pass_post'] = isset( $new_instance['show_pass_post'] );

		wp_cache_delete( $this->id );

		return $new_instance;
    }

    function form( $instance ) {
        $title = esc_attr( $instance['title'] );
		$show_pass_post = (bool)$instance['show_pass_post'];
		$duration = intval( $instance['duration'] );
		if ( !in_array( $duration, array( 0, 1, 7, 30, 365 ) ) )
			$duration = 0;
		$num_posts = intval( $instance['num_posts'] );
		if ( $num_posts < 1 )
			$num_posts = 5;
		$post_type = $instance['post_type'];
		if ( !in_array( $post_type, array( 'post', 'page', 'both' ) ) )
			$post_type = 'both';
        ?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Display:' ); ?>
			<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
			<?php
				$post_type_choices = array( 'post' => __( 'Posts' ), 'page' => __( 'Pages' ), 'both' => __( 'Posts & Pages' ) );
				foreach ( $post_type_choices as $post_type_value => $post_type_text ) {
					echo "<option value='$post_type_value' " . ( $post_type == $post_type_value ? "selected='selected'" : '' ) . ">$post_type_text</option>\n";
				}
			?>
			</select>
			</label></p>
			<p><label for="<?php echo $this->get_field_id( 'num_posts' ); ?>"><?php _e( 'Maximum number of results:' ); ?>
			<select id="<?php echo $this->get_field_id('num_posts'  ); ?>" name="<?php echo $this->get_field_name( 'num_posts' ); ?>">
			<?php
				for ( $i = 1; $i <= 20; ++$i ) {
					echo "<option value='$i' " . ( $num_posts == $i ? "selected='selected'" : '' ) . ">$i</option>\n";
				}
			?>
			</select>
			</label></p>
			<p><label for="<?php echo $this->get_field_id( 'duration' ); ?>"><?php _e( 'Limit to:' ); ?>
			<select id="<?php echo $this->get_field_id( 'duration' ); ?>" name="<?php echo $this->get_field_name( 'duration' ); ?>">
			<?php
				$duration_choices = array( 1 => __( '1 Day' ), 7 => __( '7 Days' ), 30 => __( '30 Days' ), 365 => __( '365 Days' ), 0 => __( 'All Time' ) );
				foreach ( $duration_choices as $duration_num => $duration_text ) {
					echo "<option value='$duration_num' " . ( $duration == $duration_num ? "selected='selected'" : '' ) . ">$duration_text</option>\n";
				}
			?>
			</select>
			</label></p>
			<p><label for="<?php echo $this->get_field_id( 'show_pass_post' ); ?>"><input id="<?php echo $this->get_field_id( 'show_pass_post' ); ?>" class="checkbox" type="checkbox" name="<?php echo $this->get_field_name( 'show_pass_post' ); ?>"<?php echo checked( $show_pass_post ); ?> /> <?php _e( 'Include password protected posts/pages' ); ?></label></p>
        <?php 
    }

}

add_action( 'widgets_init', create_function( '', 'return register_widget( "Most_Commented_Widget" );' ) );

if ( !function_exists( 'mdv_most_commented' ) ) {
	function mdv_most_commented( $no_posts, $before, $after, $show_pass_post, $duration, $echo = true ) {
		echo 'Please use the new widget in Appearance->Widgets or use an old version of the plugin from http://wordpress.org/extend/plugins/most-commented/download/. The mdv_most_commented() function is no longer supported.';
	}
}