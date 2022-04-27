<?php
$form_class = '.eod_shortcode_form.for_ticker';
$display_options = get_option('eod_display_settings');
$ndap = $display_options['ndap'] ? : EOD_DEFAULT_SETTINGS['ndap'];
$ndape = $display_options['ndape'] ? : EOD_DEFAULT_SETTINGS['ndape'];
?>


<form class="<?= str_replace('.', ' ', $form_class) ?>">
    <div class="field">
        <div class="h">Ticker type <span class="require" title="required shortcode element">*</span></div>
        <label>
            <input type="radio" name="eod_ticker_type" value="historical" checked="checked">
            historical - when loading the page, the user receives up-to-date data for the last day
        </label>
        <label>
            <input type="radio" name="eod_ticker_type" value="live">
            live - when loading the page, the user receives up-to-date data for the last 15 minutes
        </label>
        <label>
            <input type="radio" name="eod_ticker_type" value="realtime">
            realtime - user get real-time data, the element updates it on its own
        </label>
    </div>

    <div class="field">
        <label class="h">Ticker code/name <span class="require" title="required shortcode element">*</span></label>
        <div class="eod_search_box for_ticker">
            <input class="eod_search_input" type="text" autocomplete="off" placeholder="Find ticker by code or company name"/>
            <div class="result"></div>
        </div>
    </div>

    <div class="field eod_ticker_name">
        <div class="h">Display name</div>
        <label>
            <input class="id" type="radio" name="eod_ticker_name" value="id" checked="checked">
            <span>code <b></b></span>
        </label>
        <label>
            <input class="name" type="radio" name="eod_ticker_name" value="">
            <span>company name <b></b></span>
        </label>
        <label>
            <input class="empty" type="radio" name="eod_ticker_name" value="">
            <span>empty</span>
        </label>
        <label>
            <input class="custom" type="radio" name="eod_ticker_name" value="">
            <span>custom</span>
            <input type="text" id="custom_ticker_name">
        </label>
    </div>

    <div class="field">
        <div class="h">A number of digits after decimal point</div>
        <p>By default, the <a href="<?= get_admin_url() ?>admin.php?page=eod-settings">global settings</a> are used. If you do not change the values in the fields below, then the shortcode will use the global settings.</p>
        <div>quantity for base value <i>( AAPL.US xxx.<b>XX</b> (+x.xx) )</i></div>
        <label>
            <input type="number" name="eod_ticker_ndap" value="<?= $ndap ?>" data-default="<?= $ndap ?>" min="0">
        </label>
    </div>
    <div class="field">
        <div>quantity for evolution <i>( AAPL.US xxx.xx (+x.<b>XX</b>) )</i></div>
        <label>
            <input type="number" name="eod_ticker_ndape" value="<?= $ndape ?>" data-default="<?= $ndape ?>" min="0">
        </label>
    </div>
    
    <div class="field">
        <div class="h">Your shortcode:</div>
        <div>
            <div class="eod_shortcode_result">-</div>
        </div>
    </div>
</form>



<script>
function eod_create_ticker_shortcode(){
    let $shortcode = jQuery('<?= $form_class ?> .eod_shortcode_result'),
        $search_box = jQuery('<?= $form_class ?> .eod_search_box'),
        $ndap = jQuery('<?= $form_class ?> input[name=eod_ticker_ndap]'),
        $ndape = jQuery('<?= $form_class ?> input[name=eod_ticker_ndape]'),
        ticker = $search_box.find('.selected').data('ticker'),
        type = jQuery('<?= $form_class ?> input[name=eod_ticker_type]:checked').val(),
        title = jQuery('<?= $form_class ?> input[name=eod_ticker_name]:checked').val();

    if(title === 'id') title = false;

    if(!ticker || !type){
        $shortcode.html('-');
        jQuery('.tab.active .eod_error').remove();
        return false;
    }

    let target = ticker['code'] + '.' + ticker['exchange'], 
        last_target = $search_box.data('last_target');
    
    // Validate when changing the target or type of the shortcode
    if(!last_target || last_target !== type+target){
        check_token_on_example_page(type, {
            target: target
        });
    }
    // Save selected target and type
    $search_box.data('last_target', type+target);

    $shortcode.html(
        '[eod_' + type + ' '
            + 'target="' + target + '"'
            + ( (title === false) ? '' : (' title="'+title+'"') )
            + ( ($ndap.val() === $ndap.attr('data-default')) ? '' : (' ndap="'+$ndap.val()+'"') )
            + ( ($ndape.val() === $ndape.attr('data-default')) ? '' : (' ndape="'+$ndape.val()+'"') )
        + ']'
    );
}

// Input custom name
jQuery('#custom_ticker_name').keyup(jQuery.debounce(500, function (e) {
    let $radio = jQuery('<?= $form_class ?> input[name=eod_ticker_name].custom');
    
    $radio.trigger('click');
    $radio.val(e.target.value);
    eod_create_ticker_shortcode();
    
}));

jQuery(document).on('change', '<?= $form_class ?> input[type=radio], <?= $form_class ?> input[type=number]', function(){
    eod_create_ticker_shortcode();
});
jQuery(document).on('click', '<?= $form_class ?> .eod_search_box .remove', function(){
    jQuery(this).parent().remove();
    eod_create_ticker_shortcode();
});

jQuery(function(){
    // Search ticker
    eod_search_input(
        jQuery('<?= $form_class ?> .eod_search_input'),
        function (res) {
            let $box = res.$row.closest('.eod_search_box');
            $box.find('.selected').remove();
            $box.append(
                res.$row.clone().data('ticker', res.ticker).attr('class', 'selected').append('<div class="remove"></div>')
            );
            res.$input.val('');

            // Change Display name
            let $dn_box = jQuery('<?= $form_class ?> .field.eod_ticker_name');
            $dn_box.find('.id + span > b').text( res.ticker['code'] + '.' + res.ticker['exchange'] );
            $dn_box.find('.name').val( res.ticker['name'] );
            $dn_box.find('.name + span > b').text( res.ticker['name'] );

            eod_create_ticker_shortcode();
        }
    );
});
</script>