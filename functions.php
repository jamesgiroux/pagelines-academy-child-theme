<?php
// Setup  -- Probably want to keep this stuff... 

/**
 * Hello and welcome to the PageLines Academy Child Theme! First, lets load the PageLines core so we have access to the functions 
 */	
require_once( dirname(__FILE__) . '/setup.php' );
	
// Chances are you probably won't have much to add in here but in case you're thinking about it or have a background in it, this is where you can add your hooks, actions and section calls.

// Posix check

// add_filter( 'posix_bypass', '__return_true' );
add_filter( 'render_css_posix_', '__return_true' );

// This is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'PLC_STORE_URL', 'http://pagelinescommunity.com' ); // add your own unique prefix to prevent conflicts

// The name of your product. This should match the download name in EDD exactly
define( 'PLC_THEME_NAME', 'PageLines Academy Child Theme' ); // add your own unique prefix to prevent conflicts



/***********************************************
* This is our updater
***********************************************/

if ( !class_exists( 'EDD_SL_Theme_Updater' ) ) {
	// Load our custom theme updater
	include( dirname( __FILE__ ) . '/EDD_SL_Theme_Updater.php' );
}

$test_license = trim( get_option( 'edd_sample_theme_license_key' ) );

$edd_updater = new EDD_SL_Theme_Updater( array(
		'remote_api_url' 	=> PLC_STORE_URL, 	// Our store URL that is running EDD
		'version' 			=> '1.0', 				// The current theme version we are running
		'license' 			=> $test_license, 		// The license key (used get_option above to retrieve from DB)
		'item_name' 		=> PLC_THEME_NAME,	// The name of this theme
		'author'			=> 'James Giroux'	// The author's name
	)
);


/***********************************************
* Add our menu item
***********************************************/

function edd_sample_theme_license_menu() {
	add_theme_page( 'Theme License', 'Theme License', 'manage_options', 'themename-license', 'edd_sample_theme_license_page' );
}
add_action('admin_menu', 'edd_sample_theme_license_menu');



/***********************************************
* Sample settings page, substitute with yours
***********************************************/

function edd_sample_theme_license_page() {
	$license 	= get_option( 'edd_sample_theme_license_key' );
	$status 	= get_option( 'edd_sample_theme_license_key_status' );
	?>
	<div class="wrap">
		<h2><?php _e('Theme License Options'); ?></h2>
		<form method="post" action="options.php">

			<?php settings_fields('edd_sample_theme_license'); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Theme License Key'); ?>
						</th>
						<td>
							<input id="edd_sample_theme_license_key" name="edd_sample_theme_license_key" type="text" class="regular-text" value="<?php esc_attr( $license ); ?>" />
							<label class="description" for="edd_sample_theme_license_key"><?php _e('Enter your PageLines Academy Child Theme license key'); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Activate License'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<span style="color:green;"><?php _e('active'); ?></span>
									<?php wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_theme_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
								<?php } else {
									wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_theme_license_activate" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>

		</form>
	<?php
}

function edd_sample_theme_register_option() {
	// creates our settings in the options table
	register_setting('edd_sample_theme_license', 'edd_sample_theme_license_key', 'edd_theme_sanitize_license' );
}
add_action('admin_init', 'edd_sample_theme_register_option');


/***********************************************
* Gets rid of the local license status option
* when adding a new one
***********************************************/

function edd_theme_sanitize_license( $new ) {
	$old = get_option( 'edd_sample_theme_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'edd_sample_theme_license_key_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

/***********************************************
* Illustrates how to activate a license key.
***********************************************/

function edd_sample_theme_activate_license() {

	if( isset( $_POST['edd_theme_license_activate'] ) ) {
	 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
			return; // get out if we didn't click the Activate button

		global $wp_version;

		$license = trim( get_option( 'edd_sample_theme_license_key' ) );

		$api_params = array(
			'edd_action' => 'activate_license',
			'license' => $license,
			'item_name' => urlencode( PLC_THEME_NAME )
		);

		$response = wp_remote_get( add_query_arg( $api_params, PLC_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "active" or "inactive"

		update_option( 'edd_sample_theme_license_key_status', $license_data->license );

	}
}
add_action('admin_init', 'edd_sample_theme_activate_license');

/***********************************************
* Illustrates how to deactivate a license key.
* This will descrease the site count
***********************************************/

function edd_sample_theme_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_theme_license_deactivate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'edd_sample_theme_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( PLC_THEME_NAME ) // the name of our product in EDD
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, PLC_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'edd_sample_theme_license_key_status' );

	}
}
add_action('admin_init', 'edd_sample_theme_deactivate_license');



/***********************************************
* Illustrates how to check if a license is valid
***********************************************/

function edd_sample_theme_check_license() {

	global $wp_version;

	$license = trim( get_option( 'edd_sample_theme_license_key' ) );

	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_name' => urlencode( PLC_THEME_NAME )
	);

	$response = wp_remote_get( add_query_arg( $api_params, PLC_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

	if ( is_wp_error( $response ) )
		return false;

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if( $license_data->license == 'valid' ) {
		echo 'valid'; exit;
		// this license is still valid
	} else {
		echo 'invalid'; exit;
		// this license is no longer valid
	}
}