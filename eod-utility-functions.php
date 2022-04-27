<?php
/**
 * Get display options with default values
 * @return array
 */
function get_eod_display_options()
{
    return wp_parse_args( get_option( 'eod_display_settings' ), EOD_DEFAULT_SETTINGS );
}


/**
 * Converting saved JSON string in widget to targets list
 * @param array $instance widget data
 * @return array
 */
function eod_get_ticker_list_from_widget_instance($instance)
{
    $targets = array();
    if( isset($instance['target']) && !empty($instance['target']) )
        $targets = json_decode($instance['target'], true);

    // Support old version with flat array
    if(is_array($targets) && count($targets) && is_array( $targets[0] )){
        $list_of_targets = $targets;
    }else{
        // (old version) $targets is an array without parameters
        $list_of_targets = [];
        foreach($targets as $item) {
            $list_of_targets[] = array(
                'target' => $item
            );
        }
    }
    return $list_of_targets;
}

/**
 * Static load template method
 * @param $templatePath
 * @param $vars
 * @return string
 */
function eod_load_template($templatePath, $vars)
{
    //Load template
    $template = EOD_PATH.$templatePath;
    ob_start();
    extract($vars);
    include $template;
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

/**
 * Includes a file within the EOD plugin.
 *
 * @param string $filename The specified file.
 * @return void
 */
function eod_include( $filename = '' ) {
    $file_path = EOD_PATH . ltrim( $filename, '/' );
    if ( file_exists( $file_path ) ) {
        include_once $file_path;
    }
}

/**
 * Display sortable flat list
 *
 * @param string $saved_json saved list in json format
 * @param array $fd_lib array with parameter titles
 */
function eod_display_saved_list($saved_json, $fd_lib ){
    global $eod_api;
    $list = json_decode( $saved_json );
    foreach ($list as $slug){
        // Define title
        $path = explode('->', $slug);
        $buffer = $fd_lib;
        foreach ($path as $key){
            if(!isset($buffer[$key])) break;
            $buffer = $buffer[$key];
        }
        $title = is_string($buffer) ? $buffer : $slug;

        echo "<li>
                <span data-slug='$slug'>
                    $title
                    <button title='remove item'>-</button>
                </span>
              </li>";
    }
}

/**
 * Display sortable source list
 *
 * @param array $list
 * @param array $path list of keys
 */
function eod_display_source_list(array $list, $path = array(), $display_group = array()) {
    foreach ($list as $key=>$var){
        if( $key[0] === '_' ) continue;
        $class_list = array('draggable');
        $slug = implode('->', array_merge($path, [$key]));
        $depth = count($path)+1;

        // If a display group is specified, then need to check if the current parameter is in this group.
        $in_group = true;
        $current_group = array_merge($path, [$key]);
        foreach ($display_group as $i => $k){
            if ($current_group[$i] !== $k)
                $in_group = false;

            // Break loop
            // Current item already not in group or next key out of range
            if ($in_group === false || $i+2 > count($current_group))
                break;
        }
        if($in_group === false)
            $class_list[] = 'hide';

        // Display item
        if( is_array($var) ){
            // deepen
            $path[] = $key;

            echo "<li class='has_child ".implode(' ', $class_list)."'>
                    <span style='padding-left: ".($depth*10)."px;' data-slug='$slug'>
                        $key
                        <button title='add whole group'>+</button>
                    </span>";
            echo   '<ul>';
            eod_display_source_list($var, $path, $display_group);
            echo   '</ul>';
            echo '</li>';

            // get up
            array_pop($path);

        }else {
            echo "<li class='".implode(' ', $class_list)."'>
                    <span style='padding-left: ".($depth*10)."px;' data-slug='$slug'>
                        $var
                        <button title='add item'>+</button>
                    </span>
                  </li>";
        }
    }
}
