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
			echo "<div class=\"worldcurrency_selection_box_placeholder\"></div>";
		}
	 
	}
	
	add_action( 'widgets_init', create_function('', 'return register_widget("WorldCurrencyWidget");') );