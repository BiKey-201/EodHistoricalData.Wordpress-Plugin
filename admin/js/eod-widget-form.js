/* ======================================
                  TICKER
   ====================================== */
jQuery(document).on('widget-updated widget-added', function(e){
    // Ticker search and selection
    eod_search_input(jQuery('.eod_ticker_widget .eod_search_widget_input'), function (res) {
        let $display_name_radio = res.$input.closest('.eod_widget_form').find('.field.display_name input:checked'),
            target = res.ticker.code + '.' + res.ticker.exchange,
            display_name = ($display_name_radio.length && $display_name_radio.val() === 'name') ?
                res.ticker.name + ' (' + target + ')' : target,
            $box = res.$row.closest('.eod_search_box'),
            $list = $box.find('.selected'),
            $item = jQuery('\
                <li data-target="' + target + '" data-name="' + res.ticker.name + '">\
                    <span class="move"></span>\
                    <div class="header">\
                        <span class="name">' + display_name + '</span>\
                        <div class="toggle"></div>\
                        <div class="remove"></div>\
                    </div>\
                    <div class="settings">\
                        <label>\
                            <span>Custom name:</span>\
                            <input type="text" class="name" placeholder="Default: '+ target +' ">\
                        </label>\
                        <label>\
                            <span>A number of digits after decimal point:</span>\
                            <input type="number" class="ndap" placeholder="Default: '+ eod_display_settings.ndap +'" min="0">\
                        </label>\
                    </div>\
                </li>');

        // Add item
        $list.append( $item );
        compile_ticker_list_val( $list );
    });

    // Sortable selected list
    jQuery(".eod_ticker_widget .eod_search_box.advanced .selected").sortable({
        handle: ".move",
        axis: "y",
        revert: false,
        revertDuration: 0,
        cursor: "grabbing",
        stop: function (e, ul){
            let $list = ul.item.closest('.selected');
            compile_ticker_list_val( $list );
        }
    });
});


jQuery(document).on('change', '.eod_ticker_widget input.name', function(){
    let ticker_title = jQuery(this).val(),
        display = jQuery(this).closest('.eod_widget_form').find('.field.display_name input:checked').val(),
        $li = jQuery(this).closest('li'),
        target =  $li.attr('data-target'),
        name =  $li.attr('data-name');

    let text = target;
    if( ticker_title ) {
        text = ticker_title+' ('+target+')';
    }else if( display === 'name' && name ){
        text = name+' ('+target+')';
    }

    $li.find('.header .name').text(text)
});
jQuery(document).on('change', '.eod_ticker_widget .field.display_name input', function(){
    let $input = jQuery(this),
        $list = $input.closest('.eod_widget_form').find('.eod_search_box .selected');

    $list.find('li').each(function(){
        let $item = jQuery(this),
            custom_name = $item.find('input.name').val(),
            name = $item.attr('data-name'),
            target = $item.attr('data-target');

        // The item already has a custom name
        if(custom_name) return;

        if( $input.val() === 'name' ){
            // For the display name, check it.
            if(name) {
                $item.find('.name').text( name + ' (' +target+ ')' );
                return;
            }

            // If it is not found, get the name from the API and write in the attribute.
            jQuery.ajax({
                dataType: "json",
                method: "POST",
                url: eod_ajax_url,
                data: {
                    'action': 'search_by_string',
                    'nonce_code': eod_ajax_nonce,
                    'string': target,
                }
            }).always((data) => {
                if(data.error) console.log('EOD-error: ' +data.error, target);
            }).done((data) => {
                if(data.length){
                    // Use first item
                    $item.attr('data-name', data[0].Name);
                    $item.find('.name').text( data[0].Name + ' (' +target+ ')' );
                    compile_ticker_list_val( $list );
                }
            });
        }else{
            $item.find('.name').text( target );
        }
    });
});
jQuery(document).on('change', '.eod_ticker_widget .eod_search_box.advanced .selected input', function(){
    compile_ticker_list_val( jQuery(this).closest('.selected') );
});
jQuery(document).on('click', '.eod_ticker_widget .eod_search_box.advanced .header .name, .eod_search_box.advanced .header .toggle', function(){
    let $li = jQuery(this).closest('li');
    $li.toggleClass('opened');
});
jQuery(document).on('click', '.eod_ticker_widget .eod_search_box .remove', function(){
    let $list = jQuery(this).closest('.selected');
    jQuery(this).closest('li').remove();
    compile_ticker_list_val( $list );
});

function compile_ticker_list_val( $list ){
    // Collect targets info
    let list_of_targets = [];
    $list.find('li').each(function () {
        let target = jQuery(this).attr('data-target'),
            full_name = jQuery(this).attr('data-name'),
            title = jQuery(this).find('input.name').val(),
            ndap = jQuery(this).find('input.ndap').val(),
            data = {target: target};

        if(title) data.title = title;
        if(full_name) data.name = full_name;
        if(ndap === 0 || ndap) data.ndap = ndap;

        list_of_targets.push(data);
    });

    // Write in the input field
    let $input = $list.closest('.eod_widget_form').find('input.target_list');
    $input.val( JSON.stringify( list_of_targets ) ).change();
}

/* ======================================
                   NEWS
   ====================================== */
jQuery(document).on('change', '.eod_news_widget .news_type input', function(){
    let $widget = jQuery(this).closest('.eod_news_widget');
    $widget.find('.news_type input').each(function(){
        jQuery('.eod_news_widget .field.by_'+jQuery(this).val()).toggle( jQuery(this).is(':checked') );
    });
    $widget.find('select').val('').change();
    $widget.find('.eod_search_box .remove').click();
});



/* ======================================
               ALL WIDGETS
   ====================================== */
jQuery(document).on('widget-updated widget-added', function(e){
    // Ticker search and selection
    eod_search_input(jQuery('.eod_widget_form .eod_search_box:not(.advanced) .eod_search_input'), function (res) {
        let $box = res.$row.closest('.eod_search_box'),
            $target_input = $box.closest('.eod_widget_form').find('input.target'),
            target = res.ticker.code + '.' + res.ticker.exchange;

        // Display target
        $box.find('.selected').html('\
            <span>'+target+'</span>\
            <span></span>\
            <div class="remove"></div>');

        // Write in hidden input
        $target_input.val(target);
    });
});
jQuery(document).on('click', '.eod_widget_form .eod_search_box:not(.advanced) .remove', function(){
    jQuery(this).closest('.eod_widget_form').find('input.target').val('').change();
    jQuery(this).parent().remove();
});