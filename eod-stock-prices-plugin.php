<?php

/*
Plugin Name: Financial Stocks & Crypto Market Data Plugin
Plugin URI: https://eodhistoricaldata.com/knowledgebase/plugins
Description: The stock prices plugin allows you to use a widget and a shortcode to display the ticker data you want.
Version: 1.7
Author: Eod Historical Data
Author URI: https://eodhistoricaldata.com
*/


require( plugin_dir_path( __FILE__ ) . 'widget/ticker-widget.php' );
require( plugin_dir_path( __FILE__ ) . 'widget/news-widget.php' );
require( plugin_dir_path( __FILE__ ) . 'widget/fundamental-widget.php' );

if(!class_exists('EOD_Stock_Prices_Plugin'))
{
    class EOD_Stock_Prices_Plugin{
        /**
         * A dummy constructor to ensure EOD is only setup once.
         */
        public function __construct(){
            // Do nothing.
        }

        /**
         * Sets up the EOD plugin.
         */
        function initialize()
        {
            // Define constants.
            $this->define( 'EOD_VER', '1.7' );
            $this->define( 'EOD_PLUGIN_NAME', 'Financial Stocks & Crypto Market Data Plugin' );
            $this->define( 'EOD_DEFAULT_API', 'OeAFFmMliFG5orCUuwAKQ8l4WWFQ67YX' );
            $this->define( 'EOD_PATH', plugin_dir_path( __FILE__ ) );
            $this->define( 'EOD_URL', plugins_url( '/',__FILE__ ) );
            $this->define( 'EOD_BASENAME', plugin_basename( __FILE__ ) );
            $this->define( 'EOD_DEFAULT_SETTINGS', array(
                'ndap' => 3,
                'ndape' => 2,
                'scrollbar' => 'on'
            ));

            // Include utility functions.
            include_once EOD_PATH . 'eod-utility-functions.php';

            // Include EOD API.
            eod_include( 'eod-api.php' );

            // Include EOD shortcodes
            eod_include( 'eod-shortcodes.php' );

            // EOD AJAX
            eod_include( 'eod-ajax.php' );

            // Add actions and filters.
            add_action( 'init', array( $this, 'init' ), 5 );
            add_action( 'init', array( $this, 'register_post_types' ), 5 );
            add_action( 'wp_enqueue_scripts',  array( $this, 'client_scripts' ) );
            add_action( 'widgets_init', array( $this, 'register_widgets' ) );

            // Admin panel
            eod_include( 'admin/eod-admin.php' );
            eod_include( 'admin/fundamental-data-presets.php' );
            eod_include( 'admin/financial-presets.php' );
        }

        /**
         * Completes the setup process on "init" of earlier.
         */
        function init() {
            // Bail early if called directly from functions.php or plugin file.
            if ( ! did_action( 'plugins_loaded' ) ) {
                return;
            }

            if(is_admin()) {
                $this->admin = new EOD_Stock_Prices_Admin();
            }
        }

        /**
         * Register post types
         */
        function register_post_types(){
            register_post_type('fundamental-data', array(
                'labels'            => array(
                    'name'              => 'Fundamental Data presets',
                    'singular_name'     => 'Fundamental Data preset',
                    'menu_name'         => 'Fundamental Data preset',
                    'all_items'         => 'Fundamental Data presets',
                    'view_item'         => 'View fundamental data preset',
                    'add_new_item'      => 'Add new fundamental data preset',
                    'add_new'           => 'Add new',
                    'edit_item'         => 'Edit fundamental data preset',
                    'update_item'       => 'Update fundamental data preset',
                    'search_items'      => 'Find fundamental data preset',
                    'not_found'         => 'Not found',
                    'not_found_in_trash' => 'Not found in trash'
                ),
                'description'       => '-',
                'supports'          => array('title'),
                'hierarchical'      => false,
                'public'            => false,
                'show_in_rest'      => false,
                'show_ui'           => true,
                //'show_in_menu'      => 'eod-stock-prices',
                'show_in_menu'      => false,
                'menu_position'     => 3,
                'can_export'        => true,
                'has_archive'       => true,
                'rewrite'           => true,
                'capability_type'   => 'page',
            ));

            register_post_type('financials', array(
                'labels'            => array(
                    'name'              => 'Financial Table presets',
                    'singular_name'     => 'Financial Table preset',
                    'menu_name'         => 'Financial Table preset',
                    'all_items'         => 'Financial Table presets',
                    'view_item'         => 'View financial table preset',
                    'add_new_item'      => 'Add new financial table preset',
                    'add_new'           => 'Add new',
                    'edit_item'         => 'Edit financial table preset',
                    'update_item'       => 'Update financial table preset',
                    'search_items'      => 'Find financial table preset',
                    'not_found'         => 'Not found',
                    'not_found_in_trash' => 'Not found in trash'
                ),
                'description'       => '-',
                'supports'          => array('title'),
                'hierarchical'      => false,
                'public'            => false,
                'show_in_rest'      => false,
                'show_ui'           => true,
                'show_in_menu'      => false,
                'menu_position'     => 3,
                'can_export'        => true,
                'has_archive'       => true,
                'rewrite'           => true,
                'capability_type'   => 'page',
            ));
        }

        /**
         * Register widgets
         */
        public function register_widgets(){
            register_widget( 'EOD_Stock_Prices_Widget' );
            register_widget( 'EOD_News_Widget' );
            register_widget( 'EOD_Fundamental_Widget' );
        }

        /**
         *
         */
        public function client_scripts() {
            $eod_display_settings = get_eod_display_options();

            // Base
            wp_enqueue_script( 'eod_stock-prices-plugin', EOD_URL . 'js/eod-stock-prices.js', array('jquery'), EOD_VER );
            wp_enqueue_style('eod_stock-prices-plugin', EOD_URL . 'css/eod-stock-prices.css', array(), EOD_VER);

            // Add ajax vars
            wp_add_inline_script( 'eod_stock-prices-plugin', 'let eod_ajax_nonce = "'.wp_create_nonce('eod_ajax_nonce').'", eod_ajax_url = "'.admin_url('admin-ajax.php').'";', 'before' );

            // Add display vars
            wp_localize_script( 'eod_stock-prices-plugin', 'eod_display_settings', $this->get_js_parameters());

            // Simple bar
            if( !wp_is_mobile() && $eod_display_settings['scrollbar'] === 'on' ) {
                wp_enqueue_script('simplebar', EOD_URL . 'js/simplebar.min.js', array('jquery'));
                wp_enqueue_style('simplebar', EOD_URL . 'css/simplebar.css');
            }
        }

        /**
         * Return parameters for js scripts
         *
         * @return array
         */
        public function get_js_parameters() {
            global $eod_api;
            $prop_naming = array();
            $financials_lib = $eod_api->get_financials_lib();
            array_walk_recursive ($financials_lib, function($a, $b) use (&$prop_naming) { $prop_naming [$b] = $a; });

            $eod_display_settings = get_option('eod_display_settings');
            return array(
                'ndap' => isset($eod_display_settings['ndap']) ? $eod_display_settings['ndap'] : EOD_DEFAULT_SETTINGS['ndap'],
                'ndape' => isset($eod_display_settings['ndape']) ? $eod_display_settings['ndape'] : EOD_DEFAULT_SETTINGS['ndape'],
                'prop_naming' => $prop_naming
            );
        }

        /**
         * Defines a constant if doesnt already exist.
         *
         * @param string $name The constant name.
         * @param mixed  $value The constant value.
         */
        function define($name, $value = true ) {
            if ( !defined( $name ) ) {
                define( $name, $value );
            }
        }
    }
}


if(class_exists('EOD_Stock_Prices_Plugin')) {
    /*
    *
    * The main function responsible for returning the one true eod Instance to functions everywhere.
    * Use this function like you would a global variable, except without needing to declare the global.
    *
    * Example: <?php $eod = eod(); ?>
    * @param   void
    * @return  EOD_Stock_Prices_Plugin
    */
    function eod()
    {
        global $eod;

        // Instantiate only once.
        if ( ! isset( $eod ) ) {
            $eod = new EOD_Stock_Prices_Plugin();
            $eod->initialize();
        }
        return $eod;
    }

    // Instantiate.
    eod();
}


