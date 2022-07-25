let eod_api_token = '';
document.addEventListener('DOMContentLoaded', (e) => {
    eod_init();
});

async function eod_init(){
    eod_api_token = await jQuery.ajax({
        method: "POST",
        url: eod_ajax_url,
        data: {
            'action': 'get_eod_token',
            'nonce_code': eod_ajax_nonce,
        }
    });

    eod_display_all_live_tickers();
    eod_display_fundamental_data();
    eod_init_realtime_tickers()
    eod_display_all_historical_tickers();
    // Display items by AJAX
    if( eod_display_settings.news_ajax && eod_display_settings.news_ajax === 'on' ) {
        eod_display_news();
    }

    // Refresh tickers every minute. It will affect your daily API Limit!
    const EOD_refresh_common_tickers = false,
        EOD_refresh_interval = 60000;             // 60000 = 1 minute
    if(EOD_refresh_common_tickers){
        let EOD_refresh = setInterval(function () {
            console.log("EOD_refresh_common_tickers");
            eod_display_all_live_tickers();
        }, EOD_refresh_interval);
    }
}


function abbreviateNumber(value) {
    // Check type
    let newValue;
    if(typeof value === 'string'){
        newValue = +value;
    }else{
        newValue = value;
    }

    if(isNaN(newValue) || typeof newValue !== 'number')
        return value;

    if (value >= 1000 || value <= -1000) {
        let shift = 1,
            suffixes = ["", "K", "M", "B", "T"],
            suffixNum = Math.floor( ((""+Math.floor(value)).length - shift)/3 ),
            shortValue = suffixNum !== 0 ? (value / Math.pow(1000,suffixNum)) : value;

        if (shortValue % 1 !== 0)  shortValue = shortValue.toFixed(2);
        newValue = shortValue+suffixes[suffixNum];
    }
    return newValue;
}

function get_eod_fundamental(target, callback){
    if(typeof callback !== 'function' || !target) return false;

    jQuery.ajax({
        dataType: "json",
        method: "POST",
        url: eod_ajax_url,
        data: {
            'action': 'get_fundamental_data',
            'nonce_code': eod_ajax_nonce,
            'target': target
        }
    }).always((data) => {
        if(data.error) console.log('EOD-error: ' +data.error, target);
    }).done((data) => {
        callback(data);
    });
}


function get_eod_ticker(type = 'historical', list, callback){
    if(typeof callback !== 'function' || !jQuery.isArray(list) || list.length < 1) return false;

    jQuery.ajax({
        dataType: "json",
        method: "POST",
        url: eod_ajax_url,
        data: {
            'action': 'get_real_time_ticker',
            'nonce_code': eod_ajax_nonce,
            'list': list,
            'type': type
        }
    }).always((data) => {
        if(data.error) console.log('EOD-error: ' +data.error, type, list);
    })
    .done((data) => { callback(data); });
}

function render_eod_ticker(type, target, value, prevValue = false){       
    if(!target) return false;

    // Display settings
    // ndap - number of digits after decimal point (base value)
    // ndape - (evolution value)
    let ndap = eod_display_settings.ndap,
        ndape = eod_display_settings.ndape;

    // The ticker can be without the other half.
    let trg = target.toLowerCase().split('.'),
        full_t_class = '.'+type+'.eod_t_'+trg.join('_'),     // AAPL.US
        t_class = '.'+type+'.eod_t_'+trg[0],                 // AAPL
        $tickers = jQuery(full_t_class+', '+t_class);

    // Display error
    if(!value || value === 'NA') {
        $tickers.text('no result from real time api').closest('.eod_ticker').addClass('error');
        return false;
    }

    // Display data
    $tickers.each(function(){
        let $item = jQuery(this);

        // Check local display settings
        let local_ndap = $item.attr('data-ndap') ? parseInt($item.attr('data-ndap')) : ndap,
            local_ndape = $item.attr('data-ndape') ? parseInt($item.attr('data-ndape')) : ndape;

        // Close value eod_display_settings
        value = parseFloat(value).toFixed( local_ndap );
        $item.text(value);

        // Evolution
        if(!prevValue || value === '-') return false;
        let evolution = parseFloat(value - prevValue).toFixed( local_ndape ),
            evol_html = '(<span role="value">'+(evolution > 0 ? '+' : '')+evolution+'</span>)';
        $item.siblings('.evolution').html(evol_html).closest('.eod_ticker')
            .toggleClass('plus', evolution > 0)
            .toggleClass('equal', evolution == 0)
            .toggleClass('minus', evolution < 0);
    });
}

