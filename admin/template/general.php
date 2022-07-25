<div class="wrap">
    <div class="eod_page with_sidebar">
        <div>
            <?php eod_include( 'admin/template/header.php' ); ?>

            <div class="eod_section">
                <div class="h">Quick start</div>
                <p>To start using it you need to be registered at the <a href="https://eodhistoricaldata.com/?utm_source=p_c&utm_medium=wp_plugin&utm_campaign=new_wp" target="_blank">EODhistoricaldata website</a><br> Right after you’ll register the API key will be sent to your email, or you can copy it into the <a href="https://eodhistoricaldata.com/cp/settings?utm_source=p_c&utm_medium=wp_plugin&utm_campaign=new_wp" target="_blank">settings website section</a>.<br>
                You’ll need to choose the <a href="https://eodhistoricaldata.com/pricing?utm_source=p_c&utm_medium=wp_plugin&utm_campaign=new_wp" target="_blank">proper pricing plan</a> from the EOD Historical Data service for your needs.
                <p>Plugin works in two modes: Inline mode and WP Widget (see below for details).</p>
            </div>

            <div class="eod_section">
                <div class="h">Create a widget by simple SHORTCODE</div>
                <p>Just generate the shortcode by the samples below and put it into your post.</p>
                <div class="samples_grid">
                    <div class="widget row2">
                        <div class="header">Financial News</div>
                        <div class="description">Displays news feed related by TAGs or ticker</div>
                        <div class="sample">
                            <img src="<?= EOD_URL ?>/img/sample_news.png">
                        </div>
                        <div class="footer">
                            <a href="<?= get_admin_url() ?>admin.php?page=eod-examples&e=news" class="s eod_btn">Create Shortcode</a>
                            <a href="<?= get_admin_url() ?>widgets.php" class="w">
                                or use as WP Widget:
                                <span>EOD Financial news</span>
                            </a>
                        </div>
                    </div>
                    <div class="widget col2">
                        <div class="header">Financial Table</div>
                        <div class="description">Allows for organizing the Financial data eg: Earnings, Financial Reports, Balance Sheets, Cash Flows, and Income Statements by the quarterly or yearly view, with the specified time intervals.</div>
                        <div class="sample">
                            <img src="<?= EOD_URL ?>/img/sample_ftable.png">
                        </div>
                        <div class="footer">
                            <a href="<?= get_admin_url() ?>admin.php?page=eod-examples&e=financials" class="s eod_btn">Create Shortcode</a>
                            <a href="<?= get_admin_url() ?>widgets.php" class="w">
                                or use as WP Widget:
                                <span>EOD Financial Table</span>
                            </a>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="header">Ticker String</div>
                        <div class="description">Сan be used to display single ticker prices in various places of your site</div>
                        <div class="sample">
                            <img src="<?= EOD_URL ?>/img/sample_ticker.png">
                        </div>
                        <div class="footer">
                            <a href="<?= get_admin_url() ?>admin.php?page=eod-examples&e=ticker" class="s eod_btn">Create Shortcode</a>
                            <a href="<?= get_admin_url() ?>widgets.php" class="w">
                                or use as WP Widget:
                                <span>EOD Stock Price Ticker</span>
                            </a>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="header">Fundamental Data</div>
                        <div class="description">Such as General Information, Numbers for Valuation, Earnings etc. For Stocks, ETFs, Mutual Funds, Indices</div>
                        <div class="sample">
                            <img src="<?= EOD_URL ?>/img/sample_fundamental.png">
                        </div>
                        <div class="footer">
                            <a href="<?= get_admin_url() ?>admin.php?page=eod-examples&e=fundamental" class="s eod_btn">Create Shortcode</a>
                            <a href="<?= get_admin_url() ?>widgets.php" class="w">
                                or use as WP Widget:
                                <span>EOD Fundamental Data</span>
                            </a>
                        </div>
                    </div>

<!--                    <a href="--><?//= get_admin_url() ?><!--admin.php?page=eod-examples&e=ticker">-->
<!--                        <div class="h">Ticker string</div>-->
<!--                        <div>Сan be used to display single ticker prices in various places of your site</div>-->
<!--                    </a>-->
<!--                    <a href="--><?//= get_admin_url() ?><!--admin.php?page=eod-examples&e=fundamental">-->
<!--                        <div class="h">Fundamental data</div>-->
<!--                        <div>Such as General Information, Numbers for Valuation, Earnings etc. For Stocks, ETFs, Mutual Funds, Indices</div>-->
<!--                    </a>-->
<!--                    <a href="--><?//= get_admin_url() ?><!--admin.php?page=eod-examples&e=news">-->
<!--                        <div class="h">Financial news</div>-->
<!--                        <div>Displays news feed related by TAGs or ticker</div>-->
<!--                    </a>-->
                </div>
            </div>

            <div class="eod_section">
                <div class="h">Or use standard WordPress widget's</div>
                <p>
                    You can configure any of our plugin widgets:<br>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>EOD Financial news</li>
                        <li>EOD Stock Price Ticker</li>
                        <li>EOD Fundamental Data</li>
                    </ul>
                    on the <a href="<?= get_admin_url() ?>widgets.php">'Appearance-> Widgets' settings page</a>.
                </p>
            </div>

            <div class="eod_section">
                <div class="h">Any suggestions or still have any questions?</div>
                <p>We are gladly implementing new demanded features, which are suggested by our subscriber's partner and potential users, feel free to send us an email to <a href="mailto:support@eodhistoricaldata.com">support@eodhistoricaldata.com</a> and we will get back to you next 24 hours!</p>
            </div>
        </div>
        <div class="eod_sidebar">
            <?php include( plugin_dir_path( __FILE__ ) . 'sidebar.php'); ?>
        </div>
    </div>
</div>