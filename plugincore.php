<?php
/**
 * @package   Edd_License_Key_Template
 * @author    David Cramer <david@digilab.co.za>
 * @license   GPL-2.0+
 * @link      http://cramer.co.za
 * @copyright 2015 David
 *
 * @wordpress-plugin
 * Plugin Name: EDD License Key Template
 * Plugin URI:  https://calderawp.com
 * Description: Generates license keys based on template
 * Version:     1.0.0
 * Author:      David Cramer
 * Author URI:  http://cramer.co.za
 * Text Domain: edd-license-key-template
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load instance
add_action( 'plugins_loaded', 'cl_init_key_pattern_actions' );
function cl_init_key_pattern_actions(){

	add_action('add_meta_boxes', 'cl_edd_key_add_metaboxes' );
	add_action('save_post', 'cl_edd_key_save_post_metaboxes' );	

}


/**
 * setup meta boxes.
 *
 *
 * @return    null
 */
function cl_edd_key_add_metaboxes( $slug, $post = false ){
	add_meta_box('license_key_template', 'License Key Template', 'cl_edd_key_pattern_metabox', 'download', 'side', 'default' );
}


function cl_edd_key_pattern_metabox( $post, $args ){

	$pattern	= get_post_meta( $post->ID, '_cl_key_template', true );
	if( empty( $pattern ) ){
		$pattern = '****-****-****-********';
	}

	echo '<input type="hidden" name="cl_edd_key_pattern_nonce" value="', wp_create_nonce( 'cl_edd_key_gen_pattern' ), '" />';
	echo '<input type="text" value="' . esc_attr( $pattern ) . '" id="cl_field_key_template" name="_cl_key_template" style="width:100%;">';
	echo '<p class="description">* : Numbers & Letters<br>&amp; : Letters Only <br># : Numbers Only</p>';


}



/**
 * Save data from meta box
 *
 * @since 1.0
 */
function cl_edd_key_save_post_metaboxes( $post_id ) {

	global $post;

	// verify nonce
	if ( ! isset( $_POST['cl_edd_key_pattern_nonce'] ) || ! wp_verify_nonce( $_POST['cl_edd_key_pattern_nonce'], 'cl_edd_key_gen_pattern' ) ) {
		return $post_id;
	}

	// Check for auto save / bulk edit
	if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return $post_id;
	}

	if ( isset( $_POST['post_type'] ) && 'download' != $_POST['post_type'] ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( isset( $_POST['_cl_key_template'] ) ) {
		update_post_meta( $post_id, '_cl_key_template', trim( $_POST['_cl_key_template'] ) ) ;
	} else {
		delete_post_meta( $post_id, '_cl_key_template' );
	}

}



/**
 * Generate a new Key
 *
 *
 * @return    string    generated key.
 */
function cl_edd_gen_key_pattern( $key, $license_id, $download_id, $payment_id, $cart_index ){

	global $wpdb;

	$key_template = get_post_meta( $download_id, '_cl_key_template', true );
	if( empty( $key_template ) ){
		$key_template = '****-****-****-********';
	}


	$an_tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890123456789';
	$n_tokens = '0123456789';
	$a_tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$not_unique = true;
	
	while( $not_unique === true){
		$input_line = $key_template;

		// alphanumer
		preg_match_all("/(\*)/", $input_line, $alphanumer);
		if(!empty($alphanumer[0])){
			for($i=0;$i<count($alphanumer[0]); $i++){
				$char = $an_tokens[rand(0, (strlen($an_tokens)-1) )];
				$input_line = preg_replace('/\*/', $char,$input_line, 1);
			}
		}
		// alphanumer
		preg_match_all("/(#)/", $input_line, $alphanumer);
		if(!empty($alphanumer[0])){
			for($i=0;$i<count($alphanumer[0]); $i++){
				$char = $n_tokens[rand(0, (strlen($n_tokens)-1) )];
				$input_line = preg_replace('/#/', $char,$input_line, 1);
			}
		}
		// alphanumer
		preg_match_all("/(&)/", $input_line, $alphanumer);
		if(!empty($alphanumer[0])){
			for($i=0;$i<count($alphanumer[0]); $i++){
				$char = $a_tokens[rand(0, (strlen($a_tokens)-1) )];
				$input_line = preg_replace('/&/', $char,$input_line, 1);
			}
		}

		$is_unique = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM `" . $wpdb->postmeta . "` WHERE `meta_key` = '_edd_sl_key' AND `meta_value` = %s LIMIT 1 ;", $input_line) );
		if(empty($is_unique)){
			$not_unique = false;
		}
	}

	return $input_line;
}
add_filter( 'edd_sl_generate_license_key', 'cl_edd_gen_key_pattern', 10, 5 );


function cl_cleanup_span_wrapping(){
	if( !empty( $_GET['action'] ) && !empty( $_GET['payment_id'] ) ){
		echo '<style>.edd_sl_license_key{white-space: nowrap;}</style>';
	}
}
add_action( 'wp_print_styles', 'cl_cleanup_span_wrapping');