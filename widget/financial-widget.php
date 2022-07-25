<?php
/**
 * Created by IntelliJ IDEA.
 * User: slabre
 * Date: 02/12/2017
 * Time: 14:12
 */

if(!class_exists('EOD_Stock_Prices_Plugin')) {
    class EOD_Financial_Widget extends WP_Widget
    {
        public static $widget_base_id = 'EOD_Financial_Widget';

        function __construct()
        {
            parent::__construct(
                self::$widget_base_id,
                __('EOD Financial Table', 'eod-stock-prices'),
                array('description' => __('-', 'eod-stock-prices'))
            );
        }

        /*
         * Display on the site
         */
        public function widget($args, $instance)
        {
            $financial_group = '';

            // Get Financial Table preset
            if($instance['preset'] && is_numeric($instance['preset'])) {
                $financials_list = get_post_meta($instance['preset'], '_financials_list', true);
                $financial_group = get_post_meta($instance['preset'], '_financial_group', true);

                if($financials_list === '') {
                    $financials_list = array('error' => 'Preset not found');
                }else {
                    $financials_list = json_decode($financials_list);
                    foreach ($financials_list as &$item){
                        $path = explode('->', $item);
                        $item = end($path);
                    }
                }

            }else {
                $financials_list = array('error' => 'Wrong preset id');
            }

            // Years
            $years = '';
            if( $instance['year_from'] || $instance['year_to'] )
                $years = $instance['year_from'] . '-' . $instance['year_to'];

            $widget_html = eod_load_template(
                "widget/template/financial-widget.php",
                array(
                    '_this'              => $this,
                    'args'               => $args,
                    'target'             => $instance['target'],
                    'years'              => $years,
                    'financials_list'    => $financials_list,
                    'financial_group'    => $financial_group,
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
            $financial_presets = get_posts([
                'post_type' => 'financials',
                'post_status' => 'publish',
                'numberposts' => -1
            ]);

            $widget_html = eod_load_template(
                "widget/template/financial-widget-form.php",
                array(
                    '_this'             => $this,
                    'financial_presets' => $financial_presets,
                    'target'            => isset($instance['target']) ? $instance['target'] : '',
                    'preset'            => isset($instance['preset']) ? $instance['preset'] : '',
                    'year_from'         => isset($instance['year_from']) ? $instance['year_from'] : '',
                    'year_to'           => isset($instance['year_to']) ? $instance['year_to'] : '',
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
            $instance['year_from'] = $new_instance['year_from'] ? $new_instance['year_from'] : '';
            $instance['year_to'] = $new_instance['year_to'] ? $new_instance['year_to'] : '';
            return $instance;
        }
    }
}