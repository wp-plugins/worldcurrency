<?php

	class WorldCurrencyWidget extends WP_Widget {
		
		function WorldCurrencyWidget()	{
			$widget_ops = array('classname' => 'WorldCurrencyWidget', 'description' => 'Shows the currency selection box if needed' );
			$this->WP_Widget('WorldCurrencyWidget', 'World Currency', $widget_ops);
		}
		 
		function form($instance) {
		}
		 
		function update($new_instance, $old_instance) {
		}
		 
		function widget($args, $instance) {
			global $post;
			if (strpos($post->post_content, 'worldcurrency') !== false)
				echo dt_wc_getCurrencySelectionBox();
		}
	 
	}
	
	add_action( 'widgets_init', create_function('', 'return register_widget("WorldCurrencyWidget");') );