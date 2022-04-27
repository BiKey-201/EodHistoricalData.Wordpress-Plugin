<?php
$fd_presets = get_posts([
    'post_type' => 'fundamental-data',
    'post_status' => 'publish',
    'numberposts' => -1
]);
$form_class = '.eod_shortcode_form.for_fundamental';
?>

<form class="<?= str_replace('.', ' ', $form_class) ?>">
    <div class="field">
        <label for="esi_fd" class="h">Ticker code/name <span class="require" title="required shortcode element">*</span></label>
        <div class="eod_search_box">
            <input id="esi_fd" class="eod_search_input" type="text" autocomplete="off" placeholder="Find ticker by code or company name"/>
            <div class="result"></div>
        </div>
    </div>

    <div class="field">
        <label for="fd_preset" class="h">Data Preset <span class="require" title="required shortcode element">*</span></label>
        <p>The preset defines the list of data that will be displayed. You can create it on the page <a href="<?= get_admin_url() ?>edit.php?post_type=fundamental-data">Fundamental Data presets</a>.</p>
        <select id="fd_preset">
            <option value="">Select preset</option>
        <?php foreach ($fd_presets as $preset){ ?>
            <option value="<?= $preset->ID ?>"><?= $preset->post_title ?></option>
        <?php } ?>
        </select>
    </div>

    <div class="field">
        <div class="h">Your shortcode:</div>
        <div>
            <div class="eod_shortcode_result" id="eod_fundamental_shortcode">-</div>
        </div>
    </div>
</form>



<script>
    function eod_create_fundamental_shortcode(){
        let $shortcode = jQuery('<?= $form_class ?> .eod_shortcode_result'),
            $search_box = jQuery('<?= $form_class ?> .eod_search_box'),
            ticker = $search_box.find('.selected').data('ticker'),
            preset_id = jQuery('<?= $form_class ?> #fd_preset option:checked').val(),
            label = jQuery('<?= $form_class ?> #fd_preset option:checked').text();

        if(!ticker || !preset_id){
            $shortcode.html('-');
            jQuery('.tab.active .eod_error').remove();
            return false;
        }

        let target = ticker['code'] + '.' + ticker['exchange'],
            last_target = $search_box.data('last_target');

        // Validate when changing the target or type of the shortcode
        if(!last_target || last_target !== target){
            check_token_on_example_page('fundamental', {
                target: target
            });
        }
        // Save selected target
        $search_box.data('last_target', target);

        $shortcode.html(
            '[eod_fundamental '
                + 'target="' + target + '" '
                + 'id="' + preset_id + '" '
                + 'preset="' + label + '"'
            + ']'
        );
    }


    jQuery(document).on('change', '<?= $form_class ?> select', function(){
        eod_create_fundamental_shortcode();
    });

    jQuery(document).on('click', '<?= $form_class ?> .eod_search_box .remove', function(){
        jQuery(this).parent().remove();
        eod_create_fundamental_shortcode();
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

                eod_create_fundamental_shortcode();
            }
        );
    });
</script>