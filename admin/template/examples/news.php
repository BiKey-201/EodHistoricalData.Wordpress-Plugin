<?php global $eod_api; ?>
<?php $tags = $eod_api->get_news_topics(); ?>
<?php $form_class = '.eod_shortcode_form.for_news'; ?>

<form class="<?= str_replace('.', ' ', $form_class) ?>">
    <p>To display news, you must specify at one of the fields: Ticker code/name or Topic.</p>
    <div class="field flex">
        <label>
            <input type="radio" name="eod_news_type" value="ticker" checked="checked">
            Ticker
        </label>
        <label>
            <input type="radio" name="eod_news_type" value="topic">
            Topic
        </label>
    </div>

    <div class="field">
        <label class="h">Ticker code/name <span class="require" title="required shortcode element">*</span></label>
        <div class="eod_search_box">
            <input class="eod_search_input" type="text" autocomplete="off" placeholder="Find ticker by code or company name"/>
            <div class="result"></div>
        </div>
    </div>
    
    <div class="field" style="display: none;">
        <div class="h">Topics <span class="require" title="required shortcode element">*</span></div>
        <div>We have more than 50 tags to get news for a given topic, this list is expanding, below you can find all recommended tags in alphabet order:</div>
        <select name="eod_news_tag">
            <option value="" disabled selected hidden>select topic</option>
            <?php foreach($tags as $tag){ ?>
                <option value="<?= $tag ?>"><?= $tag ?></option>
            <?php } ?>
        </select>
    </div>
    
    <div class="field">
        <div class="h">Limit</div>
        <div>The number of results should be returned with the query. Default value: 50, maximum value: 1000.</div>
        <label>
            <input type="number" name="eod_news_limit" value="50" min="0" max="1000">
        </label>
    </div>
    
    <div class="field">
        <div class="h">Offset</div>
        <div>The offset of the data. Default value: 0, minimum value: 0. For example, to get 100 symbols starting from 200 you should use limit=100 and offset=200.</div>
        <label>
            <input type="number" name="eod_news_offset" value="0" min="0">
        </label>
    </div>
    
    <div class="field">
        <div class="h">Time interval</div>
        <input type="date" name="eod_news_from" value="" max="<?= Date('Y-m-d') ?>">
        <span>:</span>
        <input type="date" name="eod_news_to" value="" max="<?= Date('Y-m-d') ?>">
    </div>
    
    <div class="field">
        <div class="h">Your shortcode:</div>
        <div>
            <div class="eod_shortcode_result" id="eod_news_shortcode">-</div>
        </div>
    </div>
    
</form>



<script>
function eod_create_news_shortcode(){
    let $shortcode = jQuery('<?= $form_class ?> .eod_shortcode_result'),
        $search_box = jQuery('<?= $form_class ?> .eod_search_box'),
        ticker = $search_box.find('.selected').data('ticker'),
        tag = jQuery('<?= $form_class ?> select[name=eod_news_tag]').val(),
        type = jQuery('<?= $form_class ?> input[name=eod_news_type]:checked').val(),
        offset = jQuery('<?= $form_class ?> input[name=eod_news_offset]').val(),
        limit = jQuery('<?= $form_class ?> input[name=eod_news_limit]').val(),
        from = jQuery('<?= $form_class ?> input[name=eod_news_from]').val(),
        to = jQuery('<?= $form_class ?> input[name=eod_news_to]').val();

    // Default offset and limit
    if( offset === '') offset = 0;
    if( limit === '') offset = 50;

    // Check news type and ignore unwanted target
    if(type === 'topic') ticker = false;
    else tag = false;
    $search_box.closest('.field').toggle( type === 'ticker' );
    jQuery('<?= $form_class ?> select[name=eod_news_tag]').closest('.field').toggle( type === 'topic' );

    if(!ticker && !tag){
        $shortcode.html('-');
        jQuery('.tab.active .eod_error').remove();
        return false;
    }
        
    let target = ticker ? ticker['code'] + '.' + ticker['exchange'] : '',
        last_target = $search_box.data('last_target');
        
    // Validate when changing the target or topic of the shortcode
    if(!last_target || last_target !== target+tag){
        check_token_on_example_page('news', {
            target: target,
            tag: tag
        });
    }
    // Save selected target and type
    $search_box.data('last_target', target+tag);

    $shortcode.html(
        '[eod_news '
            + ( ticker ? (' target="'+ target +'"') : '' )
            + ( tag ? (' tag="'+tag+'"') : '' )
            + ( (offset === 0) ? '' : (' offset="'+offset+'"') )
            + ( (limit === 50) ? '' : (' limit="'+limit+'"') )
            + ( from ? (' from="'+from+'"') : '' )
            + ( to ? (' to="'+to+'"') : '' )
        + ']'
    );
}

jQuery(document).on('change', '<?= $form_class ?> input:not(.eod_search_input), <?= $form_class ?> select', function(){
    eod_create_news_shortcode();
});
jQuery(document).on('click', '<?= $form_class ?> .eod_search_box .remove', function(){
    jQuery(this).parent().remove();
    eod_create_news_shortcode();
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

            eod_create_news_shortcode();
        }
    );
});
</script>