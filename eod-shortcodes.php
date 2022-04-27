<?php
/**
 * Shortcode initialization
 */
function eod_shortcodes_init(){
    // Fundamental data
    add_shortcode('eod_fundamental', 'eod_shortcode_fundamental');
    add_shortcode('eod_financials', 'eod_shortcode_financials');
    // News
    add_shortcode('eod_news', 'eod_shortcode_news');
    // Tickers
    add_shortcode('eod_historical', 'eod_shortcode_historical');
    add_shortcode('eod_live', 'eod_shortcode_live');
    add_shortcode('eod_realtime', 'eod_shortcode_realtime');
}
eod_shortcodes_init();


/**
 * Shortcode EOD Fundamental Data
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_fundamental($atts=[], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target' => false,
        'id' => false,
    ], $atts, $tag);

    // Get Fundamental Data preset
    if($shortcode_atts['id'] && is_numeric($shortcode_atts['id'])) {
        $fd_list = get_post_meta($shortcode_atts['id'], '_fd_list', true);

        if($fd_list === '')
            $fd_list = array('error' => 'Preset not found');
        else
            $fd_list = json_decode( $fd_list );

    }else {
        $fd_list = array('error' => 'Wrong preset id');
    }

    return eod_load_template("template/fundamental.php", array(
        'fd_list' => $fd_list,
        'target'  => $shortcode_atts['target'],
        'key'     => str_replace('.', '_', strtolower($shortcode_atts['target']))
    ));
}

/**
 * Shortcode EOD Financials
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_financials($atts=[], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target' => false,
        'timeline' => 'yearly',
        'years' => false,
        'id' => false,
    ], $atts, $tag);

    // Get Financials preset
    $financial_group = '';
    if($shortcode_atts['id'] && is_numeric($shortcode_atts['id'])) {
        $financials_list = get_post_meta($shortcode_atts['id'], '_financials_list', true);
        $financial_group = get_post_meta($shortcode_atts['id'], '_financial_group', true);

        if($financials_list === '' || $financial_group === '') {
            $financials_list = array('error' => 'Preset [' . $shortcode_atts['id'] . '] not found');
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

    return eod_load_template("template/financials.php", array(
        'financials_list' => $financials_list,
        'financial_group' => $financial_group,
        'target'          => $shortcode_atts['target'],
        'years'           => $shortcode_atts['years'],
        'key'             => str_replace('.', '_', strtolower($shortcode_atts['target']))
    ));
}

/**
 * Shortcode EOD News
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_news($atts=[], $content = null, $tag = '')
{
    global $eod_api;

    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target' => false,
        'tag'    => false,
        'limit'  => 50,
        'offset' => 0,
        'from'   => false,
        'to'     => false
    ], $atts, $tag);

    if($shortcode_atts['target'] || $shortcode_atts['tag'])
        $news = $eod_api->get_news($shortcode_atts['target'], array(
            'tag'    => $shortcode_atts['tag'],
            'limit'  => intval($shortcode_atts['limit']),
            'offset' => intval($shortcode_atts['offset']),
            'from'   => $shortcode_atts['from'],
            'to'     => $shortcode_atts['to']
        ));
    else
        $news = array('error' => 'wrong target or topic');

    return eod_load_template("template/news.php", array(
        'news' => $news
    ));
}


/**
 * Shortcode EOD Ticker
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_historical($atts=[], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target'  => false,
        'title'   => false,
        'ndap'    => false,
        'ndape'    => false
    ], $atts, $tag);

    return eod_load_template("template/ticker.php", array(
        'type'       => 'eod_historical',
        'target'     => $shortcode_atts['target'],
        'title'      => $shortcode_atts['title'],
        'ndap'       => $shortcode_atts['ndap'],
        'ndape'      => $shortcode_atts['ndape'],
        'key'        => str_replace('.', '_', strtolower($shortcode_atts['target']))
    ));
}


/**
 * Shortcode EOD Live
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_live($atts=[], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target'  => false,
        'title'   => false,
        'ndap'    => false,
        'ndape'    => false
    ], $atts, $tag);

    return eod_load_template("template/ticker.php", array(
        'type'       => 'eod_live',
        'target'     => $shortcode_atts['target'],
        'title'      => $shortcode_atts['title'],
        'ndap'       => $shortcode_atts['ndap'],
        'ndape'      => $shortcode_atts['ndape'],
        'key'        => str_replace('.', '_', strtolower($shortcode_atts['target']))
    ));
}


/**
 * Shortcode EOD Realtime
 * @param array $atts
 * @param null $content
 * @param string $tag
 * @return string
 */
function eod_shortcode_realtime($atts=[], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $shortcode_atts = shortcode_atts([
        'target'  => false,
        'title'   => false,
        'ndap'    => false
    ], $atts, $tag);

    $error = false;
    $key_target = explode('.', strtolower($shortcode_atts['target']) );
    if(count($key_target) !== 2) $error = 'wrong target';

    return eod_load_template("template/realtime_ticker.php", array(
        'error'      => $error,
        'target'     => $shortcode_atts['target'],
        'title'      => $shortcode_atts['title'],
        'ndap'       => $shortcode_atts['ndap'],
        'key'        => implode('_', $key_target)
    ));
}
