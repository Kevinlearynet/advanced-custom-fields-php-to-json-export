<?php
/*
 Plugin Name: Advanced Custom Fields: PHP to JSON Export
 Plugin URI: https://github.com/Kevinlearynet/advanced-custom-fields-php-to-json-export
 Description: Export PHP defined field groups in Advanced Custom Fields as JSON for import into the UI.
 Version: 1.0.0
 Author: Kevin Leary
 Author URI: https://www.kevinleary.net
 */

/**
 * ACF PHP to JSON
 *
 * Convert PHP ACF definitions to JSON for import into UI
 */
class ACF_PHP_to_JSON {

	/**
	 * Hooks
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'generate_export' ), 0 );
	}


	/**
	 * Admin Menu
	 *
	 * Adds the "Export PHP Fields as JSON" menu item to the bottom of the
	 * ACF "Custom Fields" emnu.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=acf-field-group', 'Export PHP Fields as JSON', 'Export PHP as JSON', 'manage_options', 'export-php-as-json', array( $this, 'redirect_to_file' ) );
	}


	/**
	 * Redirect to File
	 */
	public function redirect_to_file() {

		// Generate filename
		$filename = 'acf-export-php-field-groups-' . date( 'm-d-Y' ) . '.json';

		// Security nonce
		$redirect_to = wp_nonce_url( "/wp-admin/index.php", 'acf-export-php-field-groups', 'download_file' );
		$redirect_to .= "&download=$filename";
		?>
		<div class="wrap">
			<h1>Export PHP Fields as JSON</h1>
			<div class="card">
				<h2 class="title">Downloading&hellip;</h2>
				<p>All ACF field groups registered with PHP will be downloaded now as <strong><?php echo $filename; ?></strong>.</p>
			</div>
		</div><!--// end .wrap -->

		<!-- Download file -->
		<script>
		function downloadURI( uri, name ) {
			var link = document.createElement("a");
			link.download = name;
			link.href = uri;
			link.click();
		}
		downloadURI( "<?php echo $redirect_to; ?>", "<?php echo $filename; ?>" );
		</script>
		<?php
	}


	/**
	 * Create JSON
	 *
	 * Gathers all registered fields and creates a *.json file download
	 */
	public function generate_export() {

		// Basic routing check
		$request_uri = esc_attr( $_SERVER['REQUEST_URI'] );
		if ( ! strstr( $request_uri, '/wp-admin/index.php?download_file=' ) )
			return;

		// Security checks
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( 'You must be an administrator to perform this action.' );

		if ( ! isset( $_GET['download_file'] ) || ! wp_verify_nonce( $_GET['download_file'], 'acf-export-php-field-groups' ) )
			wp_die( 'This link is no longer is expired or invalid.' );

		// Build JSON export
		$groups = acf_get_local_field_groups();
		$json = [];
		foreach ( $groups as $group ) {
			$fields = acf_get_local_fields( $group['key'] );
			unset( $group['ID'] );
			$group['fields'] = $fields;
			$json[] = $group;
		}
		$json = json_encode( $json, JSON_PRETTY_PRINT );

		// Output as downloadable *.json file
		$filename = esc_attr( $_GET['download'] );
		header( "Content-disposition: attachment; filename=$filename" );
		header( "Content-type: application/json" );
		echo $json;
		exit;
	}

} // end ACF_PHP_to_JSON

new ACF_PHP_to_JSON();