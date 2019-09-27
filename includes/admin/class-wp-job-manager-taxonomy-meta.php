<?php
/**
 * File containing the class WP_Job_Manager_Taxonomy_Meta.
 *
 * @package wp-job-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles taxonomy meta custom fields. Just used for job type and category.
 *
 * @since 1.28.0
 */
class WP_Job_Manager_Taxonomy_Meta {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.28.0
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.28.0
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
	 * WP_Job_Manager_Taxonomy_Meta constructor.
	 */
	public function __construct() {
		//@todo replace with a custom option to enable this functionality
		if ( wpjm_category_as_company_enabled() ) {
			add_action( 'job_listing_category_edit_form_fields', array( $this, 'display_schema_org_category_fields' ), 10, 2 );
			add_action( 'job_listing_category_add_form_fields', array( $this, 'add_form_display_schema_org_company_fields' ), 10 );
			add_action( 'edited_job_listing_category', array( $this, 'set_schema_org_category_fields' ), 10, 2 );
			add_action( 'created_job_listing_category', array( $this, 'set_schema_org_category_fields' ), 10, 2 );
		}

		if ( wpjm_job_listing_employment_type_enabled() ) {
			add_action( 'job_listing_type_edit_form_fields', array( $this, 'display_schema_org_employment_type_field' ), 10, 2 );
			add_action( 'job_listing_type_add_form_fields', array( $this, 'add_form_display_schema_org_employment_type_field' ), 10 );
			add_action( 'edited_job_listing_type', array( $this, 'set_schema_org_employment_type_field' ), 10, 2 );
			add_action( 'created_job_listing_type', array( $this, 'set_schema_org_employment_type_field' ), 10, 2 );
			add_filter( 'manage_edit-job_listing_type_columns', array( $this, 'add_employment_type_column' ) );
			add_filter( 'manage_job_listing_type_custom_column', array( $this, 'add_employment_type_column_content' ), 10, 3 );
			add_filter( 'manage_edit-job_listing_type_sortable_columns', array( $this, 'add_employment_type_column_sortable' ) );
		}
	}

