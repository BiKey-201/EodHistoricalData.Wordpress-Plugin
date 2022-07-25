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
        <div class="eod_search_box advanced">
            <input class="eod_search_input" type="text" autocomplete="off" placeholder="Find ticker by code or company name"/>
            <div class="result"></div>
            <ul class="selected"></ul>
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
        <div class="h">Pagination</div>
        <div>The number of news items per page. Default 0 disables pagination.</div>
        <label>
            <input type="number" name="eod_news_pagination" value="0" min="0">
        </label>
    </div>
    
    <div class="field">
        <div class="h">Time interval</div>
        <input type="date" name="eod_news_from" value="" max="<?= Date('Y-m-d') ?>">
        <span>:</span>
        <input type="date" name="eod_news_to" value="" max="<?= Date('Y-m-d') ?>">
    </div>

    <div class="field">
        <div class="h">Class name</div>
        <div>Adds an html class for css styling or other purposes.</div>
        <label>
            <input type="text" name="eod_news_classname" placeholder="my_news_feed">
        </label>
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
        $targets = $search_box.find('.selected li'),
        tag = jQuery('<?= $form_class ?> select[name=eod_news_tag]').val(),
        type = jQuery('<?= $form_class ?> input[name=eod_news_type]:checked').val(),
        pagination = Math.abs( jQuery('<?= $form_class ?> input[name=eod_news_pagination]').val() ),
        limit = Math.abs( jQuery('<?= $form_class ?> input[name=eod_news_limit]').val() ),
        from = jQuery('<?= $form_class ?> input[name=eod_news_from]').val(),
        to = jQuery('<?= $form_class ?> input[name=eod_news_to]').val(),
        classname = jQuery('<?= $form_class ?> input[name=eod_news_classname]').val();

    // Default pagination and limit
    if( pagination === '') pagination = 0;
    if( limit === '') limit = 50;

    // Check news type and ignore unwanted target
    if(type === 'topic') $targets = false;
    else tag = false;
    $search_box.closest('.field').toggle( type === 'ticker' );
    jQuery('<?= $form_class ?> select[name=eod_news_tag]').closest('.field').toggle( type === 'topic' );

    if(!$targets.length && !tag){
        $shortcode.html('-');
        jQuery('.tab.active .eod_error').remove();
        return false;
    }

    let target = '',
        targets_list = [],
        last_target = $search_box.data('last_target');
    if($targets.length){
        $targets.each(function(){
            targets_list.push(jQuery(this).attr('data-target'));
        });
        target = targets_list.join(', ');
    }

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
            + ( target ? (' target="'+ target +'"') : '' )
            + ( tag ? (' tag="'+tag+'"') : '' )
            + ( (pagination === 0) ? '' : (' pagination="'+pagination+'"') )
            + ( (limit === 50) ? '' : (' limit="'+limit+'"') )
            + ( from ? (' from="'+from+'"') : '' )
            + ( to ? (' to="'+to+'"') : '' )
            + ( classname ? (' classname="'+ classname +'"') : '' )
        + ']'
    );
}

jQuery(document).on('change', '<?= $form_class ?> input:not(.eod_search_input), <?= $form_class ?> select', function(){
    eod_create_news_shortcode();
});
jQuery(document).on('click', '<?= $form_class ?> .eod_search_box .remove', function(){
    jQuery(this).closest('li').remove();
    eod_create_news_shortcode();
});

jQuery(function(){
    // Search ticker
    eod_search_input(
        jQuery('<?= $form_class ?> .eod_search_input'),
        function (res) {
            let target = res.ticker.code + '.' + res.ticker.exchange,
                $box = res.$input.closest('.eod_search_box').find('.selected'),
                $item = jQuery('\
                    <li data-target="' + target + '">\
                        <span class="move"></span>\
                        <div class="header">\
                            <span class="name">' + target + '</span>\
                            <div class="remove"></div>\
                        </div>\
                    </li>');
            // Add item
            $box.append( $item );

            res.$input.val('');

            eod_create_news_shortcode();
        }
    );
});
</script>