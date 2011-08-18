<?php

// Adds compatibility methods between WP_Widget and scbForms

abstract class scbWidget extends WP_Widget {
	protected $defaults = array();

	private static $scb_widgets = array();

	static function init( $class, $file = '', $base = '' ) {
		self::$scb_widgets[] = $class;

		add_action( 'widgets_init', array( __CLASS__, '_scb_register' ) );

		// for auto-uninstall
		if ( $file && $base && class_exists( 'scbOptions' ) )
			new scbOptions( "widget_$base", $file );
	}

	static function _scb_register() {
		foreach ( self::$scb_widgets as $widget )
			register_widget( $widget );
	}

	// A pre-filled method, for convenience
	function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		extract( $args );

		echo $before_widget;

		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '', $instance, $this->id_base );

		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$this->content( $instance );

		echo $after_widget;
	}

	// This is where the actual widget content goes
	function content( $instance ) {}


//_____HELPER METHODS_____


	// See scbForms::input()
	// Allows extra parameter $args['title']
	protected function input( $args, $formdata = array() ) {
		// Add default class
		if ( !isset( $args['extra'] ) && 'text' == $args['type'] )
			$args['extra'] = array( 'class' => 'widefat' );

		// Add default label position
		if ( !in_array( $args['type'], array( 'checkbox', 'radio' ) ) && empty( $args['desc_pos'] ) )
			$args['desc_pos'] = 'before';

		// Then add prefix to names and formdata
		$old_name = $args['name'];
		$new_name = $this->scb_get_field_name( $old_name );

		$new_formdata = array();
		$new_formdata[ $new_name ] = scbForms::get_value( $old_name, $formdata );

		$args['name'] = $new_name;

		return scbForms::input( $args, $new_formdata );
	}


//_____INTERNAL METHODS_____


	private function scb_get_field_name( $name ) {
		$name = (array) $name;

		$name[0] = $this->get_field_name( $name[0] );

		return scbForms::get_name( $name );
	}
}

