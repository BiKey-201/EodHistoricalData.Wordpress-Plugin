<?php
if(!class_exists('EOD_Fundamental_Data_Admin')) {
    class EOD_Fundamental_Data_Admin{
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
                'fd-list',
                'Fundamental data list',
                array(&$this,'display_fd_list_mb'),
                'fundamental-data',
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

            $fd_list = get_post_meta($post->ID, '_fd_list', true);
            $vars = $eod_api->get_fd_lib();
            ?>

            <div class="fd_array_grid">
                <div>
                    <input type="text" class="search_fd_variable" placeholder="Search data">
                    <ul class="fd_list source_list">
                        <?php eod_display_source_list($vars); ?>
                    </ul>
                </div>
                <div>
                    <ul class="fd_list selected_list">
                        <?php if($fd_list){ ?>
                            <?php eod_display_saved_list($fd_list, $eod_api->get_fd_lib()); ?>
                        <?php } ?>
                    </ul>
                </div>
            </div>

            <input type="hidden" id="fd_list" name="fd_list" value="<?= htmlspecialchars( $fd_list ) ?>">
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
            if ( 'fundamental-data' == $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_page', $post_id ) )
                    return 'cannot edit page';
            } elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
                return 'cannot edit post';
            }

            $fd_list = sanitize_text_field( $_POST['fd_list'] );
            update_post_meta( $post_id, '_fd_list', $fd_list );
        }
    }
}
