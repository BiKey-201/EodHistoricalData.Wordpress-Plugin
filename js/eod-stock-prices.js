
let eod_api_token = '';
jQuery(function(){
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
    let newValue = value;
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
    })
    .done((data) => { callback(data); });
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
     loading and displaying all live tickers
   ========================================= */
function eod_display_all_live_tickers(){
    // Finding and prepare all tickers. Creating list.
    let eod_t_list = [];

    jQuery(".eod_live").each(function(){
        let target = jQuery(this).attr('data-target');
            
        // Common ticker
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

            // Class target
            let trg = target.toLowerCase().split('.'),
                $ul = jQuery('.eod_fd_list.eod_t_'+trg.join('_')),
                $table_box = jQuery('.eod_financials.eod_t_'+trg.join('_'));

            // for simple list data
            $ul.find('> li').each(function(){
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

                // Display string or number value
                if( ['number', 'string'].indexOf(typeof value) > -1 ) {
                    $li.append('<span>'+value+'</span>');
                }else if(typeof value === 'object'){
                    let items_list = '';
                    for (let item of value) {
                        let keys_list = '';
                        for (let key in item) {
                            keys_list += '<li><b>'+key+': </b><span>'+item[key]+'</span></li>';
                        }
                        items_list += '<li><ul>'+keys_list+'</ul></li>';
                    }
                    $li.append('<ul>'+items_list+'</ul>');
                }
            });

            // for financials tables

            $table_box.each(function(){
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
function eod_render_financial_table($table_box) {
    let financials_list = $table_box.data('data');
    if (!financials_list) return;

    let $table = $table_box.find('.eod_tbody'),
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
    let $header = jQuery('<div class="header"><div><span>Currency: '+currency+'</span></div></div>'),
        dates = [];
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
    $header.append( dates.reverse() );
    $table.append( $header );

    // Another rows of stats
    for(let parameter of parameters.split(';')){
        let $row = jQuery('<div></div>');

        // First column of parameters names
        let display_name = eod_display_settings.prop_naming[parameter];
        if(!display_name) display_name = parameter;
        $row.append( jQuery('<div><span title="'+ display_name +'">'+ display_name +'</span></div>') )

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
            cols.push( jQuery('<div>'+ (value === '' ? '-' : value) +'</div>') );
        }

        $row.append( cols.reverse() )
        $table.append( $row );
    }
}