	/**
	 * Set the category fields when creating/updating a category type item.
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Taxonomy category ID.
	 */
	public function set_schema_org_category_fields( $term_id, $tt_id ) {
		$fields = wpjm_job_listing_category_options();
		
		if(isset($_POST['current_company_logo'])) {
			$_POST['company_logo'] = create_attachment_from_url($_POST['current_company_logo'], $term_id, $_POST['company_name'] . ' Logo');
		}
		error_log(print_r($_POST, true));

		foreach($fields as $field) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce check handled by WP core.
			$input = isset( $_POST[$field['name']] ) ? sanitize_text_field( wp_unslash( $_POST[$field['name']] ) ) : null;
			if ( $input ) {
				update_term_meta( $term_id, $field['name'], sanitize_text_field( wp_unslash( $input ) ) );
			} elseif ( null !== $input ) {
				delete_term_meta( $term_id, $field['name'] );
			}
		}
	}

	/**
	 * Set the employment type field when creating/updating a job type item.
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Taxonomy type ID.
	 */
	public function set_schema_org_employment_type_field( $term_id, $tt_id ) {
		$employment_types = wpjm_job_listing_employment_type_options();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce check handled by WP core.
		$input_employment_type = isset( $_POST['employment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['employment_type'] ) ) : null;

		if ( $input_employment_type && isset( $employment_types[ $input_employment_type ] ) ) {
			update_term_meta( $term_id, 'employment_type', sanitize_text_field( wp_unslash( $input_employment_type ) ) );
		} elseif ( null !== $input_employment_type ) {
			delete_term_meta( $term_id, 'employment_type' );
		}
	}

	/**
	 * Add the option to select schema.org company fields for job category on the edit meta field form.
	 *
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 */
	public function display_schema_org_category_fields( $term, $taxonomy ) {
		$fields = wpjm_job_listing_category_options();

		foreach($fields as $field) {
			$value = get_term_meta( $term->term_id, $field['name'], true );

			?>
			<style>
				img {
					max-width: 200px;
					max-height: 200px;
				}
			</style>
			<tr class="form-field term-group-wrap">
				<th scope="row"><label for="feature-group"><?php esc_html_e( $field['label'], 'wp-job-manager' ); ?></label></th>
				<td>
				<?php
					if($field['type'] == 'file') {
						$field['value'] = $value;
						get_job_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $field['name'], 'field' => $field ) );
					}
					else {
					?>
						<input name="<?php esc_html_e( $field['name'], 'wp-job-manager' ); ?>" id="<?php esc_html_e( $field['name'], 'wp-job-manager' ); ?>" type="text" value="<?php echo $value; ?>" size="40">
					<?php } ?>
					</td>
			</tr>
			<?php
		}
	}

	/**
	 * Add the option to select schema.org employmentType for job type on the edit meta field form.
	 *
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 */
	public function display_schema_org_employment_type_field( $term, $taxonomy ) {
		$employment_types        = wpjm_job_listing_employment_type_options();
		$current_employment_type = get_term_meta( $term->term_id, 'employment_type', true );

		if ( ! empty( $employment_types ) ) {
			?>
			<tr class="form-field term-group-wrap">
			<th scope="row"><label for="feature-group"><?php esc_html_e( 'Employment Type', 'wp-job-manager' ); ?></label></th>
			<td><select class="postform" id="employment_type" name="employment_type">
					<option value=""></option>
					<?php foreach ( $employment_types as $key => $employment_type ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_employment_type, $key ); ?>><?php echo esc_html( $employment_type ); ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
			<?php
		}
	}

	/**
	 * Add the option to select schema.org company fields for job type on the add meta field form.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function add_form_display_schema_org_company_fields( $taxonomy ) {
		$fields = wpjm_job_listing_category_options();

		foreach($fields as $field) {
			$value = get_term_meta( $term->term_id, $field['name'], true );

			?>
			<style>
				img {
					max-width: 200px;
					max-height: 200px;
				}
			</style>
			<tr class="form-field term-group-wrap">
				<th scope="row"><label for="feature-group"><?php esc_html_e( $field['label'], 'wp-job-manager' ); ?></label></th>
				<td>
				<?php
					if($field['type'] == 'file') {
						$field['value'] = $value;
						get_job_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $field['name'], 'field' => $field ) );
					}
					else {
					?>
						<input name="<?php esc_html_e( $field['name'], 'wp-job-manager' ); ?>" id="<?php esc_html_e( $field['name'], 'wp-job-manager' ); ?>" type="text" value="<?php echo $value; ?>" size="40">
					<?php } ?>
					</td>
			</tr>
			<?php
		}
	}

	/**
	 * Add the option to select schema.org employmentType for job type on the add meta field form.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function add_form_display_schema_org_employment_type_field( $taxonomy ) {
		$employment_types = wpjm_job_listing_employment_type_options();

		if ( ! empty( $employment_types ) ) {
			?>
			<div class="form-field term-group">
			<label for="feature-group"><?php esc_html_e( 'Employment Type', 'wp-job-manager' ); ?></label>
			<select class="postform" id="employment_type" name="employment_type">
					<option value=""></option>
					<?php foreach ( $employment_types as $key => $employment_type ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $employment_type ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
		}
	}

	/**
	 * Adds the Employment Type column when listing job type terms in WP Admin.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_employment_type_column( $columns ) {
		$columns['employment_type'] = __( 'Employment Type', 'wp-job-manager' );
		return $columns;
	}

	/**
	 * Adds the Employment Type column as a sortable column when listing job type terms in WP Admin.
	 *
	 * @param array $sortable
	 * @return array
	 */
	public function add_employment_type_column_sortable( $sortable ) {
		$sortable['employment_type'] = 'employment_type';
		return $sortable;
	}

	/**
	 * Adds the Employment Type column content when listing job type terms in WP Admin.
	 *
	 * @param string $content
	 * @param string $column_name
	 * @param int    $term_id
	 * @return string
	 */
	public function add_employment_type_column_content( $content, $column_name, $term_id ) {
		if ( 'employment_type' !== $column_name ) {
			return $content;
		}
		$employment_types     = wpjm_job_listing_employment_type_options();
		$term_id              = absint( $term_id );
		$term_employment_type = get_term_meta( $term_id, 'employment_type', true );

		if ( ! empty( $term_employment_type ) && isset( $employment_types[ $term_employment_type ] ) ) {
			$content .= esc_attr( $employment_types[ $term_employment_type ] );
		}
		return $content;
	}
}

WP_Job_Manager_Taxonomy_Meta::instance();
