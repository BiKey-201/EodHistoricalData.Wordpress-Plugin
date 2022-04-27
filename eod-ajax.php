<?php
if (wp_doing_ajax()) {
    // Get Fundamental Data
    add_action('wp_ajax_nopriv_get_fundamental_data', 'get_fundamental_data_callback');
    add_action('wp_ajax_get_fundamental_data', 'get_fundamental_data_callback');
    function get_fundamental_data_callback(){
        if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
        global $eod_api;
        echo json_encode( $eod_api->get_fundamental_data($_POST['target']) );
        wp_die();
    }

    // Get API token
	add_action('wp_ajax_nopriv_get_eod_token', 'get_eod_token_callback');
	add_action('wp_ajax_get_eod_token', 'get_eod_token_callback');
	function get_eod_token_callback(){
		if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
        global $eod_api;
		echo $eod_api->get_eod_api_key();
		wp_die();
	}

	// Get ticker data
	add_action('wp_ajax_nopriv_get_real_time_ticker', 'get_real_time_ticker_callback');
	add_action('wp_ajax_get_real_time_ticker', 'get_real_time_ticker_callback');
	function get_real_time_ticker_callback(){
		if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
		global $eod_api;
        echo json_encode( $eod_api->get_real_time_ticker($_POST['type'], $_POST['list']) );
		wp_die();
	}

	// Check API token for permissions
	add_action('wp_ajax_eod_check_token_capability', 'eod_check_token_capability_callback');
	function eod_check_token_capability_callback(){
		if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
        global $eod_api;
		echo json_encode( $eod_api->check_token_capability($_POST['type'], $_POST['props']) );
		wp_die();
	}

    // Searching for items from API by string
    add_action('wp_ajax_search_by_string', 'search_by_string_callback');
    function search_by_string_callback(){
        if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
        global $eod_api;
        echo json_encode( $eod_api->search_by_string($_POST['string']) );
        wp_die();
    }

	// Get User data
	add_action('wp_ajax_get_user_data', 'get_user_data_callback');
	function get_user_data_callback(){
		if (!wp_verify_nonce($_POST['nonce_code'], 'eod_ajax_nonce')) die('Stop!');
        echo json_encode( EOD_Stock_Prices_Admin::get_user_data($_POST['key']) );
		wp_die();
	}
}

?>