<?php
/**
 * File containing the class WP_Job_Manager_Admin.
 *
 * @package wp-job-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles front admin page for WP Job Manager.
 *
 * @since 1.0.0
 */
class WP_Job_Manager_Admin {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.26.0
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.26.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_version;

		include_once dirname( __FILE__ ) . '/class-wp-job-manager-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-wp-job-manager-cpt.php';
		WP_Job_Manager_CPT::instance();

		include_once dirname( __FILE__ ) . '/class-wp-job-manager-settings.php';
		include_once dirname( __FILE__ ) . '/class-wp-job-manager-writepanels.php';
		include_once dirname( __FILE__ ) . '/class-wp-job-manager-setup.php';

		$this->settings_page = WP_Job_Manager_Settings::instance();

		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'current_screen', [ $this, 'conditional_includes' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 12 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Set up actions during admin initialization.
	 */
	public function admin_init() {
		include_once dirname( __FILE__ ) . '/class-wp-job-manager-taxonomy-meta.php';
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		switch ( $screen->id ) {
			case 'options-permalink':
				include 'class-wp-job-manager-permalink-settings.php';
				break;
		}
	}

	/**
	 * Enqueues CSS and JS assets.
	 */
	public function admin_enqueue_scripts() {
		WP_Job_Manager::register_select2_assets();

		$screen = get_current_screen();
		if ( in_array( $screen->id, apply_filters( 'job_manager_admin_screen_ids', [ 'edit-job_listing', 'plugins', 'job_listing', 'job_listing_page_job-manager-settings', 'job_listing_page_job-manager-addons' ] ), true ) ) {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'select2' );
			wp_enqueue_style( 'job_manager_admin_css', JOB_MANAGER_PLUGIN_URL . '/assets/css/admin.css', [], JOB_MANAGER_VERSION );
			wp_enqueue_script( 'wp-job-manager-datepicker' );
			wp_register_script( 'jquery-tiptip', JOB_MANAGER_PLUGIN_URL . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', [ 'jquery' ], JOB_MANAGER_VERSION, true );
			wp_enqueue_script( 'job_manager_admin_js', JOB_MANAGER_PLUGIN_URL . '/assets/js/admin.min.js', [ 'jquery', 'jquery-tiptip', 'select2' ], JOB_MANAGER_VERSION, true );
			wp_enqueue_script( 'wp-job-manager-company-data-admin', JOB_MANAGER_PLUGIN_URL . '/assets/js/company-data-admin.js', array( 'jquery' ), JOB_MANAGER_VERSION, true );
			
			$ajax_data        = array(
				'ajax_url'                => WP_Job_Manager_Ajax::get_endpoint(),
			);
			wp_localize_script( 'wp-job-manager-company-data-admin', 'job_manager_ajax_company_data', $ajax_data );

			wp_localize_script(
				'job_manager_admin_js',
				'job_manager_admin_params',
				[
					'user_selection_strings' => [
						'no_matches'        => _x( 'No matches found', 'user selection', 'wp-job-manager' ),
						'ajax_error'        => _x( 'Loading failed', 'user selection', 'wp-job-manager' ),
						'input_too_short_1' => _x( 'Please enter 1 or more characters', 'user selection', 'wp-job-manager' ),
						'input_too_short_n' => _x( 'Please enter %qty% or more characters', 'user selection', 'wp-job-manager' ),
						'load_more'         => _x( 'Loading more results&hellip;', 'user selection', 'wp-job-manager' ),
						'searching'         => _x( 'Searching&hellip;', 'user selection', 'wp-job-manager' ),
					],
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'search_users_nonce'     => wp_create_nonce( 'search-users' ),
				]
			);
		}

		if ( job_manager_user_can_upload_file_via_ajax() ) {
			wp_register_script( 'jquery-iframe-transport', JOB_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.iframe-transport.js', array( 'jquery' ), '9.30.0', true );
			wp_register_script( 'jquery-fileupload', JOB_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.fileupload.js', array( 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ), '9.30.0', true );
			wp_register_script( 'wp-job-manager-ajax-file-upload', JOB_MANAGER_PLUGIN_URL . '/assets/js/ajax-file-upload.min.js', array( 'jquery', 'jquery-fileupload' ), JOB_MANAGER_VERSION, true );

			ob_start();
			get_job_manager_template(
				'form-fields/uploaded-file-html.php',
				array(
					'name'      => '',
					'value'     => '',
					'extension' => 'jpg',
				)
			);
			$js_field_html_img = ob_get_clean();

			ob_start();
			get_job_manager_template(
				'form-fields/uploaded-file-html.php',
				array(
					'name'      => '',
					'value'     => '',
					'extension' => 'zip',
				)
			);
			$js_field_html = ob_get_clean();

			wp_localize_script(
				'wp-job-manager-ajax-file-upload',
				'job_manager_ajax_file_upload',
				array(
					'ajax_url'               => WP_Job_Manager_Ajax::get_endpoint(),
					'js_field_html_img'      => esc_js( str_replace( "\n", '', $js_field_html_img ) ),
					'js_field_html'          => esc_js( str_replace( "\n", '', $js_field_html ) ),
					'i18n_invalid_file_type' => esc_html__( 'Invalid file type. Accepted types:', 'wp-job-manager' ),
				)
			);
		} 

		wp_enqueue_style( 'job_manager_admin_menu_css', JOB_MANAGER_PLUGIN_URL . '/assets/css/menu.css', array(), JOB_MANAGER_VERSION );
	}

	/**
	 * Adds pages to admin menu.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=job_listing', __( 'Settings', 'wp-job-manager' ), __( 'Settings', 'wp-job-manager' ), 'manage_options', 'job-manager-settings', [ $this->settings_page, 'output' ] );

		if ( WP_Job_Manager_Helper::instance()->has_licenced_products() || apply_filters( 'job_manager_show_addons_page', true ) ) {
			add_submenu_page( 'edit.php?post_type=job_listing', __( 'WP Job Manager Add-ons', 'wp-job-manager' ), __( 'Add-ons', 'wp-job-manager' ), 'manage_options', 'job-manager-addons', [ $this, 'addons_page' ] );
		}
	}

	/**
	 * Displays addons page.
	 */
	public function addons_page() {
		$addons = include 'class-wp-job-manager-addons.php';
		$addons->output();
	}
}

WP_Job_Manager_Admin::instance();
