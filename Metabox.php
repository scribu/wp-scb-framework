<?php

class scbMetabox {

	private $identifier, $display_name;

	private $post_types = array();

	private $post_data = array();

	protected $actions = array( 'admin_enqueue_scripts', 'post_updated_messages' );

	public function __construct( $identifier, $display_name, $post_types = 'post', $context = 'advanced', $priority = 'default' ) {
		$this->identifier = $identifier;
		$this->display_name = $display_name;
		$this->context = $context;
		$this->priority = $priority;

		if ( is_string( $post_types ) )
			$post_types = array( $post_types );

		$this->post_types = $post_types;

		add_action( 'load-post.php', array( $this, 'pre_register' ) );
		add_action( 'load-post-new.php', array( $this, 'pre_register' ) );
	}

	final public function pre_register() {
		if ( ! in_array( get_current_screen()->post_type, $this->post_types ) )
			return;

		if ( ! $this->condition() )
			return;

		if ( isset( $_GET['post'] ) )
			$this->post_data = $this->get_meta( intval( $_GET['post'] ) );

		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, '_save_post' ), 10, 2 );

		foreach ( $this->actions as $action ) {
			if ( method_exists( $this, $action ) )
				add_action( $action, array( $this, $action ) );
		}
	}

	// Additional checks before registering the metabox
	protected function condition() {
		return true;
	}

	final public function register() {
		if ( $this->context === 'standalone' ) {
			add_action( 'edit_form_advanced', array( $this, 'standalone' ) );
		} else {
			foreach ( $this->post_types as $post_type ) {
				add_meta_box( $this->identifier, $this->display_name, array( $this, 'display' ), $post_type, $this->context, $this->priority );
			}
		}
	}

	final public function standalone() {
		global $post_ID;

		$this->display( get_post( $post_ID ) );
	}

	public function before_display( $form_data, $post ) {
		return $form_data;
	}

	public function display( $post ) {
		$form_fields = $this->form_fields();
		if ( ! $form_fields )
			return;

		$form_data = $this->post_data;
		$error_fields = array();

		if ( isset( $form_data['_error_data_' . $this->identifier ] ) ) {
			$data = unserialize( $form_data['_error_data_' . $this->identifier ] );

			$error_fields = $data['fields'];
			$form_data = $data['data'];
		}

		$form_data = $this->before_display( $form_data, $post );

		$form = $this->table( $form_fields, $form_data, $error_fields );

		$this->before_form( $post );
		echo $form;
		$this->after_form( $post );

		delete_post_meta( $post->ID, '_error_data_' . $this->identifier  );
	}

	public function table( $rows, $formdata, $errors = array() ) {
		$output = '';
		foreach ( $rows as $row ) {
			$output .= $this->table_row( $row, $formdata, $errors );
		}

		$output = scbForms::table_wrap( $output );

		return $output;
	}

	public function table_row( $row, $formdata, $errors = array() ) {
		$input = scbForms::input( $row, $formdata );

		// If row has an error, highlight it
		$style = ( in_array( $row['name'], $errors ) ) ? 'style= "background-color: #FFCCCC"' : '';

		return html( 'tr',
			html( "th $style scope='row'", $row['title'] ),
			html( "td $style", $input )
		);
	}

	// Display some extra HTML before the form
	public function before_form( $post ) { }

	// Return the list of form fields
	public function form_fields() {
		return array();
	}

	// Display some extra HTML after the form
	public function after_form( $post ) { }

	// Makes sure that the saving occurs only for the post being edited
	final public function _save_post( $post_id, $post ) {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'editpost' )
			return;

		if ( $post_id != $_POST['post_ID'] )
			return;

		if ( ! in_array( $post->post_type, $this->post_types ) )
			return;

		$this->save( $post->ID );
	}

	protected function save( $post_id ) {
		$form_fields = $this->form_fields();

		$to_update = scbForms::validate_post_data( $form_fields );

		// Filter data
		$to_update = $this->before_save( $to_update, $post_id );

		// Validate dataset
		$is_valid = $this->validate_post_data( $to_update, $post_id );
		if ( $is_valid instanceof WP_Error && $is_valid->get_error_codes() ) {

			$error_data = array(
				'fields' => $is_valid->get_error_codes(),
				'data' => $to_update
			);
			update_post_meta( $post_id, '_error_data_' . $this->identifier, $error_data );

			$location = add_query_arg( 'message', 1, get_edit_post_link( $post_id, 'url' ) );
			wp_redirect( apply_filters( 'redirect_post_location', $location, $post_id ) );
			exit;
		}

		foreach ( $to_update as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	protected function before_save( $post_data, $post_id ) {
		return $post_data;
	}

	protected function validate_post_data( $post_data ) {
		return false;
	}

	private function get_meta( $post_id ) {
		$meta = get_post_custom( $post_id );
		foreach ( $meta as $key => $values )
			$meta[$key] = $meta[$key][0];

		return $meta;
	}
}

