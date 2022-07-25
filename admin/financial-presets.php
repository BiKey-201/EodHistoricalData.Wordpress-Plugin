<?php
if(!class_exists('EOD_Financials_Admin')) {
    class EOD_Financials_Admin{
        /**
         * Prepare plugin hooks / filters
         */
        public function __construct(){
            add_action( 'add_meta_boxes', array(&$this,'add_meta_boxes' ));
            add_action( 'save_post', array(&$this,'save_meta_fields') );
            add_action( 'new_to_publish', array(&$this,'save_meta_fields') );
        }


        /**
         * Add meta boxes
         */
        function add_meta_boxes() {
            add_meta_box(
                'financials-list',
                'Financials list',
                array(&$this,'display_fd_list_mb'),
                'financials',
                'normal',
                'low'
            );
        }


        /**
         * Display meta box with fundamental data list.
         * Contain two lists: source and selected data.
         */
        function display_fd_list_mb() {
            global $post;
            global $eod_api;

            $financials_list = get_post_meta($post->ID, '_financials_list', true);
            $financial_group = get_post_meta($post->ID, '_financial_group', true);
            if(!$financial_group) $financial_group = 'Financials->Balance_Sheet';

            $financials_lib = $eod_api->get_financials_lib();
            ?>

            <div class="eod_page">
                <div class="field">
                    <div class="h">Group of parameters</div>
                    <select name="financial_group">
                        <option value="Earnings->History" <?php selected($financial_group, 'Earnings->History'); ?>>
                            Earnings - History</option>
                        <option value="Earnings->Trend" <?php selected($financial_group, 'Earnings->Trend'); ?>>
                            Earnings - Trend</option>
                        <option value="Earnings->Annual" <?php selected($financial_group, 'Earnings->Annual'); ?>>
                            Earnings - Annual</option>
                        <option value="Financials->Balance_Sheet" <?php selected($financial_group, 'Financials->Balance_Sheet'); ?>>
                            Financials - Balance Sheet</option>
                        <option value="Financials->Cash_Flow" <?php selected($financial_group, 'Financials->Cash_Flow'); ?>>
                            Financials - Cash Flow</option>
                        <option value="Financials->Income_Statement" <?php selected($financial_group, 'Financials->Income_Statement'); ?>>
                            Financials - Income Statement</option>
                    </select>
                </div>
                <div class="fd_array_grid">
                    <div>
                        <input type="text" class="search_fd_variable" placeholder="Search data">
                        <?php foreach ($financials_lib as $type => $vars) { ?>
                        <ul class="fd_list source_list <?= implode( '_', explode('->', $type) ) ?> <?= $type === $financial_group ? 'active' : '' ?>">
                            <?php eod_display_source_list($vars); ?>
                        </ul>
                        <?php } ?>
                    </div>
                    <div>
                        <ul class="fd_list selected_list">
                            <?php if($financials_list){ ?>
                                <?php eod_display_saved_list($financials_list, $eod_api->get_financials_lib()); ?>
                            <?php } ?>
                        </ul>
                    </div>
                </div>

                <input type="hidden" id="fd_list" name="financials_list" value="<?= htmlspecialchars( $financials_list ) ?>">
            </div>
            <?php
            wp_nonce_field( basename( __FILE__ ), 'fd_nonce' );
        }

        /*
         * Save/update post
         */
        function save_meta_fields( $post_id ) {

            // verify nonce
            if (!isset($_POST['fd_nonce']) || !wp_verify_nonce($_POST['fd_nonce'], basename(__FILE__)))
                return 'nonce not verified';

            // Check autosave
            if ( wp_is_post_autosave( $post_id ) )
                return 'autosave';

            // Check post revision
            if ( wp_is_post_revision( $post_id ) )
                return 'revision';

            // Check permissions
            if ( 'financials' == $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_page', $post_id ) )
                    return 'cannot edit page';
            } elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
                return 'cannot edit post';
            }

            $financials_list = sanitize_text_field( $_POST['financials_list'] );
            update_post_meta( $post_id, '_financials_list', $financials_list );
            $financial_group = sanitize_text_field( $_POST['financial_group'] );
            update_post_meta( $post_id, '_financial_group', $financial_group );
        }
    }
}

