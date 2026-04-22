<?php

/**
 * Classifieds Core Main Class
 **/
if (!class_exists('Classifieds_Core_Main')):
    class Classifieds_Core_Main extends Classifieds_Core
    {

        public $cf_ads_per_page = 20;
        public $classified_submit_validator;
        public $my_classifieds_request_actions;

        /**
         * Constructor.
         *
         * @return void
         **/

        function __construct()
        {

            parent::__construct(); //Get the inheritance right

            require_once $this->plugin_dir . 'core/class-classified-submit-validator.php';
            $this->classified_submit_validator = new CF_Classified_Submit_Validator( $this );
            require_once $this->plugin_dir . 'core/class-my-classifieds-request-actions.php';
            $this->my_classifieds_request_actions = new CF_My_Classifieds_Request_Actions( $this );

            //add_action( 'init', array(&$this, 'init'));

            /* Handle requests for plugin pages */

            add_action('template_redirect', array(&$this, 'process_page_requests'));

            add_action('template_redirect', array(&$this, 'handle_page_requests'));

            /* Enqueue scripts */
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'), 99);

            add_filter('author_link', array(&$this, 'on_author_link'));

        }

        function init()
        {
            global $wp, $wp_rewrite;

            parent::init();

            //Listing author rewrite rule
            $wp->add_query_var('cf_author_name');
            $wp->add_query_var('cf_author_page');

            $result = add_query_arg(array(
                'cf_author_name' => '$matches[1]',
                'cf_author_page' => '$matches[3]',
            ), 'index.php');

            add_rewrite_rule('cf-author/(.+?)(/page/(.+?))?/?$', $result, 'top');
            $rules = get_option('rewrite_rules');
            if (!isset($rules['cf-author/(.+?)(/page/(.+?))?/?$'])) $wp_rewrite->flush_rules();

        }


        /**
         * Process $_REQUEST for main pages.
         *
         * @uses set_query_var() For passing variables to pages
         * @return void|die() if "_wpnonce" is not verified
         **/
        function process_page_requests()
        {

            //Manage Classifieds
            if (is_page($this->my_classifieds_page_id)) {
                $this->my_classifieds_request_actions->handle_confirm_action( $_POST );

                //Updating Classifieds
            } elseif (is_page($this->add_classified_page_id) || is_page($this->edit_classified_page_id)) {

                if (isset($_POST['cf_end_listing']) || isset($_POST['cf_delete_listing'])) {
                    if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'verify' ) ) {
                        die(__('Security check failed!', $this->text_domain));
                    }

                    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
                    if ( $post_id > 0 ) {
                        if ( isset( $_POST['cf_end_listing'] ) && current_user_can( 'edit_post', $post_id ) ) {
                            $expiration_timestamp = (int) get_post_meta( $post_id, '_expiration_date', true );
                            $has_remaining_runtime = ( $expiration_timestamp > current_time( 'timestamp' ) );
                            if ( ! $has_remaining_runtime ) {
                                set_query_var('cf_post_id', $post_id);
                                set_query_var('cf_action', 'edit');
                                set_query_var('cf_error', __( 'Anzeige beenden ist nur mit Restlaufzeit moeglich.', $this->text_domain ));
                                return;
                            }
                            $this->process_status( $post_id, 'private' );
                            wp_safe_redirect( get_permalink( $this->my_classifieds_page_id ) );
                            exit;
                        }

                        if ( isset( $_POST['cf_delete_listing'] ) && current_user_can( 'delete_post', $post_id ) ) {
                            wp_delete_post( $post_id );
                            wp_safe_redirect( get_permalink( $this->my_classifieds_page_id ) );
                            exit;
                        }
                    }
                }

                if (isset($_POST['update_classified'])) {
                    if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'verify' ) ) {
                        die(__('Security check failed!', $this->text_domain));
                    }
                    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
                    $required_validation = $this->classified_submit_validator->validate_frontend_required_fields( $_POST, $post_id );
                    if ( empty( $required_validation['valid'] ) ) {
                        set_query_var('cf_post_id', absint( $_POST['post_id'] ?? 0 ));
                        set_query_var('cf_action', 'edit');
                        set_query_var('cf_inline_errors', $required_validation['errors']);
                        set_query_var('cf_error', __( 'Bitte pruefe die markierten Felder.', $this->text_domain ));
                        return;
                    }
                    $expiration_timestamp = $post_id ? (int) get_post_meta( $post_id, '_expiration_date', true ) : 0;
                    $is_started_listing = ( $expiration_timestamp > 0 );
                    $is_expired_listing = ( $is_started_listing && $expiration_timestamp <= current_time( 'timestamp' ) );
                    $payments_options = (array) $this->get_options( 'payments' );
                    $expired_restart_mode = isset( $payments_options['expired_restart_mode'] ) ? sanitize_key( $payments_options['expired_restart_mode'] ) : 'credits';
                    if ( ! in_array( $expired_restart_mode, array( 'none', 'free', 'credits' ), true ) ) {
                        $expired_restart_mode = 'credits';
                    }

                    $requires_credits = ! $is_started_listing || ( $is_expired_listing && 'credits' === $expired_restart_mode );
                    $validation = $this->classified_submit_validator->validate_update_submission( $_POST );
                    $credits_required = $validation['credits_required'];
                    $can_restart_expired = ! $is_expired_listing || 'none' !== $expired_restart_mode;
                    $has_required_credits = ! $requires_credits || $validation['has_credits'];
                    // If user have more credits of the required credits proceed with renewing the ad
                    if ( $can_restart_expired && $has_required_credits ) {
                        // Update ad
                        $this->update_ad($_POST);
                        // Save the expiration date
                        if ( $post_id ) {
                            $this->save_expiration_date($post_id, ! $is_started_listing || $is_expired_listing);
                        }
                        // Set the proper step which will be loaded by "page-my-classifieds.php"
                        set_query_var('cf_action', 'my-classifieds');

                        if ( $requires_credits && ! $this->is_full_access() ) {
                            // Update new credits amount
                            $this->transactions->credits -= $credits_required;
                        } elseif ( $requires_credits ) {
                            //Check one_time
                            if ($this->transactions->billing_type == 'one_time') $this->transactions->status = 'used';
                        }

                        if ( $post_id ) {
                            wp_safe_redirect(get_permalink($post_id));
                        } else {
                            wp_safe_redirect(get_permalink($this->my_classifieds_page_id));
                        }
                        exit;

                    } else {

                        //save ad if have no credits
                        $post_data = $_POST;
                        $post_data['classified_data']['post_status'] = 'draft';
                        /* Create ad */
                        $post_id = $this->update_ad($post_data);
                        set_query_var('cf_post_id', absint( $_POST['post_id'] ?? 0 ));
                        /* Set the proper step which will be loaded by "page-my-classifieds.php" */
                        set_query_var('cf_action', 'edit');
                        $error = $is_expired_listing && 'none' === $expired_restart_mode
                            ? __('Neustart nach Ablauf ist deaktiviert. Deine Anzeige wurde als Entwurf gespeichert.', $this->text_domain)
                            : __('Du hast nicht genug Credits fuer die ausgewaehlte Laufzeit. Waehle, wenn moeglich, eine kuerzere Laufzeit oder kauf mehr Credits.<br />Deine Anzeige wurde als Entwurf gespeichert.', $this->text_domain);
                        set_query_var('cf_error', $error);
                    }
                }
            }
        }


        /**
         * Handle $_REQUEST for main pages.
         *
         * @uses set_query_var() For passing variables to pages
         * @return void|die() if "_wpnonce" is not verified
         **/
        function handle_page_requests()
        {
            global $wp_query;

            /* Handles request for classifieds page */
            $templates = array();
            $taxonomy = (empty($wp_query->query_vars['taxonomy'])) ? '' : $wp_query->query_vars['taxonomy'];

            //Check if a custom template is selected, if not or not a page, default to the one selected for the directory_listing virtual page.
            $id = get_queried_object_id();
            if (empty($id)) $id = $this->classifieds_page_id;
            $slug = get_page_template_slug($id);
            if (empty($slug)) $page_template = get_page_template();
            else $page_template = locate_template(array($slug, 'page.php', 'index.php'));

            if (is_feed()) {
                return;
            } elseif (is_page($this->classifieds_page_id)) {
                $templates = array('page-classifieds.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    $wp_query->post_count = 1;
                    add_filter('the_title', array(&$this, 'page_title_output'), 10, 2);
                    add_filter('the_content', array(&$this, 'classifieds_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif ('' != get_query_var('cf_author_name') || isset($_REQUEST['cf_author']) && '' != $_REQUEST['cf_author']) {
                $templates = array('page-author');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    $wp_query->post_count = 1;
                    add_filter('the_title', array(&$this, 'page_title_output'), 10, 2);
                    add_filter('the_content', array(&$this, 'classifieds_content'));
                }
// 			add_filter( 'template_include', array( &$this, 'custom_classifieds_template' ) );
                $this->is_classifieds_page = true;

            } elseif (is_post_type_archive('classifieds')) {
                global $wp_query;
                $p = get_post($this->classifieds_page_id);
                $wp_query->posts = array($p);
                $wp_query->post_count = 1;
                /* Set the proper step which will be loaded by "page-my-classifieds.php" */
                $templates = array('archive-classifieds.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    $wp_query->post_count = 1;
                    add_filter('the_title', array(&$this, 'page_title_output'), 10, 2);
                    add_filter('the_content', array(&$this, 'classifieds_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif (is_archive() && in_array($taxonomy, array('kleinenanzeigen-cat', 'kleinanzeigen-region'))) {
                /* Set the proper step which will be loaded by "page-my-classifieds.php" */
                $templates = array("taxonomy-{$taxonomy}.php", 'taxonomy.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    // Ensure the loop runs once so the_content filter fires.
                    // If the taxonomy query returned no posts, seed with the classifieds page
                    // post to avoid "Undefined array key 0" from WP_Query internals.
                    if ( empty( $wp_query->posts ) ) {
                        $cf_page = get_post( $this->classifieds_page_id );
                        if ( $cf_page ) {
                            $wp_query->posts = array( $cf_page );
                        }
                    }
                    $wp_query->post_count = 1;
                    add_filter('the_title', array(&$this, 'page_title_output'), 10, 2);
                    add_filter('the_content', array(&$this, 'classifieds_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif (is_single() && 'classifieds' == get_query_var('post_type')) {
                $templates = array('single-classifieds.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_content', array(&$this, 'single_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif (is_page($this->my_credits_page_id)) {
                $templates = array('page-my-credits.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_content', array(&$this, 'my_credits_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif (is_page($this->checkout_page_id)) {
                $templates = array('page-checkout.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_content', array(&$this, 'checkout_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } elseif (is_page($this->signin_page_id)) {
                $templates = array('page-signin.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_title', array(&$this, 'delete_post_title'), 11); //after wpautop
                    add_filter('the_content', array(&$this, 'signin_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } //My Classifieds page
            elseif (is_page($this->my_classifieds_page_id)) {
                $templates = array('page-my-classifieds.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_content', array(&$this, 'my_classifieds_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } //Classifieds update pages
            elseif (is_page($this->add_classified_page_id) || is_page($this->edit_classified_page_id)) {
                $templates = array('page-update-classified.php');
                if (!$this->classifieds_template = locate_template($templates)) {
                    $this->classifieds_template = $page_template;
                    add_filter('the_content', array(&$this, 'update_classified_content'));
                }
                add_filter('template_include', array(&$this, 'custom_classifieds_template'));
                $this->is_classifieds_page = true;
            } /* If user wants to go to My Classifieds main page  */
            elseif (isset($_POST['go_my_classifieds'])) {
                wp_safe_redirect(get_permalink($this->my_classifieds_page_id));
                exit;
            } /* If user wants to go to checkout page  */
            elseif (isset($_POST['purchase'])) {
                wp_safe_redirect(get_permalink($this->checkout_page_id));
                exit;
            } else {
                /* Set the proper step which will be loaded by "page-my-classifieds.php" */
                set_query_var('cf_action', 'my-classifieds');
            }

            //load  specific items
            if ($this->is_classifieds_page) {
                add_filter('edit_post_link', array(&$this, 'delete_edit_post_link'));
            }
        }

        /**
         * Enqueue scripts.
         *
         * @return void
         **/
        function enqueue_scripts()
        {
            if (file_exists(get_template_directory() . '/style-classifieds.css')) {
                wp_enqueue_style('style-classifieds', get_template_directory_uri() . '/style-classifieds.css', array(), filemtime(get_template_directory() . '/style-classifieds.css'));
            } elseif (file_exists($this->plugin_dir . 'ui-front/general/style-classifieds.css')) {
                wp_enqueue_style('style-classifieds', $this->plugin_url . 'ui-front/general/style-classifieds.css', array(), filemtime($this->plugin_dir . 'ui-front/general/style-classifieds.css'));
            }

            // Akzentfarbe der Single-View aus Admin-Settings als CSS-Custom-Property injizieren.
            $fe_opts      = $this->get_options( 'frontend' );
            $accent_raw   = isset( $fe_opts['single_accent_color'] ) ? $fe_opts['single_accent_color'] : '';
            $accent_color = preg_match( '/^#[0-9a-f]{6}$/i', $accent_raw ) ? $accent_raw : '#0f6cbd';
            wp_add_inline_style( 'style-classifieds', '.cf-single-page{--cf-accent:' . esc_attr( $accent_color ) . ';}' );

            $this->enqueue_frontend_runtime_assets();
        }

        /**
         * Enqueue interactive frontend assets only on relevant Classifieds screens.
         *
         * @return void
         */
        function enqueue_frontend_runtime_assets() {
            $script_path = $this->plugin_dir . 'ui-front/js/ui-front.js';
            $script_ver = file_exists( $script_path ) ? filemtime( $script_path ) : $this->plugin_version;

            $needs_frontend_script = is_page( $this->my_classifieds_page_id )
                || is_page( $this->add_classified_page_id )
                || is_page( $this->edit_classified_page_id )
                || is_singular( 'classifieds' )
                || is_post_type_archive( 'classifieds' )
                || is_tax( array( 'kleinenanzeigen-cat', 'kleinanzeigen-region' ) );

            if ( $needs_frontend_script ) {
                wp_enqueue_script( 'cf-frontend', $this->plugin_url . 'ui-front/js/ui-front.js', array( 'jquery' ), $script_ver, true );
                $this->localize_frontend_runtime_script();
            }

            if ( is_page( $this->add_classified_page_id ) || is_page( $this->edit_classified_page_id ) ) {
                $tagsinput_path = $this->plugin_dir . 'ui-front/js/cf-tagsinput.js';
                $tagsinput_ver = file_exists( $tagsinput_path ) ? filemtime( $tagsinput_path ) : $this->plugin_version;
                wp_enqueue_script( 'cf-tagsinput', $this->plugin_url . 'ui-front/js/cf-tagsinput.js', array( 'jquery' ), $tagsinput_ver, true );
            }
        }

        /**
         * Localize runtime config for ui-front.js once per request.
         *
         * @return void
         */
        function localize_frontend_runtime_script() {
            static $localized = false;
            if ( $localized ) {
                return;
            }

            $frontend_options = (array) $this->get_options( 'frontend' );
            $auto_restore_default = ! isset( $frontend_options['archive_auto_restore'] ) || 1 === (int) $frontend_options['archive_auto_restore'];

            $config = array(
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'cf_frontend_actions' ),
                'messageNonce'       => wp_create_nonce( 'cf_send_message' ),
                'dashboardNonce'     => wp_create_nonce( 'cf_frontend_actions' ),
                'textDomain'         => $this->text_domain,
                'autoRestoreDefault' => $auto_restore_default,
                'i18n'               => array(
                    'loading'            => __( 'Wird geladen ...', $this->text_domain ),
                    'loadError'          => __( 'Die Schnellansicht konnte gerade nicht geladen werden.', $this->text_domain ),
                    'copyDefault'        => __( 'Link teilen', $this->text_domain ),
                    'copySuccess'        => __( 'Link kopiert', $this->text_domain ),
                    'saveFilterPrompt'   => __( 'Wie willst du diesen Filter nennen?', $this->text_domain ),
                    'saveFilterEmpty'    => __( 'Gib erst einen Namen fuer den Filter ein.', $this->text_domain ),
                    'saveFilterDone'     => __( 'Filter wurde gemerkt.', $this->text_domain ),
                    'loadFilterEmpty'    => __( 'Waehle erst einen gespeicherten Filter aus.', $this->text_domain ),
                    'deleteFilterAsk'    => __( 'Willst du diesen gespeicherten Filter wirklich loeschen?', $this->text_domain ),
                    'deleteFilterDone'   => __( 'Filter wurde geloescht.', $this->text_domain ),
                    'savedFilterDefault' => __( 'Gespeicherten Filter laden', $this->text_domain ),
                ),
                'strings'            => array(
                    'sending'    => __( 'Wird gesendet...', $this->text_domain ),
                    'sent'       => __( 'Nachricht gesendet!', $this->text_domain ),
                    'error'      => __( 'Ups, da ist was schiefgelaufen.', $this->text_domain ),
                    'noMessages' => __( 'Noch keine Nachrichten.', $this->text_domain ),
                ),
            );

            wp_localize_script( 'cf-frontend', 'cfFrontend', $config );
            $localized = true;
        }

        function on_author_link($link = '')
        {
            global $post;

            if ($post->post_type == 'classifieds') {
                $link = str_replace('/author/', '/cf-author/', $link);
            }
            return $link;
        }

    }

    /* Initiate Class */
    global $Classifieds_Core;
    $Classifieds_Core = new Classifieds_Core_Main;

endif;
?>