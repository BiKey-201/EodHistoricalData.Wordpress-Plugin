<?php $options = get_eod_display_options(); ?>
<div class="wrap">
    <div class="eod_page with_sidebar">
        <div>
            <?php eod_include( 'admin/template/header.php' ); ?>

            <div class="eod_section">
                <div class="h">Settings</div>
                <form method="post" action="options.php" name="form">
                    <div class="field">
                        <div class="h">A number of digits after decimal point</div>
                        <div>quantity for base value <i>( AAPL.US xxx.<b>XX</b> (+x.xx) )</i></div>
                        <label>
                            <input type="number" name="eod_display_settings[ndap]" value="<?= $options['ndap'] ? : EOD_DEFAULT_SETTINGS['ndap'] ?>" min="0">
                        </label>
                    </div>
                    <div class="field">
                        <div>quantity for evolution <i>( AAPL.US xxx.xx (+x.<b>XX</b>) )</i></div>
                        <label>
                            <input type="number" name="eod_display_settings[ndape]" value="<?= $options['ndape'] ? : EOD_DEFAULT_SETTINGS['ndape'] ?>" min="0">
                        </label>
                    </div>

                    <div class="field">
                        <div class="h">Use custom scrollbar for desktop devices</div>
                        <p>Some widgets, such as the financial table, require horizontal scrolling. If this option is enabled, the stylized version of the scrollbar will be used instead of the browser's. This may result in a slight decrease in performance and page load time.</p>
                        <button class="eod_toggle timeline">
                            <input type="checkbox" value="off" <?php checked( 'off', $options['scrollbar'] ); ?>
                                   name="eod_display_settings[scrollbar]">
                            <span>No</span>
                            <input type="checkbox" value="on" <?php checked( 'on', $options['scrollbar'] ); ?>
                                   name="eod_display_settings[scrollbar]">
                            <span>Yes</span>
                        </button>
                    </div>
                    <?php settings_fields('eod_display_settings'); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <div class="eod_sidebar">
            <?php include( plugin_dir_path( __FILE__ ) . 'sidebar.php'); ?>
        </div>
    </div>
</div>