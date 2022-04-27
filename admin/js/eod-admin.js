// jQuery debounce
!function(t,n){var o,u=t.jQuery||t.Cowboy||(t.Cowboy={});u.throttle=o=function(t,o,e,i){var r,a=0;function c(){var u=this,c=+new Date-a,f=arguments;function d(){a=+new Date,e.apply(u,f)}i&&!r&&d(),r&&clearTimeout(r),i===n&&c>t?d():!0!==o&&(r=setTimeout(i?function(){r=n}:d,i===n?t-c:t))}return"boolean"!=typeof o&&(i=e,e=o,o=n),u.guid&&(c.guid=e.guid=e.guid||u.guid++),c},u.debounce=function(t,u,e){return e===n?o(t,u,!1):o(t,e,!1!==u)}}(this);

// Search tickers by name/code
// @param jQuery $element   - search input element
// @param function callback - call after selecting an item from the list of results
// @param int limit         - max length of result list
// @return void
//
// callback( params );
// @param jQuery $input     - search input element
// @param jQuery $row       - clicked element
// @param array ticker      - selected ticker data
// @param array data        - API response
function eod_search_input($element, callback, limit = 6){
    // Use debounce and wait for input to stop
    $element.keyup( jQuery.debounce(400, function(e){
        let $input = jQuery(this);
        if (!e.target.value) return;

        // Find suitable tickers by name/code
        jQuery.getJSON("https://eodhistoricaldata.com/api/query-search-extended/?q=" + e.target.value, function (data) {
            // Display result list below search input
            let list = [], $result = $input.siblings('.result');
            jQuery.each(data, function (i, item) {
                let $row = jQuery('\
                    <div class="item">\
                        <span>' + item['code'] + '.' + item['exchange'] + '</span>\
                        <span>' + item['name'] + '</span>\
                    </div>'
                );

                // Add click listener in which the callback will be called.
                $row.data('ticker', item).on('click', () => {
                    callback( {
                        $input: $input,
                        $row: $row,
                        ticker: $row.data('ticker'),
                        data: data
                    } );
                    $row.closest('.result').html('');
                });
                list.push($row);
                if (i > limit) return false;
            });
            $result.html('').append(list);
        });
    }));
}

// Checks if data can be received
// @param string type       - API type: historical, live, news, etc
// @param array props       - required props
// @param function callback - run after
function eod_check_token_capability(type, props = {}, callback){
    jQuery.ajax({
        dataType: "json",
        method: "POST",
        url: eod_ajax_url,
        data: {
            'action': 'eod_check_token_capability',
            'nonce_code': eod_ajax_nonce,
            'type': type,
            'props': props
        }
    })
    .always((data) => {})
    .done((data) => {
        let error = false;
        if(data.error) {
            error = {
                error_code: data.error_code,
                error: data.error
            };
        }else if(Array.isArray(data) && data.length === 0){
            error = {
                error: 'undefined API type or not enough parameters'
            };
        }

        callback(error);
    });
}

// UI
// Toggle button
jQuery(document).on('click', '.eod_toggle', function(e){
    e.preventDefault();
    let $checkbox = jQuery(this).find('[type=checkbox]');
    if( jQuery(e.target).prev().is(':checked') ) return;
    $checkbox.each(function () { this.checked = !this.checked; });
});