<?php
/**
 * Created by IntelliJ IDEA.
 * User: slabre
 * Date: 02/12/2017
 * Time: 14:12
 */

if(!class_exists('EOD_Stock_Prices_Plugin')) {
    class EOD_Fundamental_Widget extends WP_Widget
    {
        public static $widget_base_id = 'EOD_Fundamental_Widget';

        function __construct()
        {
            parent::__construct(
                self::$widget_base_id,
                __('EOD Fundamental Data', 'eod-stock-prices'),
                array('description' => __('-', 'eod-stock-prices'))
            );
        }

        /*
         * Display on the site
         */
        public function widget($args, $instance)
        {
            // Get Fundamental Data preset
            if($instance['preset'] && is_numeric($instance['preset'])) {
                $fd_list = get_post_meta($instance['preset'], '_fd_list', true);

                if($fd_list === '')
                    $fd_list = array('error' => 'Preset not found');
                else
                    $fd_list = json_decode( $fd_list );

            }else {
                $fd_list = array('error' => 'Wrong preset id');
            }

            $widget_html = eod_load_template(
                "widget/template/fundamental-widget.php",
                array(
                    '_this'              => $this,
                    'args'               => $args,
                    'target'             => $instance['target'],
                    'fd_list'            => $fd_list,
                    'title'              => apply_filters('widget_title', $instance['title'])
                )
            );

            echo $widget_html;
        }

        /*
         * Display in admin panel
         */
        public function form($instance)
        {
            $fd_presets = get_posts([
                'post_type' => 'fundamental-data',
                'post_status' => 'publish',
                'numberposts' => -1
            ]);

            $widget_html = eod_load_template(
                "widget/template/fundamental-widget-form.php",
                array(
                    '_this'             => $this,
                    'fd_presets'        => $fd_presets,
                    'target'            => isset($instance['target']) ? $instance['target'] : '',
                    'preset'            => isset($instance['preset']) ? $instance['preset'] : '',
                    'widget_title'      => isset($instance['title']) ? $instance['title'] : '',
                    'eod_options'       => get_option('eod_options'),
                )
            );

            echo $widget_html;
        }

        /*
         * Update widget data
         */
        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
            $instance['target'] = (!empty($new_instance['target'])) ? strip_tags($new_instance['target']) : '';
            $instance['preset'] = (!empty($new_instance['preset'])) ? $new_instance['preset'] : '';
            return $instance;
        }
    }
}