/* =========================================
     loading and displaying all financial news
   ========================================= */
/**
 * Initiate the loading and display financial news on the page.
 * @param $items jQuery list of EOD news boxes. Default displaying all .eod_news_list elements on page.
 */
function eod_display_news( $items = false ){
    if($items && !($items instanceof jQuery)) return;
    if(!$items) $items = jQuery(".eod_news_list");

    $items.each(function(){
        eod_display_news_item( jQuery(this) );
    });
}

/**
 * Loading and display financial news for the current item
 * @param $box
 */
function eod_display_news_item( $box ){
    if(!($box instanceof jQuery)) return;
    $box = $box.eq(0);

    // Loading animation
    $box.addClass('eod_loading');

    // Collect parameters
    let props = {};
    for(let prop of ['target','tag','from','to','limit','pagination']) {
        let val = $box.attr('data-' + prop);
        if(val) props[prop] = val;
    }
    if(!props.target && !props.tag) return false;

    // Get and display news html
    jQuery.ajax({
        dataType: "json",
        method: "POST",
        url: eod_ajax_url,
        data: {
            'action': 'get_eod_financial_news',
            'nonce_code': eod_ajax_nonce,
            'props': props
        }

    }).always((data) => {
        $box.data('target', props.target).removeClass('eod_loading');
        if(!data) console.log('EOD-error: empty news response', props);

    }).done((data) => {
        if(!data || data.error) return false;

        // Sort by date
        data.sort(function(a,b){
            return new Date(b.date) - new Date(a.date);
        });
        // Discard the excess and duplicates
        let whitelist = [], res = [];
        for(let i=0; i<data.length; i++){
            let slug = data[i].date + data[i].title;
            if( whitelist.indexOf(slug) === -1 ){
                res.push(data[i]);
                whitelist.push(slug);
            }
        }
        if(props.limit) res = res.slice(0, parseInt(props.limit));

        // Render
        eod_render_news_item($box, res);
    });
}
function eod_render_news_item( $box, data ){
    // Save data
    $box.data('data', data);

    // Remove old list and pagination
    $box.find('.list, .eod_pagination').remove();

    // Add pagination
    let pagination = $box.attr('data-pagination'),
        limit = pagination ? Math.abs(pagination) : data.length,
        last_page = Math.ceil(data.length/limit);
    if(pagination) {
        let $pagination = jQuery('\
                <div class="eod_pagination start">\
                    <button class="prev"></button>\
                    <span>Page</span>\
                    <input type="number" min="1" value="1" max="' + last_page + '">\
                    <span>of ' + last_page + '</span>\
                    <button class="next"></button>\
                </div>');

        // Change page event
        $pagination.find('input[type=number]').on('change', function () {
            let $input = jQuery(this),
                $box = $input.closest('.eod_news_list');

            // Check range
            if (parseInt($input.val()) < 1)
                $input.val(1);
            if (parseInt($input.val()) > parseInt($input.attr('max')))
                $input.val($input.attr('max'));

            // Check last and fist page
            console.log($pagination);
            $pagination.toggleClass('start', parseInt($input.val()) === 1);
            $pagination.toggleClass('end', $input.val() === $input.attr('max'));

            // Change news list
            eod_set_news_page($box, parseInt($input.val()));
        });

        // Click on arrow button
        $pagination.find('button').on('click', function () {
            let $input = jQuery(this).siblings('input').eq(0),
                d = jQuery(this).hasClass('next') ? 1 : -1,
                current_page = parseInt($input.val()),
                max_page = $input.attr('max'),
                next_page = current_page + d;

            if (next_page < 1 || next_page > max_page) return false;

            $input.val(next_page).change();
        });

        $box.prepend($pagination);

        // Add news list container
        $box.prepend( jQuery('<div class="list"></div>') )

        eod_set_news_page($box);
    }
}
function eod_set_news_page( $box, page = 1 ){
    let data = $box.data('data'),
        news = [],
        limit = $box.attr('data-pagination') ? parseInt($box.attr('data-pagination')) : data.length,
        offset = (page-1)*limit;
    for(let i=0; i<limit && (offset+i)<data.length; i++)
        news.push( eod_news_item_html( data[offset+i] ) );
    $box.find('.list').html(news);
}
function eod_news_item_html( item ){
    // Tags
    let tags = '';
    for(let tag of item.tags)
        tags += '<li>'+tag+'</li>';

    // Datetime
    let display_date, number,
        timestamp = new Date( item.date ).getTime(),
        now = new Date().getTime(),
        time_ago = (now - timestamp)/1000;
    if(time_ago > 24*3600){
        let date_options = {year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric'};
        display_date = new Date(timestamp).toLocaleDateString("en-US", date_options);
    }else{
        if(time_ago > 3600){
            number = Math.floor(time_ago/3600);
            display_date = number + ( number>1 ? ' hours ago' : ' hour ago');
        }else{
            number = Math.floor(time_ago/60);
            display_date = number + ( number>1 ? ' minutes ago' : ' minute ago');
        }
    }

    return '\
        <div class="eod_news_item">\
            <div class="thumbnail"></div>\
            <a rel="nofollow" target="_blank" class="h" href="'+item.link+'">'+item.title+'</a>\
            <time dateTime="'+item.date+'" class="date">'+display_date+'</time>\
            <blockquote cite="'+item.link+'">\
                <div class="description">\
                    '+ item.content.substring(0, 300) +'\
                </div>\
            </blockquote>\
            <ul class="tags">'+tags+'</ul>\
        </div>';
}

/* =========================================
     loading and displaying all live tickers
   ========================================= */
function eod_display_all_live_tickers(){
    // Finding and prepare all tickers. Creating list.
    let eod_t_list = [];

    jQuery(".eod_live").each(function(){
        let target = jQuery(this).attr('data-target');
        if( eod_t_list.indexOf(target) === -1 )
            eod_t_list.push(target);
    });
    
    // Get and display close value
    get_eod_ticker('live', eod_t_list, function(data){
        if(!data || data.error) return false;
        if(data.code){
            render_eod_ticker('eod_live', data.code, data.close, data.previousClose);
        }else if(data['0']){
            for(const [key, item] of Object.entries(data)){
                render_eod_ticker('eod_live', item.code, item.close, item.previousClose);
            }
        }
    });
}


/* =========================================
     loading and displaying all historical tickers
   ========================================= */
function eod_display_all_historical_tickers(){
    // Finding and prepare all tickers. Creating list.
    let eod_t_list = [];

    jQuery(".eod_historical").each(function(){
        let target = jQuery(this).attr('data-target');
            
        // Common ticker
        if( eod_t_list.indexOf(target) === -1 )
            eod_t_list.push(target);
    });
    
    // Get and display close value
    get_eod_ticker('historical', eod_t_list, function(data){
        if(!data || data.error) return false;
        if(data.code){
            render_eod_ticker('eod_historical', data.code, data.close, data.previousClose);
        }else if(data['0']){
            for(const [key, item] of Object.entries(data)){
                render_eod_ticker('eod_historical', item.code+'.'+item.exchange_short_name, item.close, item.prev_close);
            }
        }
    });
}

/* =========================================
     init display realtime tickers
   ========================================= */
function eod_init_realtime_tickers(){
    // Finding and prepare all tickers. Creating list.
    let eod_rt_list = {
        'cc':     [],
        'forex':  [],
        'us':     []
    };
    jQuery(".eod_realtime").each(function(){
        let target = jQuery(this).attr('data-target'),
            [code, type] = target.toLowerCase().split('.');
            
        // Realtime ticker
        if(type && eod_rt_list[type] && eod_rt_list[type].indexOf( code ) === -1)
           eod_rt_list[type].push(code); 
    });
    
    // Create websocket
    const ws_eod = {
        'cc':     new WebSocket('wss://ws.eodhistoricaldata.com/ws/crypto?api_token='+eod_api_token),
        'forex':  new WebSocket('wss://ws.eodhistoricaldata.com/ws/forex?api_token='+eod_api_token),
        'us':     new WebSocket('wss://ws.eodhistoricaldata.com/ws/us?api_token='+eod_api_token)
    };
    for(let type in eod_rt_list){
        if(eod_rt_list[type].length){
            ws_eod[type].addEventListener('open', function(event){
                for(let q of eod_rt_list[type]){
                    ws_eod[type].send('{"action": "subscribe", "symbols": "'+q.toUpperCase()+'"}');
                }
            });
        }
    }

    ws_eod.cc.addEventListener('message', function(event){
        let res = JSON.parse(event.data);
        if(res.p) render_eod_ticker('eod_realtime', res.s+'.CC', res.p);
    });
    ws_eod.forex.addEventListener('message', function(event){
        let res = JSON.parse(event.data);
        if(res.a) render_eod_ticker('eod_realtime', res.s+'.FOREX', res.a);
    });
    ws_eod.us.addEventListener('message', function(event){
        let res = JSON.parse(event.data);
        if(res.p) render_eod_ticker('eod_realtime', res.s+'.US', res.p);
    });
}

/* =========================================
     loading and displaying fundamental data
   ========================================= */
function eod_display_fundamental_data(){
    // Fundamental data include simple list data (.eod_fd_list) and financials tables (.eod_financials)
    // Finding and prepare all tickers. Creating list.
    let eod_t_list = [];

    jQuery(".eod_fd_list, .eod_financials").each(function(){
        let target = jQuery(this).attr('data-target');

        // Common ticker
        if( eod_t_list.indexOf(target) === -1 )
            eod_t_list.push(target);
    });

    // Get Fundamental Data and display
    for(let target of eod_t_list) {
        get_eod_fundamental(target, function (data) {
            if(!data || data.error) return false;

            // Find fundamental data elements
            let trg = target.toLowerCase().split('.'),
                $fd_list = jQuery('.eod_fd_list.eod_t_'+trg.join('_')),
                $financials_table = jQuery('.eod_financials.eod_t_'+trg.join('_'));

            // Render data
            // for simple data list
            $fd_list.find('> li').each(function(){
                let $li = jQuery(this),
                    slug = $li.attr('data-slug');
                if(!slug) return;

                // Find value in data
                let path = slug.split('->'),
                    value = data;
                for(let key of path) {
                    if (value[key] === undefined) return;
                    value = value[key];
                }

                // Display string or number value as string
                if( ['number', 'string', 'undefined'].indexOf(typeof value) > -1 ) {
                    $li.append('<span>'+ abbreviateNumber(value) +'</span>');

                // Display object as table
                }else if(typeof value === 'object' && value !== null){
                    let Table = new EodCreateTable({
                            type: 'table'
                        });

                    // The parameter item may contain a title in its own key
                    let has_row_name = !Array.isArray(value);

                    // Table header
                    let [first_item_key] = Object.keys(value);
                    if( typeof( value[first_item_key] ) === 'object' ) {
                        if(has_row_name){
                            Table.set_header( ['', ...Object.keys(value[first_item_key])] );
                        }else{
                            Table.set_header( Object.keys(value[first_item_key]) );
                        }
                    }

                    // Table body
                    for (let [index, item] of Object.entries(value)) {
                        let values_list = typeof item === 'object' ? Object.values(item) : [item];
                        if(has_row_name){
                            Table.add_row( [index, ...values_list] );
                        }else{
                            Table.add_row( values_list );
                        }
                    }

                    // Show table
                    let $wrapper = jQuery('<div class="eod_table_wrapper"></div>');
                    $wrapper.append( Table.get_table() )
                    let simple_bar = new SimpleBar( $wrapper[0] );
                    $li.append( $wrapper );
                }
            });

            // for financials tables
            $financials_table.each(function(){
                // Save data in element
                jQuery(this).data('data', data);

                // Render table
                eod_render_financial_table( jQuery(this) );
            });
        });
    }
}

/* =========================================
           render financial table
   ========================================= */
function eod_render_financial_table( $table_box ) {
    let financials_list = $table_box.data('data');
    if (!financials_list) return;

    let Table = new EodCreateTable({
            type: 'div'
        }),
        selected_timeline = $table_box.data('selected_timeline'),
        group = $table_box.attr('data-group') ? $table_box.attr('data-group').split('->') : false,
        parameters = $table_box.attr('data-cols'),
        years = $table_box.attr('data-years');

    if (!group || !parameters) return;

    // The source data may contain separate arrays: 'yearly', 'quarterly'.
    // Or without them, but assuming that the list of data has the same gradation
    // either by 'yearly' or by 'quarterly'.
    // This parameter determines which key to use or how interpret date keys in the list.
    let timeline_type = false;
    timeline_type = eod_display_settings.prop_naming['_timeline_' + group[group.length - 1]];

    if( !selected_timeline )
        selected_timeline = timeline_type === 'both' ? 'yearly' : timeline_type;


    // Define data group
    while (group.length) {
        let key = group.shift();
        if (financials_list[key]) financials_list = financials_list[key];
        else break;
    }

    // Define currency. Not every group contain parameter.
    let currency = financials_list.currency_symbol;

    // Financials list may contain separate arrays: 'yearly', 'quarterly'.
    // Select specific
    if (timeline_type === 'both'){
        if (financials_list[selected_timeline]) financials_list = financials_list[selected_timeline];
        else return;
    }

    // If 'currency_symbol' not found select first item and get currency.
    // This method cannot be used as the main one because not every item has currency/currency_symbol.
    if(!currency) currency = Object.values( financials_list )[0].currency;

    // Prepare time interval
    if(years){
        years = years.split('-');
        if(years.length < 2) years = false;
    }

    // Add timeline toggle
    if( timeline_type === 'both' && $table_box.children('.eod_toggle').length === 0 ) {
        let $toggle = jQuery('<button class="eod_toggle timeline">\
                                <span>Annual</span>\
                                <span>Quarterly</span>\
                              </button>');

        // Toggle default option
        $toggle.find('span').eq( selected_timeline === 'quarterly' ? 1 : 0 ).addClass('selected');

        // Toggle event
        $toggle.click(function (e) {
            let $target = jQuery(e.target);
            if( $target.hasClass('selected') ) return;
            jQuery(this).toggleClass('on');
            jQuery(this).find('span').toggleClass('selected');
            $table_box.data('selected_timeline', jQuery(this).hasClass('on') ? 'quarterly' : 'yearly');
            eod_render_financial_table($table_box);
        });
        $table_box.prepend($toggle);
    }

    // Remove old tables
    $table_box.find('.eod_tbody').html('');

    // First header row
    let dates = [];
    for(let [date, item] of Object.entries( financials_list )){
        let d = new Date( date ),
            y = d.getFullYear(),
            m = d.getMonth()+1,
            display_date = '';

        // Filter by date interval
        if(years && !( (!years[0] || years[0] <= y) && (!years[1] || y <= years[1]) ))
            continue;

        if(selected_timeline === 'yearly')
            display_date = y;
        else if(selected_timeline === 'quarterly'){
            display_date = 'Q' + Math.ceil(m/3) + " '" + y;
        }

        dates.push('<div>'+ display_date +'</div>');
    }
    Table.set_header( ['<span>Currency: '+currency+'</span>', ...dates.reverse()] );

    // Another rows of stats
    for(let parameter of parameters.split(';')){
        // First column of parameters names
        let display_name = eod_display_settings.prop_naming[parameter];
        if(!display_name) display_name = parameter;
        display_name = '<span title="'+ display_name +'">'+ display_name +'</span>';

        // Another columns of parameters values
        let cols = [];
        for(let [date, item] of Object.entries( financials_list )){
            let value = '',
                d = new Date( date ),
                y = d.getFullYear();

            // Filter by date interval
            if(years && !( (!years[0] || years[0] <= y) && (!years[1] || y <= years[1]) ))
                continue;

            if(item[parameter] === 0 || item[parameter]) value = abbreviateNumber(item[parameter]);
            cols.push( (value === '' ? '-' : value) );
        }

        Table.add_row( [display_name, ...cols.reverse()] );
    }

    // Show table
    $table_box.find('.eod_tbody').replaceWith( Table.get_tbody() );
}


function getTextWidth(text, font) {
    // re-use canvas object for better performance
    const canvas = getTextWidth.canvas || (getTextWidth.canvas = document.createElement("canvas"));
    const context = canvas.getContext("2d");
    context.font = font;
    const metrics = context.measureText(text);
    return metrics.width;
}

function getCssStyle(element, prop) {
    return window.getComputedStyle(element, null).getPropertyValue(prop);
}

function getCanvasFontSize(el = document.body) {
    const fontWeight = getCssStyle(el, 'font-weight') || 'normal';
    const fontSize = getCssStyle(el, 'font-size') || '16px';
    const fontFamily = getCssStyle(el, 'font-family') || 'Times New Roman';

    return `${fontWeight} ${fontSize} ${fontFamily}`;
}

class EodCreateTable {
    constructor(p) {
        const _this = this;
        _this.header = [];
        _this.rows = [];
        _this.type = p.type ? p.type : 'div';
        _this.template = {
            table: {
                table: 'table',
                tbody: 'tbody',
                row: 'tr',
                header: 'th',
                cell: 'td'
            },
            div: {
                table: 'div',
                tbody: 'div',
                row: 'div',
                header: 'div',
                cell: 'div'
            }
        }[_this.type];
    }

    set_header( list ) {
        const _this = this;
        _this.header_list = list;
    }

    add_row( list ) {
        const _this = this;
        _this.rows.push( list );
    }

    get_tbody() {
        const _this = this;
        let tag = _this.template,
            $tbody = jQuery('<'+tag.tbody+' class="eod_tbody"></'+tag.tbody+'>');

        // Header
        if ( Array.isArray( _this.header_list ) && _this.header_list.length ){
            let $header = jQuery('<'+tag.row+' class="header"></'+tag.row+'>');
            for (let item of _this.header_list) {
                $header.append('<'+tag.header+'>' + item + '</'+tag.header+'>');
            }
            $tbody.append($header);
        }

        // Body
        for( let row of _this.rows ){
            let $row = jQuery('<'+tag.row+'></'+tag.row+'>');
            for( let item of row ){
                $row.append('<'+tag.cell+'>' + item + '</'+tag.cell+'>');
            }
            $tbody.append($row);
        }

        return $tbody;
    }

    get_table() {
        let tag = this.template,
            $table = jQuery('<'+tag.table+' class="eod_table"></'+tag.table+'>');
        $table.append( this.get_tbody() )
        return $table;
    }

    //     max_first_col_width = 0,
    //     font_styles = 'normal 12px ' + getCssStyle(document.body, 'font-family') || 'Times New Roman';

    // for (let [index, item] of Object.entries(value)) {
    //     let $row = jQuery('<div><div>'+index+'</div></div>'),
    //         first_col_width = getTextWidth( index, font_styles );
    //
    //     if( first_col_width > max_first_col_width ) max_first_col_width = first_col_width;
    // }
    //
    // max_first_col_width += 15;
    // $table.find('.eod_tbody > div > div:first-child').css({'width': max_first_col_width + 'px'});
}