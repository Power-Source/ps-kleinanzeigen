<?php
/**
 * Classifieds Core Class
 **/

global $Classifieds_Core;

if ( ! class_exists( 'Classifieds_Core' ) ):
	class Classifieds_Core {
		// Definiere Klassenvariablen für veraltete dynamische Eigenschaften
		public $capability_map;
		public $add_classified_page_id;
		public $add_classified_page_page_slug;
		public $edit_classified_page_id;
		public $edit_classified_page_slug;
		public $my_credits_page_slug;
		public $signin_page_id;
		public $signin_page_slug;
		public $classified_template;
		public $my_classifieds_ajax;
		public $favorites_manager;
		public $message_read_service;
		public $quick_view_service;
		public $reserved_status_service;
		public $contact_form_service;
		public $template_content_service;
		public $shortcode_service;

		/** @public plugin version */
		public $plugin_version = CF_VERSION;
		/** @public plugin database version */
		public $plugin_db_version = CF_DB_VERSION;
		/** @public string $plugin_url Plugin URL */
		public $plugin_url = CF_PLUGIN_URL;
		/** @public string $plugin_dir Path to plugin directory */
		public $plugin_dir = CF_PLUGIN_DIR;
		/** @public string $text_domain The text domain for strings localization */
		public $text_domain = CF_TEXT_DOMAIN;
		/** @public string $options_name The name of the plugin options entry in DB */
		public $options_name = CF_OPTIONS_NAME;

		/** @public string $plugin_prefix Plugin prefix */
		public $plugin_prefix = 'cf_';
		/** @public string $post_type Plugin post type */
		public $post_type = 'classifieds';
		/** @public array $taxonomies Post taxonomies */
		public $taxonomy_objects;
		/** @public array $taxonomies Post taxonomies */
		public $taxonomy_names;
		/** @public array $custom_fields The custom fields associated with this post type */
		public $custom_fields = array();
		/** @public string $custom_fields_prefix The custom fields DB prefix */
		public $custom_fields_prefix = '_cf_';
		/** @public string User role */
		public $user_role = 'subscriber';
		/** @public boolean True if submitted form is valid. */
		public $form_valid = true;
		/** @public boolean Login error flag */
		public $login_error;
		/** @public boolean The current user */
		public $current_user;
		/** @public string Current user credits */
		public $user_credits = 0;
		/** @public boolean flag whether to flush all plugin data on plugin deactivation */
		public $flush_plugin_data = false;

		/** @public string/int Current maximum range of page links to show in pagination pagination (used in query) */
		public $pagination_range = 4;
		/** @public string/bool Whether to display pagination at the top of the page */
		public $pagination_top;
		/** @public string/bool Whether to display pagination at the bottom of the page */
		public $pagination_bottom;

		/** @public int classifieds_page_id the Classifieds default page ID number. Track by ID so the page permalink and slug may be internationalized */
		public $classifieds_page_id = 0;
		/** @public string classifieds_page_slug the Classifieds page slug. Track by ID so the page permalink and slug may be internationalized */
		public $classifieds_page_slug = '';
		/** @public string classifieds_page_name the Classifieds default page name for templates. Track by ID so the page permalink and slug may be internationalized */
		public $classifieds_page_name = 'classifieds';

		/** @public int the My Classifieds default page ID number. Track by ID so the page permalink and slug may be internationalized */
		public $my_classifieds_page_id = 0;
		/** @public string the My Classifieds page slug. Track by ID so the page permalink and slug may be internationalized */
		public $my_classifieds_page_slug = '';
		/** @public string classifieds_page_name the Classifieds default page name for templates. Track by ID so the page permalink and slug may be internationalized */
		public $my_classifieds_page_name = 'my-classifieds';

		/** @public int the Checkout default page ID number. Track by ID so the page permalink and slug may be internationalized */
		public $checkout_page_id = 0;
		/** @public string the My Classifieds page slug. Track by ID so the page permalink and slug may be internationalized */
		public $checkout_page_slug = '';
		/** @public string classifieds_page_name the Classifieds default page name for templates. Track by ID so the page permalink and slug may be internationalized */
		public $checkout_page_name = 'checkout';

		public $is_classifieds_page = false;

		/** @public int My credits page ID */
		public $my_credits_page_id = 0;

		public $use_credits = false;
		public $use_paypal = false;
		public $use_authorizenet = false;

		public $use_free = false;
		public $use_recurring = false;
		public $use_one_time = false;

		public $transactions = null;

		/**
		 * Constructor.
		 *
		 * @return void
		 **/
		function __construct() {

			//Default capability map for Classifieds
			$this->capability_map = array(
				'read_classifieds'             => __( 'View classifieds.', $this->text_domain ),
				'read_private_classifieds'     => __( 'View private classifieds.', $this->text_domain ),
				'publish_classifieds'          => __( 'Add classifieds.', $this->text_domain ),
				'edit_classifieds'             => __( 'Edit classifieds.', $this->text_domain ),
				'edit_published_classifieds'   => __( 'Edit published classifieds.', $this->text_domain ),
				'edit_private_classifieds'     => __( 'Edit private classifieds.', $this->text_domain ),
				'delete_classifieds'           => __( 'Delete classifieds', $this->text_domain ),
				'delete_published_classifieds' => __( 'Delete published classifieds.', $this->text_domain ),
				'delete_private_classifieds'   => __( 'Delete private classifieds.', $this->text_domain ),
				'edit_others_classifieds'      => __( 'Edit others\' classifieds.', $this->text_domain ),
				'delete_others_classifieds'    => __( 'Delete others\' classifieds.', $this->text_domain ),
				'upload_files'                 => __( 'Upload files.', $this->text_domain ),
			);


			/* Register activation hook */
			register_activation_hook( $this->plugin_dir . 'loader.php', array( &$this, 'on_activate' ) );
			/* Register deactivation hook */
			register_deactivation_hook( $this->plugin_dir . 'loader.php', array( &$this, 'on_deactivate' ) );

			/* Initiate class variables from core class */
			add_action( 'init', array( &$this, 'init' ) );

			add_action( 'wp_print_scripts', array( &$this, 'on_print_scripts' ), 8 );


			/* Add theme support for post thumbnails */
			add_theme_support( 'post-thumbnails' );

			/* Create neccessary pages */
			add_action( 'wp_loaded', array( &$this, 'create_default_pages' ) );
			/* Setup roles and capabilities */
			//add_action( 'wp_loaded', array( &$this, 'roles' ) );
			/* Schedule expiration check */
			add_action( 'wp_loaded', array( &$this, 'schedule_expiration_check' ) );
			/* Add template filter */
			//add_filter( 'single_template', array( &$this, 'get_single_template' ) ) ;
			/* Add template filter */
			//add_filter( 'page_template', array( &$this, 'get_page_template' ) ) ;
			/* Add template filter */
			add_filter( 'taxonomy_template', array( &$this, 'get_taxonomy_template' ) );

			add_filter( 'parse_query', array( &$this, 'on_parse_query' ) );

			add_filter( 'wp_page_menu_args', array( &$this, 'hide_menu_pages' ), 99 );

			/* Handle login requests */
			add_action( 'template_redirect', array( &$this, 'handle_login_requests' ) );
			/* Handle all requests for checkout */
			//add_action( 'template_redirect', array( &$this, 'handle_checkout_requests' ) );
			/* Handle all requests for contact form submission */
			require_once $this->plugin_dir . 'core/class-contact-form-service.php';
			$this->contact_form_service = new CF_Contact_Form_Service( $this );
			add_action( 'template_redirect', array( $this->contact_form_service, 'handle_contact_form_requests' ) );
			require_once $this->plugin_dir . 'core/class-template-content-service.php';
			$this->template_content_service = new CF_Template_Content_Service( $this );
			require_once $this->plugin_dir . 'core/class-shortcode-service.php';
			$this->shortcode_service = new CF_Shortcode_Service( $this );

			add_action( 'wp_enqueue_scripts', array( &$this, 'on_enqueue_scripts' ) );
			add_action( 'pre_get_posts', array( &$this, 'on_pre_get_posts' ) );

			/* Check expiration dates */
			add_action( 'check_expiration_dates', array( &$this, 'check_expiration_dates_callback' ) );
			add_action( 'check_expiration_dates', array( &$this, 'maybe_expire_featured' ) );

			/** Map meta capabilities */
			add_filter( 'map_meta_cap', array( &$this, 'map_meta_cap' ), 11, 4 );
			/** Show only user's classifieds on classifieds posttype page*/
			add_filter( 'parse_query', array( &$this, 'show_only_c_user_classifieds' ) );

			// filter for $wp_query on classifieds page - it is necessary that the other plug-ins have not changed it in these pages
			add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts_for_classifieds' ), 101 );

			add_filter( 'user_contactmethods', array( &$this, 'contact_fields' ), 10, 2 );
			add_filter( 'admin_post_thumbnail_html', array( &$this, 'on_admin_post_thumbnail_html' ) );

			/** Hide theme prev/next post navigation on single classifieds */
			add_filter( 'next_post_link', array( &$this, 'suppress_adjacent_post_link_on_classifieds' ) );
			add_filter( 'previous_post_link', array( &$this, 'suppress_adjacent_post_link_on_classifieds' ) );

			add_filter( 'wp_ajax_cf-captcha', array( &$this, 'on_captcha' ) );
			add_filter( 'wp_ajax_nopriv_cf-captcha', array( &$this, 'on_captcha' ) );
			require_once $this->plugin_dir . 'core/class-favorites-manager.php';
			$this->favorites_manager = new CF_Favorites_Manager( $this );
			require_once $this->plugin_dir . 'core/class-reserved-status-service.php';
			$this->reserved_status_service = new CF_Reserved_Status_Service( $this );
			add_action( 'wp_ajax_cf_toggle_favorite', array( $this->favorites_manager, 'ajax_toggle_favorite' ) );
			add_action( 'wp_ajax_nopriv_cf_toggle_favorite', array( $this->favorites_manager, 'ajax_toggle_favorite' ) );
			add_action( 'wp_ajax_cf_remove_gallery_image', array( $this, 'ajax_remove_gallery_image' ) );
			require_once $this->plugin_dir . 'core/class-quick-view-service.php';
			$this->quick_view_service = new CF_Quick_View_Service( $this );
			add_action( 'wp_ajax_cf_quick_view', array( $this->quick_view_service, 'ajax_quick_view' ) );
			add_action( 'wp_ajax_nopriv_cf_quick_view', array( $this->quick_view_service, 'ajax_quick_view' ) );

			// Nachrichtensystem
			add_action( 'init', array( &$this, 'register_message_post_type' ) );
			require_once $this->plugin_dir . 'core/class-message-read-service.php';
			$this->message_read_service = new CF_Message_Read_Service( $this );
			require_once $this->plugin_dir . 'core/class-my-classifieds-ajax.php';
			$this->my_classifieds_ajax = new CF_My_Classifieds_Ajax( $this );
			add_action( 'wp_ajax_cf_send_message', array( $this->my_classifieds_ajax, 'ajax_send_message' ) );
			add_action( 'wp_ajax_cf_get_conversation', array( $this->my_classifieds_ajax, 'ajax_get_conversation' ) );
			add_action( 'wp_ajax_cf_mark_messages_read', array( $this->my_classifieds_ajax, 'ajax_mark_messages_read' ) );
			add_action( 'wp_ajax_cf_load_dashboard_tab', array( $this->my_classifieds_ajax, 'ajax_load_dashboard_tab' ) );
			add_action( 'wp_ajax_nopriv_cf_load_dashboard_tab', array( $this->my_classifieds_ajax, 'ajax_load_dashboard_tab' ) );
			add_action( 'wp_ajax_cf_toggle_featured', array( $this->my_classifieds_ajax, 'ajax_toggle_featured' ) );


			//Shortcodes
			add_shortcode( 'cf_list_categories', array( $this->shortcode_service, 'classifieds_categories_sc' ) );
			add_shortcode( 'cf_classifieds_btn', array( $this->shortcode_service, 'classifieds_btn_sc' ) );
			add_shortcode( 'cf_add_classified_btn', array( $this->shortcode_service, 'add_classified_btn_sc' ) );
			add_shortcode( 'cf_edit_classified_btn', array( $this->shortcode_service, 'edit_classified_btn_sc' ) );
			add_shortcode( 'cf_checkout_btn', array( $this->shortcode_service, 'checkout_btn_sc' ) );
			add_shortcode( 'cf_my_credits_btn', array( $this->shortcode_service, 'my_credits_btn_sc' ) );
			add_shortcode( 'cf_my_classifieds_btn', array( $this->shortcode_service, 'my_classifieds_btn_sc' ) );
			add_shortcode( 'cf_profile_btn', array( $this->shortcode_service, 'profile_btn_sc' ) );
			add_shortcode( 'cf_logout_btn', array( $this->shortcode_service, 'logout_btn_sc' ) );
			add_shortcode( 'cf_signin_btn', array( $this->shortcode_service, 'signin_btn_sc' ) );
			add_shortcode( 'cf_custom_fields', array( $this->shortcode_service, 'custom_fields_sc' ) );
		}


		function on_enqueue_scripts() {
			wp_enqueue_style( 'cf-taginput', $this->plugin_url . 'ui-front/css/cf-tagsinput.css' );
		}

		/**
		 * Remove one gallery image from a classifieds post via AJAX.
		 *
		 * @return void
		 */
		function ajax_remove_gallery_image() {
			check_ajax_referer( 'cf_manage_gallery', 'nonce' );

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => __( 'Bitte zuerst einloggen.', $this->text_domain ) ), 401 );
			}

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

			if ( $post_id <= 0 || $attachment_id <= 0 ) {
				wp_send_json_error( array( 'message' => __( 'Ungueltige Anfrage.', $this->text_domain ) ), 400 );
			}

			$post = get_post( $post_id );
			if ( ! $post || 'classifieds' !== $post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Anzeige wurde nicht gefunden.', $this->text_domain ) ), 404 );
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Keine Berechtigung fuer diese Anzeige.', $this->text_domain ) ), 403 );
			}

			$gallery_ids = get_post_meta( $post_id, '_cf_gallery_ids', true );
			if ( ! is_array( $gallery_ids ) ) {
				$gallery_ids = array();
			}
			$gallery_ids = array_values( array_filter( array_map( 'absint', $gallery_ids ) ) );

			if ( ! in_array( $attachment_id, $gallery_ids, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Bild ist nicht mehr in der Galerie.', $this->text_domain ) ), 409 );
			}

			$gallery_ids = array_values( array_diff( $gallery_ids, array( $attachment_id ) ) );
			update_post_meta( $post_id, '_cf_gallery_ids', $gallery_ids );

			wp_send_json_success( array(
				'gallery_ids' => $gallery_ids,
				'removed_id' => $attachment_id,
			) );
		}

		/**
		 * Initiate variables.
		 *
		 * @return void
		 **/
		function init() {
			global $wp, $wp_rewrite;

			$classifieds_obj = get_post_type_object( 'classifieds' );

			if ( ! empty( $classifieds_obj ) ) {

				if ( ! is_string( $slug = $classifieds_obj->has_archive ) ) {
					$slug = ! empty( $classifieds_obj->rewrite['slug'] ) ? $classifieds_obj->rewrite['slug'] : $this->post_type;
				}

				add_rewrite_rule( "author/([^/]+)/{$slug}/page/?([2-9][0-9]*)",
					"index.php?post_type=classifieds&author_name=\$matches[1]&paged=\$matches[2]", 'top' );

				add_rewrite_rule( "author/([^/]+)/{$slug}",
					"index.php?post_type=classifieds&author_name=\$matches[1]", 'top' );
			}

			// post_status "virtual" for pages not to be displayed in the menus but that users should not be editing.
			register_post_status( 'virtual', array(
				'label'                     => __( 'Virtual', $this->text_domain ),
				'public'                    => ( ! is_admin() ),
				//This trick prevents the virtual pages from appearing in the All Pages list but can be display on the front end.
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Virtual <span class="count">(%s)</span>', 'Virtual <span class="count">(%s)</span>' ),
			) );

			/* Set Taxonomy objects and names */
			$this->taxonomy_objects = get_object_taxonomies( $this->post_type, 'objects' );
			$this->taxonomy_names   = get_object_taxonomies( $this->post_type, 'names' );
/* Native Meta-Keys für Custom Fields */
                        $this->custom_fields['duration'] = '_cf_duration';
                        $this->custom_fields['cost']     = '_cf_cost';

			/* Set current user */
			$this->current_user = wp_get_current_user();

			$this->transactions = new CF_Transactions;

			/* Set current user credits */
			$this->user_credits = $this->transactions->credits;

			// Get pagination settings
			$options = $this->get_options( 'general' );

			$this->pagination_range  = ( isset( $options['pagination_range'] ) ) ? intval( $options['pagination_range'] ) : 4;
			$this->pagination_top    = ( ! empty( $options['pagination_top'] ) );
			$this->pagination_bottom = ( ! empty( $options['pagination_bottom'] ) );

			/* Set the member role for classifieds */
			$this->user_role = ( empty( $options['member_role'] ) ) ? get_option( 'default_role' ) : $options['member_role'];

			//How do we sell stuff
			$options = $this->get_options( 'payment_types' );

			$this->use_free = ( ! empty( $options['use_free'] ) );
			if ( ! $this->use_free ) { //Can't use gateways if it's free.

				$this->use_paypal = ( ! empty( $options['use_paypal'] ) );
				if ( $this->use_paypal ) { //make sure the api fields have something in them
					$this->use_paypal = ( ! empty( $options['paypal']['api_username'] ) ) && ( ! empty( $options['paypal']['api_password'] ) ) && ( ! empty( $options['paypal']['api_signature'] ) );
				}

				$this->use_authorizenet = ( ! empty( $options['use_authorizenet'] ) );
				if ( $this->use_authorizenet ) { //make sure the api fields have something in them
					$this->use_authorizenet = ( ! empty( $options['authorizenet']['api_user'] ) ) && ( ! empty( $options['authorizenet']['api_key'] ) );
				}

				$options = $this->get_options( 'payments' );

				$this->use_credits   = ( ! empty( $options['enable_credits'] ) );
				$this->use_recurring = ( ! empty( $options['enable_recurring'] ) );
				$this->use_one_time  = ( ! empty( $options['enable_one_time'] ) );
			}

			//Set a user capability based on users purchases
			global $blog_id;

			if ( ! is_multisite() || is_user_member_of_blog( get_current_user_id(), $blog_id ) ) {
				if ( $this->use_free
				     || ( $this->use_credits && $this->user_credits >= $options['credits_per_week'] )
				     || $this->is_full_access()
				) {
					if ( $this->current_user->has_cap( 'publish_classifieds' ) ) {
						$this->current_user->add_cap( 'create_classifieds' );
					} else {
						$this->current_user->remove_cap( 'create_classifieds' );

					}

				} else {
					$this->current_user->remove_cap( 'create_classifieds' );
				}
			}
		}

		/**
		 * Save custom post fields using native WordPress meta handling.
		
		 *
		 * @param int   $post_id The post ID.
		 * @param array $post_data $_POST data or similar array containing field values.
		 * @return void
		 */
		function save_post_custom_fields_native( $post_id, $post_data = array() ) {
                        if ( isset( $post_data['_cf_duration'] ) ) {
                                update_post_meta( $post_id, '_cf_duration', sanitize_text_field( $post_data['_cf_duration'] ) );
                        }
                        if ( isset( $post_data['_cf_cost'] ) ) {
								$normalized_cost = $this->normalize_price_value( $post_data['_cf_cost'] );
								update_post_meta( $post_id, '_cf_cost', $normalized_cost );
								update_post_meta( $post_id, '_cf_cost_num', ( '' === $normalized_cost ) ? '' : (float) $normalized_cost );
			}
		}

		/**
		 * Normalize price values to a decimal string for reliable filtering/sorting.
		 *
		 * @param mixed $raw_price Raw user input.
		 *
		 * @return string
		 */
		function normalize_price_value( $raw_price ) {
			$price = (string) $raw_price;
			$price = preg_replace( '/[^0-9,\.]/', '', $price );

			if ( false !== strpos( $price, ',' ) && false !== strpos( $price, '.' ) ) {
				if ( strrpos( $price, ',' ) > strrpos( $price, '.' ) ) {
					$price = str_replace( '.', '', $price );
					$price = str_replace( ',', '.', $price );
				} else {
					$price = str_replace( ',', '', $price );
				}
			} elseif ( false !== strpos( $price, ',' ) ) {
				$price = str_replace( ',', '.', $price );
			}

			$price = trim( $price );

			if ( '' === $price || ! is_numeric( $price ) ) {
				return '';
			}

			return number_format( (float) $price, 2, '.', '' );
		}

		/**
		 * Backfill numeric price meta for legacy ads.
		 *
		 * @return void
		 */
		function ensure_cost_numeric_index() {
			$transient_key = 'cf_cost_num_indexed';

			if ( get_transient( $transient_key ) ) {
				return;
			}

			$post_ids = get_posts(
				array(
					'post_type'      => 'classifieds',
					'post_status'    => 'any',
					'fields'         => 'ids',
					'numberposts'    => 200,
					'suppress_filters' => true,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => '_cf_cost',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_cf_cost_num',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					$raw_cost        = get_post_meta( $post_id, '_cf_cost', true );
					$normalized_cost = $this->normalize_price_value( $raw_cost );

					if ( '' !== $normalized_cost ) {
						update_post_meta( $post_id, '_cf_cost', $normalized_cost );
						update_post_meta( $post_id, '_cf_cost_num', (float) $normalized_cost );
					}
				}
			}

			set_transient( $transient_key, 1, 30 * MINUTE_IN_SECONDS );
		}

		/**
		 * Get current user's or guest's favorite classifieds.
		 *
		 * @return array<int>
		 */
		function get_favorite_ids() {
			return $this->favorites_manager->get_favorite_ids();
		}

		/**
		 * Persist favorite classifieds for logged-in users or guests.
		 *
		 * @param array<int> $favorites Favorite post ids.
		 * @return void
		 */
		function persist_favorite_ids( $favorites ) {
			$this->favorites_manager->persist_favorite_ids( $favorites );
		}

		/**
		 * Check if a classifieds post is currently favorited.
		 *
		 * @param int $post_id Post id.
		 * @return bool
		 */
		function is_favorite_post( $post_id ) {
			return $this->favorites_manager->is_favorite_post( $post_id );
		}

		/**
		 * Check if reserved state can be changed by current user.
		 *
		 * @param int $post_id Post id.
		 * @return bool
		 */
		function can_manage_reserved_status( $post_id ) {
			return $this->reserved_status_service->can_manage_reserved_status( $post_id );
		}

		/**
		 * Check reserved state for classifieds post.
		 *
		 * @param int $post_id Post id.
		 * @return bool
		 */
		function is_reserved_post( $post_id ) {
			return $this->reserved_status_service->is_reserved_post( $post_id );
		}

		/**
		 * Toggle reserved state for classifieds post.
		 *
		 * @param int $post_id Post id.
		 * @return bool New reserved state.
		 */
		function toggle_reserved_status( $post_id ) {
			return $this->reserved_status_service->toggle_reserved_status( $post_id );
		}

		/**
		 * Contact fields to add to the User profile
		 *
		 * @param array $contact_fields
		 * @param object $user_id
		 *
		 * @return array
		 */
		function suppress_adjacent_post_link_on_classifieds( $output ) {
			if ( is_singular( 'classifieds' ) ) {
				return '';
			}
			return $output;
		}

		function contact_fields( $contact_fields = array(), $user = null ) {

			$cc_contact = array(
				'cc_email'        => __( 'CC Email', $this->text_domain ),
				'cc_firstname'    => __( 'CC First Name', $this->text_domain ),
				'cc_lastname'     => __( 'CC Last Name', $this->text_domain ),
				'cc_street'       => __( 'CC Street', $this->text_domain ),
				'cc_city'         => __( 'CC City', $this->text_domain ),
				'cc_state'        => __( 'CC State', $this->text_domain ),
				'cc_zip'          => __( 'CC Zip', $this->text_domain ),
				'cc_country_code' => __( 'CC Country Code', $this->text_domain ),
			);

			return array_merge( $cc_contact, $contact_fields );

		}

		/**
		 * Create the default Directory member roles and capabilities.
		 *
		 * @return void
		 */
		function create_default_classifieds_roles() {

			//set capability for admin
			$admin = get_role( 'administrator' );
			foreach ( array_keys( $this->capability_map ) as $capability ) {
				$admin->add_cap( $capability );
			}
		}

		/**
		 * Update plugin versions
		 *
		 * @return void
		 **/
		function on_activate() {
			$this->create_default_classifieds_roles();

			/* Update plugin versions */
			$versions = array(
				'versions' => array(
					'version'    => $this->plugin_version,
					'db_version' => $this->plugin_db_version
				)
			);
			$options  = get_site_option( $this->options_name );
			$options  = ( isset( $options['versions'] ) ) ? array_merge( $options, $versions ) : $versions;
			update_site_option( $this->options_name, $options );
		}

		/**
		 * Deactivate plugin. If $this->flush_plugin_data is set to "true"
		 * all plugin data will be deleted
		 *
		 * @return void
		 */
		function on_deactivate() {
			//Remove virtual Pages
			$post_statuses = get_post_stati();
			foreach ( $post_statuses as $post_status ) {
				$args = array(
					'hierarchical' => 0,
					'meta_key'     => 'classifieds_type',
					'post_type'    => 'page',
					'post_status'  => $post_status
				);

				$pages = get_pages( $args );

				foreach ( $pages as $page ) {
					if ( isset( $page ) && 0 < $page->ID ) {
						wp_delete_post( $page->ID, true );
					}
				}
			}

			return false;
		}

		function on_plugins_loaded() {

			//Loads "classifieds-[xx_XX].mo" language file from the "languages" classifieds
			load_plugin_textdomain( $this->text_domain, false, plugin_basename( $this->plugin_dir . 'languages' ) );

			//If the activate flag is set then try to initalize the defaults
			if ( get_site_option( 'cf_activate', false ) ) {
				include_once( $this->plugin_dir . 'core/data.php' );
				new Classifieds_Core_Data();
				delete_site_option( 'cf_activate' );
			}
		}

		function on_parse_query( $query ) {
			global $wp_query;

			if ( is_object( $wp_query ) ) {
				//Handle any security redirects

				if ( ! is_user_logged_in() ) {
					if ( @is_page( $this->add_classified_page_id )
					     || @is_page( $this->edit_classified_page_id )
					     || @is_page( $this->my_classifieds_page_id )
					     || @is_page( $this->my_credits_page_id )
					     || @is_page( $this->checkout_page_id )
					) {

						$args = array( 'redirect_to' => urlencode( get_permalink( $query->queried_object_id ) ) );
						if ( ! empty( $_REQUEST['register'] ) ) {
							$args['register'] = sanitize_text_field( wp_unslash( $_REQUEST['register'] ) );
						}
						if ( ! empty( $_REQUEST['reset'] ) ) {
							$args['reset'] = sanitize_text_field( wp_unslash( $_REQUEST['reset'] ) );
						}

						wp_safe_redirect( esc_url_raw( add_query_arg( $args, get_permalink( $this->signin_page_id ) ) ) );
						exit;
					}
				}

				//Are are we managing credits?
				if ( ! $this->use_credits ) {
					if ( @is_page( $this->my_credits_page_id ) ) {
						wp_safe_redirect( get_permalink( $this->my_classifieds_page_id ) );
						exit;
					}
				}

				//Are we adding a classified?
				if ( ! ( current_user_can( 'create_classifieds' ) && current_user_can( 'publish_classifieds' ) ) ) {
					if ( @is_page( $this->add_classified_page_id ) ) {
						wp_safe_redirect( get_permalink( $this->my_classifieds_page_id ) );
						exit;
					}
				}

				//Or are we editing a classified?
				//Can the user edit classifieds?
				$post_id = ! empty( $_REQUEST['post_id'] ) ? absint( wp_unslash( $_REQUEST['post_id'] ) ) : 0;
				if ( $post_id && ! current_user_can( 'edit_classified', $post_id ) ) {
					if ( @is_page( $this->edit_classified_page_id ) ) {
						wp_safe_redirect( get_permalink( $this->my_classifieds_page_id ) );
						exit;
					}
				}
			}

			return $query;
		}

		//filters the titles for our custom pages
		function delete_post_title( $title, $id = false ) {
			global $wp_query;
			if ( $title == $wp_query->post->post_title ) {
				return '';
			}

			return $title;
		}

		/**
		 * Hide some menu pages
		 */
		function hide_menu_pages( $args ) {

			$pages = ( empty( $args['exclude'] ) ) ? '' : $args['exclude'];

			//If free no need for checkout
			if ( $this->use_free ) {
				$pages .= ',' . $this->checkout_page_id;
			}

			//If nothing saleable no checkout
			if ( ! $this->use_credits && ! $this->use_one_time && ! $this->use_recurring ) {
				$pages .= ',' . $this->checkout_page_id;
			}

			$args['exclude'] = trim( $pages, ',' );

			return $args;
		}

		/**
		 * filter for $wp_query on classifieds page - it is necessary that the other plug-ins have not changed it in these pages
		 *
		 * @return void
		 **/
		function pre_get_posts_for_classifieds() {
			global $wp_query;

			if ( isset( $wp_query->query_vars['post_type'][0] ) && 'classifieds' == $wp_query->query_vars['post_type'][0] ) {
				$wp_query->query_vars['cat']          = '';
				$wp_query->query_vars['category__in'] = array();
				$wp_query->query_vars['showposts']    = '';
			}
		}


		/**
		 * Get page by meta value
		 *
		 * @return int $page[0] /bool false
		 */
		function get_page_by_meta( $value ) {
			global $wpdb;

			//To avoid "the_posts" filters do a direct call to the database to find the post by meta
			$ids = array_keys(
				$wpdb->get_results( $wpdb->prepare(
					"
		SELECT post_id
		FROM {$wpdb->postmeta}
		WHERE meta_key='classifieds_type'
		AND meta_value=%s
		", $value ), OBJECT_K )
			);

			if ( count( $ids ) != 1 ) { //There can be only one.
				foreach ( $ids as $id ) { //Delete all and start over.
					delete_post_meta( $id, "classifieds_type" );
					wp_delete_post( $id, true );
				}

				return false;
			}

			if ( get_post_status( $ids[0] ) == 'trash' ) { //no trash
				wp_delete_post( $ids[0], true );

				return false;
			}

			if ( isset( $ids[0] ) && 0 < $ids[0] ) {
				return get_post( $ids[0] );
			}

			return false;
		}

		/**
		 * Create the default Classifieds pages.
		 *
		 * @return void
		 **/
		function create_default_pages() {
			/* Create neccessary pages */

			$post_content = __( 'Virtual page. Editing this page won\'t change anything.', $this->text_domain );

			//Classifieds list
			$classifieds_page = $this->get_page_by_meta( 'classifieds' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'Classifieds',
					'post_status'    => 'publish',
					//'post_author'    => $current_user->ID,
					'post_type'      => 'page',
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'classifieds' );
			}

			$this->classifieds_page_id   = $page_id; //Remember the number
			$this->classifieds_page_slug = $classifieds_page->post_name; //Remember the slug

			//My Classifieds
			$classifieds_page = $this->get_page_by_meta( 'my_classifieds' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'My Classifieds',
					'post_status'    => 'publish',
					//'post_author'    => $current_user->ID,
					'post_type'      => 'page',
					'post_parent'    => $this->classifieds_page_id,
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'my_classifieds' );
			}

			$this->my_classifieds_page_id   = $page_id; // Remember the number
			$this->my_classifieds_page_slug = $classifieds_page->post_name; //Remember the slug

			//Classifieds Checkout
			$classifieds_page = $this->get_page_by_meta( 'checkout_classified' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'Kleinanzeigen Kasse',
					'post_name'      => 'checkout',
					'post_status'    => 'publish',
					//'post_author'    => $current_user->ID,
					'post_type'      => 'page',
					'post_parent'    => $this->classifieds_page_id,
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
					'menu_order'     => 1
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'checkout_classified' );
			} elseif ( 'Classifieds Checkout' === $classifieds_page->post_title ) {
				// Safe one-time migration: only rename untouched legacy title.
				wp_update_post( array(
					'ID'         => $page_id,
					'post_title' => 'Kleinanzeigen Kasse',
				) );
				$classifieds_page = get_post( $page_id );
			}

			$this->checkout_page_id   = $page_id; // Remember the number
			$this->checkout_page_slug = $classifieds_page->post_name; //Remember the slug

			$classifieds_page = $this->get_page_by_meta( 'add_classified_page' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'Add Classified',
					'post_status'    => 'virtual',
					//'post_author'    => $current_user->ID,
					'post_parent'    => $this->classifieds_page_id,
					'post_type'      => 'page',
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'add_classified_page' );
			} else {
				if ( ! in_array( $classifieds_page->post_status, array( 'virtual', 'trash' ) ) ) {
					wp_update_post( array( 'ID' => $page_id, 'post_status' => 'virtual' ) );
				}
			}

			$this->add_classified_page_id        = $page_id; // Remember the number
			$this->add_classified_page_page_slug = $classifieds_page->post_name; //Remember the slug

			$classifieds_page = $this->get_page_by_meta( 'edit_classified' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'Edit Classified',
					'post_status'    => 'virtual',
					//'post_author'    => $current_user->ID,
					'post_parent'    => $this->classifieds_page_id,
					'post_type'      => 'page',
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'edit_classified' );
			} else {
				if ( ! in_array( $classifieds_page->post_status, array( 'virtual', 'trash' ) ) ) {
					wp_update_post( array( 'ID' => $page_id, 'post_status' => 'virtual' ) );
				}
			}

			$this->edit_classified_page_id   = $page_id; // Remember the number
			$this->edit_classified_page_slug = $classifieds_page->post_name; //Remember the slug

			$classifieds_page = $this->get_page_by_meta( 'my_classifeds_credits' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				$current_user = wp_get_current_user();
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'My Classifieds Credits',
					'post_name'      => 'my-credits',
					'post_status'    => 'virtual',
					//'post_author'    => $current_user->ID,
					'post_parent'    => $this->classifieds_page_id,
					'post_type'      => 'page',
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", 'my_classifeds_credits' );
			} else {
				if ( ! in_array( $classifieds_page->post_status, array( 'virtual', 'trash' ) ) ) {
					wp_update_post( array( 'ID' => $page_id, 'post_status' => 'virtual' ) );
				}
			}

			$this->my_credits_page_id   = $page_id; // Remember the number
			$this->my_credits_page_slug = $classifieds_page->post_name; //Remember the slug

			$classifieds_page = $this->get_page_by_meta( 'classifieds_signin' );
			$page_id          = ( $classifieds_page && $classifieds_page->ID > 0 ) ? $classifieds_page->ID : 0;

			if ( empty( $page_id ) ) {
				/* Construct args for the new post */
				$args             = array(
					'post_title'     => 'Classifieds Signin',
					'post_name'      => 'signin',
					'post_status'    => 'virtual',
					//'post_author'    => $current_user->ID,
					'post_parent'    => $this->classifieds_page_id,
					'post_type'      => 'page',
					'post_content'   => $post_content,
					'ping_status'    => 'closed',
					'comment_status' => 'closed'
				);
				$page_id          = wp_insert_post( $args );
				$classifieds_page = get_post( $page_id );
				add_post_meta( $page_id, "classifieds_type", "classifieds_signin" );
			} else {
				if ( ! in_array( $classifieds_page->post_status, array( 'virtual', 'trash' ) ) ) {
					wp_update_post( array( 'ID' => $page_id, 'post_status' => 'virtual' ) );
				}
			}

			$this->signin_page_id   = $page_id; //Remember the number
			$this->signin_page_slug = $classifieds_page->post_name; //Remember the slug

		}

		/**
		 * Print a list of javascript vars specific to Directory for use in javascript routines
		 *
		 */
		function on_print_scripts() {
			$frontend_options = $this->get_options( 'frontend' );
			$archive_auto_restore = 1;
			if ( isset( $frontend_options['archive_auto_restore'] ) ) {
				$archive_auto_restore = (int) $frontend_options['archive_auto_restore'];
			}

			echo '<script type="text/javascript">';
			echo "\nvar\n";
			echo "cf_classifieds = '" . esc_attr( get_permalink( $this->classifieds_page_id ) ) . "';\n";
			echo "cf_add = '" . esc_attr( get_permalink( $this->add_classified_page_id ) ) . "';\n";
			echo "cf_edit = '" . esc_attr( get_permalink( $this->edit_classified_page_id ) ) . "';\n";
			echo "cf_my = '" . esc_attr( get_permalink( $this->my_classifieds_page_id ) ) . "';\n";
			echo "cf_credits = '" . esc_attr( get_permalink( $this->my_credits_page_id ) ) . "';\n";
			echo "cf_checkout = '" . esc_attr( get_permalink( $this->checkout_page_id ) ) . "';\n";
			echo "cf_signin = '" . esc_attr( get_permalink( $this->signin_page_id ) ) . "';\n";
			echo 'window.cfFrontend = ' . wp_json_encode(
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'cf_frontend_actions' ),
					'autoRestoreDefault' => 1 === $archive_auto_restore,
					'i18n'     => array(
						'favorite'        => __( 'Merken', $this->text_domain ),
						'favorited'       => __( 'Gemerkt', $this->text_domain ),
						'quickView'       => __( 'Schnellansicht', $this->text_domain ),
						'loading'         => __( 'Wird geladen ...', $this->text_domain ),
						'loadError'       => __( 'Die Schnellansicht konnte gerade nicht geladen werden.', $this->text_domain ),
						'copySuccess'     => __( 'Link kopiert', $this->text_domain ),
						'copyDefault'     => __( 'Link teilen', $this->text_domain ),
						'saveFilterPrompt'=> __( 'Wie willst du diesen Filter nennen?', $this->text_domain ),
						'saveFilterEmpty' => __( 'Gib erst einen Namen fuer den Filter ein.', $this->text_domain ),
						'saveFilterDone'  => __( 'Filter wurde gemerkt.', $this->text_domain ),
						'deleteFilterDone'=> __( 'Filter wurde geloescht.', $this->text_domain ),
						'deleteFilterAsk' => __( 'Willst du diesen gespeicherten Filter wirklich loeschen?', $this->text_domain ),
						'loadFilterEmpty' => __( 'Waehle erst einen gespeicherten Filter aus.', $this->text_domain ),
					),
				)
			) . ';';
			echo "</script>\n";
		}


		/**
		 * Process login request.
		 *
		 * @param string $username
		 * @param string $password
		 *
		 * @return object $result->errors
		 **/
		function login( $username, $password ) {
			/* Check whether the required information is submitted */
			if ( empty( $username ) || empty( $password ) ) {
				return __( 'Bitte fuell alle Pflichtfelder aus.', $this->text_domain );
			}
			/* Build the login credentials */
			$credentials = array( 'remember' => true, 'user_login' => $username, 'user_password' => $password );
			/* Sign the user in and get the result */
			$result = wp_signon( $credentials );
			if ( isset( $result->errors ) ) {
				if ( isset( $result->errors['invalid_username'] ) ) {
					return $result->errors['invalid_username'][0];
				} elseif ( isset( $result->errors['incorrect_password'] ) ) {
					return $result->errors['incorrect_password'][0];
				}
			}
		}

		/**
		 * Add custom role for Classifieds members. Add new capabilities for admin.
		 *
		 * @global $wp_roles
		 * @return void
		 **/

		/*
	function roles() {
	global $wp_roles;

	// @todo remove remove_role
	if ( $wp_roles ) {
	$wp_roles->remove_role( $this->user_role );

	$wp_roles->add_role( $this->user_role, 'Classifieds Member', array(
	'publish_classifieds'       => true,
	'edit_classified'          => true,
	'edit_others_classifieds'   => false,
	'delete_classifieds'        => true,
	'delete_others_classifieds' => false,
	'read_private_classifieds'  => false,
	'edit_classified'           => true,
	'delete_classified'         => true,
	'read_classified'           => true,
	'upload_files'              => true,
	'assign_terms'              => true,
	'read'                      => true
	) );

	// Set administrator roles
	$wp_roles->add_cap( 'administrator', 'publish_classifieds' );
	$wp_roles->add_cap( 'administrator', 'edit_classified' );
	$wp_roles->add_cap( 'administrator', 'edit_others_classifieds' );
	$wp_roles->add_cap( 'administrator', 'delete_classifieds' );
	$wp_roles->add_cap( 'administrator', 'delete_others_classifieds' );
	$wp_roles->add_cap( 'administrator', 'read_private_classifieds' );
	$wp_roles->add_cap( 'administrator', 'edit_classified' );
	$wp_roles->add_cap( 'administrator', 'delete_classified' );
	$wp_roles->add_cap( 'administrator', 'read_classified' );
	$wp_roles->add_cap( 'administrator', 'assign_terms' );
	}
	}
	*/

		/**
		 * Show only current user classifieds on page of classifieds posttype page.
		 *
		 * @return void
		 **/
		function show_only_c_user_classifieds( $wp_query ) {
			if ( is_admin() ) {
				$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
				if ( 'classifieds' === $post_type && ! current_user_can( 'manage_options' ) ) {
					$wp_query->set( 'author', get_current_user_id() );
				}
			}
		}

		/**
		 * Map meta capabilities
		 *
		 * Learn more:
		 * @link http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types
		 * @link http://wordpress.stackexchange.com/questions/1684/what-is-the-use-of-map-meta-cap-filter/2586#2586
		 *
		 * @param <type> $caps
		 * @param <type> $cap
		 * @param <type> $user_id
		 * @param <type> $args
		 *
		 * @return array
		 **/
		function map_meta_cap( $caps, $cap, $user_id, $args ) {

			/* If editing, deleting, or reading a classified, get the post and post type object. */
			if ( 'edit_classified' == $cap || 'delete_classified' == $cap || 'read_classified' == $cap ) {
				$post      = get_post( $args[0] );
				$post_type = get_post_type_object( $post->post_type );

				/* Set an empty array for the caps. */
				$caps = array();
			}

			/* If editing a classified, assign the required capability. */
			if ( 'edit_classified' == $cap ) {
				if ( $user_id == $post->post_author ) {
					$caps[] = $post_type->cap->edit_posts;
				} else {
					$caps[] = $post_type->cap->edit_others_posts;
				}
			} /* If deleting a classified, assign the required capability. */
			elseif ( 'delete_classified' == $cap ) {
				if ( $user_id == $post->post_author ) {
					$caps[] = $post_type->cap->delete_posts;
				} else {
					$caps[] = $post_type->cap->delete_others_posts;
				}
			} /* If reading a private classified, assign the required capability. */
			elseif ( 'read_classified' == $cap ) {

				if ( 'private' != $post->post_status ) {
					$caps[] = 'read';
				} elseif ( $user_id == $post->post_author ) {
					$caps[] = 'read';
				} else {
					$caps[] = $post_type->cap->read_private_posts;
				}
			}

			/* Return capabilities required by the user. */

			return $caps;
		}

		/**
		 * Update or insert ad if no ID is passed.
		 *
		 * @param array $params Array of $_POST data
		 *
		 * @return int $post_id
		 **/
		function update_ad( $params ) {

			$current_user = wp_get_current_user();
			/* Construct args for the new post */
			$args = array(
				/* If empty ID insert Ad insetad of updating it */
				'ID'           => ( isset( $params['classified_data']['ID'] ) ) ? $params['classified_data']['ID'] : '',
				'post_title'   => wp_strip_all_tags( $params['classified_data']['post_title'] ),
				'post_name'    => '',
				'post_content' => wp_kses( $params['classified_data']['post_content'], cf_wp_kses_allowed_html() ),
				'post_excerpt' => ( empty( $params['classified_data']['post_excerpt'] ) ) ? '' : wp_kses( $params['classified_data']['post_excerpt'], cf_wp_kses_allowed_html() ),
				'post_status'  => $params['classified_data']['post_status'],
				//'post_author'    => get_current_user_id(),
				'post_type'    => $this->post_type,
				'ping_status'  => 'closed',
				//'comment_status' => 'open'
			);

			/* Insert page and get the ID */
			if ( empty( $args['ID'] ) ) {
				$post_id = wp_insert_post( $args );
			} else {
				$post_id = wp_update_post( $args );
			}

			if ( ! empty( $post_id ) ) {
				//Save custom tags
				if ( isset( $params['tag_input'] ) && is_array( $params['tag_input'] ) ) {
					foreach ( $params['tag_input'] as $key => $tags ) {
						wp_set_post_terms( $post_id, $params['tag_input'][ $key ], $key );
					}
				}

				//Save categories
				if ( isset( $params['post_category'] ) && is_array( $params['post_category'] ) ) {
					wp_set_post_terms( $post_id, $params['post_category'], 'category' );
				}

				//Save custom terms
				if ( isset( $params['tax_input'] ) && is_array( $params['tax_input'] ) ) {
					foreach ( $params['tax_input'] as $key => $term_ids ) {
						if ( is_array( $params['tax_input'][ $key ] ) ) {
							wp_set_post_terms( $post_id, $params['tax_input'][ $key ], $key );
						}
					}
				}

				// Save native fields from both legacy and direct form inputs.
				$this->save_post_custom_fields_native( $post_id, $_POST );

				$duration_meta_key = ! empty( $this->custom_fields['duration'] ) ? $this->custom_fields['duration'] : '_cf_duration';
$cost_meta_key     = '_cf_cost';

				if ( isset( $params[ $duration_meta_key ] ) ) {
					update_post_meta( $post_id, $duration_meta_key, sanitize_text_field( $params[ $duration_meta_key ] ) );
				}

				if ( isset( $params['duration'] ) ) {
					update_post_meta( $post_id, $duration_meta_key, sanitize_text_field( $params['duration'] ) );
				}

				if ( isset( $params[ $cost_meta_key ] ) ) {
					$normalized_cost = $this->normalize_price_value( $params[ $cost_meta_key ] );
					update_post_meta( $post_id, $cost_meta_key, $normalized_cost );
					update_post_meta( $post_id, '_cf_cost_num', ( '' === $normalized_cost ) ? '' : (float) $normalized_cost );
				}

				if ( isset( $params['cost'] ) ) {
					$normalized_cost = $this->normalize_price_value( $params['cost'] );
					update_post_meta( $post_id, $cost_meta_key, $normalized_cost );
					update_post_meta( $post_id, '_cf_cost_num', ( '' === $normalized_cost ) ? '' : (float) $normalized_cost );
				}


				if ( isset( $_FILES['feature_image'] ) && empty( $_FILES['feature_image']['error'] ) ) {
					/* Require WordPress utility functions for handling media uploads */
					require_once( ABSPATH . '/wp-admin/includes/media.php' );
					require_once( ABSPATH . '/wp-admin/includes/image.php' );
					require_once( ABSPATH . '/wp-admin/includes/file.php' );
					/* Upload the image ( handles creation of thumbnails etc. ), set featured image  */
					$thumbnail_id = media_handle_upload( 'feature_image', $post_id );
					set_post_thumbnail( $post_id, $thumbnail_id );
				}

				$existing_gallery_ids = get_post_meta( $post_id, '_cf_gallery_ids', true );
				if ( ! is_array( $existing_gallery_ids ) ) {
					$existing_gallery_ids = array();
				}
				$existing_gallery_ids = array_values( array_filter( array_map( 'absint', $existing_gallery_ids ) ) );

				$remove_gallery_ids = isset( $params['remove_gallery_ids'] ) ? array_values( array_filter( array_map( 'absint', (array) $params['remove_gallery_ids'] ) ) ) : array();
				if ( ! empty( $remove_gallery_ids ) ) {
					$existing_gallery_ids = array_values( array_diff( $existing_gallery_ids, $remove_gallery_ids ) );
					update_post_meta( $post_id, '_cf_gallery_ids', $existing_gallery_ids );
				}

				if ( isset( $_FILES['feature_gallery'] ) && ! empty( $_FILES['feature_gallery']['name'] ) && is_array( $_FILES['feature_gallery']['name'] ) ) {
					require_once( ABSPATH . '/wp-admin/includes/media.php' );
					require_once( ABSPATH . '/wp-admin/includes/image.php' );
					require_once( ABSPATH . '/wp-admin/includes/file.php' );

					$gallery_ids = array();
					$files_count = count( $_FILES['feature_gallery']['name'] );

					for ( $i = 0; $i < $files_count; $i ++ ) {
						if ( empty( $_FILES['feature_gallery']['name'][ $i ] ) || ! empty( $_FILES['feature_gallery']['error'][ $i ] ) ) {
							continue;
						}

						$_FILES['cf_gallery_upload'] = array(
							'name'     => $_FILES['feature_gallery']['name'][ $i ],
							'type'     => $_FILES['feature_gallery']['type'][ $i ],
							'tmp_name' => $_FILES['feature_gallery']['tmp_name'][ $i ],
							'error'    => $_FILES['feature_gallery']['error'][ $i ],
							'size'     => $_FILES['feature_gallery']['size'][ $i ],
						);

						$attachment_id = media_handle_upload( 'cf_gallery_upload', $post_id );
						if ( ! is_wp_error( $attachment_id ) ) {
							$gallery_ids[] = (int) $attachment_id;
						}
					}

					unset( $_FILES['cf_gallery_upload'] );

					if ( ! empty( $gallery_ids ) ) {
						$merged_gallery_ids = array_values( array_unique( array_merge( $existing_gallery_ids, array_map( 'absint', $gallery_ids ) ) ) );
						update_post_meta( $post_id, '_cf_gallery_ids', $merged_gallery_ids );
					}
				}

				return $post_id;
			}
		}

		/**
		 * Checking that current user has full access to add ads without credits.
		 *
		 * @return boolean
		 **/
		function is_full_access() {
			global $blog_id;

			$result = false;

			//for admin
			if ( current_user_can( 'manage_options' ) || $this->use_free ) {
				$result = true;
			}

			//for paid users
			if ( $this->transactions->billing_type ) {
				if ( 'one_time' == $this->transactions->billing_type && 'success' == $this->transactions->status ) {
					$result = true;
				} elseif ( 'recurring' == $this->transactions->billing_type && 'success' == $this->transactions->status ) {
					if ( time() < $this->transactions->expires ) {
						$result = true;
					} else {
						$this->transactions->status = 'expired';
					}
				}
			}

			return apply_filters( 'classifieds_full_access', $result );
		}

		/**
		 * Return the number of credits based on the duration selected.
		 *
		 * @return int|string Number of credits
		 **/
		function parse_duration_interval_data( $duration ) {
			$raw_duration = trim( (string) $duration );
			if ( '' === $raw_duration ) {
				return array(
					'modifier' => '+1 week',
					'weeks'    => 1,
				);
			}

			$normalized_duration = wp_strip_all_tags( $raw_duration );
			$normalized_duration = str_replace( '-', ' ', $normalized_duration );
			$normalized_duration = preg_replace( '/\s+/', ' ', $normalized_duration );
			$normalized_duration = trim( strtolower( $normalized_duration ) );

			if ( preg_match( '/^(\d+)\s*([[:alpha:][:punct:]]+)/u', $normalized_duration, $matches ) ) {
				$quantity = max( 1, absint( $matches[1] ) );
				$unit_raw = trim( $matches[2], ". \t\n\r\0\x0B" );

				$unit_map = array(
					'tag'    => array( 'modifier' => 'day', 'weeks' => 1 / 7 ),
					'tage'   => array( 'modifier' => 'day', 'weeks' => 1 / 7 ),
					'day'    => array( 'modifier' => 'day', 'weeks' => 1 / 7 ),
					'days'   => array( 'modifier' => 'day', 'weeks' => 1 / 7 ),
					'woche'  => array( 'modifier' => 'week', 'weeks' => 1 ),
					'wochen' => array( 'modifier' => 'week', 'weeks' => 1 ),
					'week'   => array( 'modifier' => 'week', 'weeks' => 1 ),
					'weeks'  => array( 'modifier' => 'week', 'weeks' => 1 ),
					'monat'  => array( 'modifier' => 'month', 'weeks' => 4 ),
					'monate' => array( 'modifier' => 'month', 'weeks' => 4 ),
					'month'  => array( 'modifier' => 'month', 'weeks' => 4 ),
					'months' => array( 'modifier' => 'month', 'weeks' => 4 ),
				);

				if ( isset( $unit_map[ $unit_raw ] ) ) {
					return array(
						'modifier' => sprintf( '+%d %s', $quantity, $unit_map[ $unit_raw ]['modifier'] ),
						'weeks'    => (float) $quantity * (float) $unit_map[ $unit_raw ]['weeks'],
					);
				}
			}

			$timestamp_now = time();
			$parsed = strtotime( '+' . $raw_duration, $timestamp_now );
			if ( false !== $parsed && $parsed > $timestamp_now ) {
				return array(
					'modifier' => '+' . $raw_duration,
					'weeks'    => ( $parsed - $timestamp_now ) / WEEK_IN_SECONDS,
				);
			}

			return array(
				'modifier' => '+1 week',
				'weeks'    => 1,
			);
		}

		function get_credits_from_duration( $duration ) {

			$options = $this->get_options( 'payments' );
			$duration_data = $this->parse_duration_interval_data( $duration );

			if ( empty( $options['enable_credits'] ) || ! isset( $options['credits_per_week'] ) || $this->use_free ) {
				$options['credits_per_week'] = 0;
			}

			return round( (float) $duration_data['weeks'] * (float) $options['credits_per_week'] );
		}


		/**
		 * Handle user login.
		 *
		 * @return void
		 **/
		function handle_login_requests() {

			if ( isset( $_POST['login_submit'] ) ) {
				$username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) );
				$password = wp_unslash( $_POST['password'] ?? '' );
				$this->login_error = $this->login( $username, $password );
			}
		}

		/**
		 * Registriert den cf_message Custom Post Type für interne Nachrichten
		 */
		function register_message_post_type() {
			register_post_type( 'cf_message', array(
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'custom-fields' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			) );
		}

		/**
		 * Liefert alle Konversationen eines Users (als Sender oder Empfänger)
		 */
		function get_user_conversations( $user_id, $type = 'all' ) {
			return $this->message_read_service->get_user_conversations( $user_id, $type );
		}

		/**
		 * Anzahl ungelesener Nachrichten für einen User
		 */
		function get_unread_message_count( $user_id = 0 ) {
			return $this->message_read_service->get_unread_message_count( $user_id );
		}

		/**
		 * Save custom fields data
		 *
		 * @param int $post_id The post id of the post being edited
		 *
		 * @return NULL If there is autosave attempt
		 **/
		function save_expiration_date( $post_id, $force_restart = false ) {
			/* prevent autosave from deleting the custom fields */
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$current_expiration = (int) get_post_meta( $post_id, '_expiration_date', true );
			$now = current_time( 'timestamp' );

			// Keep the running countdown stable while a listing is active.
			if ( ! $force_restart && $current_expiration > $now ) {
				return;
			}

			$duration = '';
			if ( isset( $_POST[ $this->custom_fields['duration'] ] ) ) {
				$duration = sanitize_text_field( wp_unslash( $_POST[ $this->custom_fields['duration'] ] ) );
			} elseif ( isset( $_POST['custom_fields'][ $this->custom_fields['duration'] ] ) ) {
				$duration = sanitize_text_field( wp_unslash( $_POST['custom_fields'][ $this->custom_fields['duration'] ] ) );
			} elseif ( isset( $_POST['duration'] ) ) {
				$duration = sanitize_text_field( wp_unslash( $_POST['duration'] ) );
			}

			if ( '' === $duration ) {
				return;
			}

			$date = $this->calculate_expiration_date( $post_id, $duration );
			if ( ! empty( $date ) ) {
				update_post_meta( $post_id, '_expiration_date', $date );
			}
		}

		/**
		 * Get formated expiration date.
		 *
		 * @param int|string $post_id
		 *
		 * @return string Date/Time formated string
		 **/
		function get_expiration_date( $post_id ) {
			$date = get_post_meta( $post_id, '_expiration_date', true );
			if ( ! empty( $date ) ) {
				return date_i18n( get_option( 'date_format' ), $date );
			} else {
				return __( 'Kein Ablaufdatum gesetzt.', $this->text_domain );
			}
		}

		/**
		 * Calculate the Unix time stamp of the modified posts
		 *
		 * @param int|string $post_id
		 * @param string $duration Valid value: "1 Week", "2 Weeks" ... etc
		 *
		 * @return int Unix timestamp
		 **/
		function calculate_expiration_date( $post_id, $duration ) {

			$duration = trim( str_replace( '-', '', $duration ) ); //Remove old place holder

			if ( empty( $duration ) ) {
				$expiration_date = get_post_meta( $post_id, '_expiration_date', true );

				return $expiration_date;
			}
			/* Process normal request */
			$duration_data = $this->parse_duration_interval_data( $duration );
			$publish_date = strtotime( get_the_date( 'Y-m-d H:i:s', $post_id ) );
			$date         = strtotime( $duration_data['modifier'], $publish_date );

			return $date;
		}

		/**
		 * Schedule expiration check for twice daily.
		 *
		 * @return void
		 **/
		function schedule_expiration_check() {
			if ( wp_get_schedule( 'check_expiration_dates' ) == false ) {
				wp_schedule_event( time(), 'hourly', 'check_expiration_dates' );
			} else {
				//we will reschedule this for quicker time
				if ( wp_get_schedule( 'check_expiration_dates' ) != 'hourly' ) {
					wp_reschedule_event( wp_next_scheduled( 'check_expiration_dates' ), 'hourly', 'check_expiration_dates' );
				}
			}
		}

		/**
		 * Check each post from the used post type and compare the expiration date/time
		 * with the current date/time. If the post is expired update it's status.
		 *
		 * @return void
		 **/
		function check_expiration_dates_callback() {
			global $wpdb;

			$last_post_id = 0;
			$batch_size   = 200;

			do {
				$post_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_type = %s
					   AND post_status = 'publish'
					   AND ID > %d
					 ORDER BY ID ASC
					 LIMIT %d",
					$this->post_type,
					$last_post_id,
					$batch_size
				) );

				if ( empty( $post_ids ) ) {
					break;
				}

				foreach ( $post_ids as $post_id ) {
					$post_id = (int) $post_id;
					$expiration_date = get_post_meta( $post_id, '_expiration_date', true );
					if ( empty( $expiration_date ) ) {
						$this->process_status( $post_id, 'draft' );
					} elseif ( $expiration_date < time() ) {
						$this->process_status( $post_id, 'private' );
					}
				}

				$last_post_id = (int) end( $post_ids );
			} while ( ! empty( $post_ids ) );
		}

		/**
		 * Validate a classifieds post id before featured meta operations.
		 *
		 * @param int $post_id The candidate post ID.
		 * @return bool True when post exists and matches classifieds post type.
		 */
		function is_valid_post( $post_id ) {
			$post_id = absint( $post_id );

			if ( $post_id <= 0 ) {
				return false;
			}

			$post = get_post( $post_id );

			if ( ! ( $post instanceof WP_Post ) ) {
				return false;
			}

			return $this->post_type === $post->post_type;
		}

		/**
		 * Activate featured status for a classified listing.
		 *
		 * @param int $post_id The classified post ID.
		 * @param int $duration_days Duration in days (0 = unlimited).
		 * @return bool True on success.
		 **/
		function set_featured( $post_id, $duration_days = 0 ) {
			if ( ! $this->is_valid_post( $post_id ) ) {
				return false;
			}

			update_post_meta( $post_id, '_cf_is_featured', 1 );

			if ( $duration_days > 0 ) {
				$featured_until = strtotime( "+{$duration_days} days" );
				update_post_meta( $post_id, '_cf_featured_until', $featured_until );
			} else {
				delete_post_meta( $post_id, '_cf_featured_until' );
			}

			return true;
		}

		/**
		 * Check if a classified listing is currently featured.
		 *
		 * @param int $post_id The classified post ID.
		 * @return bool True if featured and not expired.
		 **/
		function is_featured( $post_id ) {
			if ( ! $this->is_valid_post( $post_id ) ) {
				return false;
			}

			$is_featured = (bool) get_post_meta( $post_id, '_cf_is_featured', true );

			if ( ! $is_featured ) {
				return false;
			}

			$featured_until = get_post_meta( $post_id, '_cf_featured_until', true );

			if ( empty( $featured_until ) ) {
				return true; // Unlimited featured
			}

			if ( $featured_until < time() ) {
				// Featured has expired
				$this->unset_featured( $post_id );
				return false;
			}

			return true;
		}

		/**
		 * Get the timestamp until when a listing is featured.
		 *
		 * @param int $post_id The classified post ID.
		 * @return int|false Unix timestamp or false if not featured or unlimited.
		 **/
		function get_featured_until( $post_id ) {
			if ( ! $this->is_featured( $post_id ) ) {
				return false;
			}

			$featured_until = get_post_meta( $post_id, '_cf_featured_until', true );

			return ! empty( $featured_until ) ? (int) $featured_until : false;
		}

		/**
		 * Remove featured status from a listing.
		 *
		 * @param int $post_id The classified post ID.
		 * @return bool True on success.
		 **/
		function unset_featured( $post_id ) {
			if ( ! $this->is_valid_post( $post_id ) ) {
				return false;
			}

			delete_post_meta( $post_id, '_cf_is_featured' );
			delete_post_meta( $post_id, '_cf_featured_until' );

			return true;
		}

		/**
		 * Expire featured status for listings where featured_until has passed.
		 * Called via hourly cron hook.
		 *
		 * @return void
		 **/
		function maybe_expire_featured() {
			global $wpdb;

			$last_post_id = 0;
			$batch_size   = 200;

			do {
				$post_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm
					 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					 WHERE pm.meta_key = '_cf_featured_until'
					   AND pm.meta_value > 0
					   AND CAST(pm.meta_value AS UNSIGNED) < %d
					   AND p.post_type = %s
					   AND pm.post_id > %d
					 ORDER BY pm.post_id ASC
					 LIMIT %d",
					time(),
					$this->post_type,
					$last_post_id,
					$batch_size
				) );

				if ( empty( $post_ids ) ) {
					break;
				}

				foreach ( $post_ids as $post_id ) {
					$this->unset_featured( (int) $post_id );
				}

				$last_post_id = (int) end( $post_ids );
			} while ( ! empty( $post_ids ) );
		}

		/**
		 * Get featured listings, with optional ordering.
		 *
		 * @param array $args {
		 *     @type int $limit Number of posts to return. Default 6.
		 *     @type bool $exclude_premium Whether to exclude premium listings. Default false.
		 *     @type array $category Category IDs to filter by. Default empty (all).
		 *     @type array $exclude_ids Post IDs to exclude. Default empty.
		 * }
		 * @return WP_Query The query object with featured listings.
		 **/
		function get_featured_listings( $args = array() ) {
			$defaults = array(
				'limit'            => 6,
				'exclude_premium'  => false,
				'category'         => array(),
				'exclude_ids'      => array(),
			);

			$args = wp_parse_args( $args, $defaults );

			$meta_query = array(
				array(
					'key'   => '_cf_is_featured',
					'value' => 1,
					'compare' => '=',
				),
			);

			$tax_query = array();

			if ( ! empty( $args['category'] ) ) {
				$tax_query[] = array(
					'taxonomy' => $this->category_taxonomy,
					'field'    => 'term_id',
					'terms'    => (array) $args['category'],
				);
			}

			$query_args = array(
				'post_type'      => $this->post_type,
				'posts_per_page' => $args['limit'],
				'post_status'    => 'publish',
				'meta_query'     => $meta_query,
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			);

			if ( ! empty( $tax_query ) ) {
				$query_args['tax_query'] = $tax_query;
			}

			if ( ! empty( $args['exclude_ids'] ) ) {
				$query_args['post__not_in'] = (array) $args['exclude_ids'];
			}

			return new WP_Query( $query_args );
		}

		/**
		 * Save plugin options.
		 *
		 * @param  array $params The $_POST array
		 *
		 * @return die() if _wpnonce is not verified
		 **/
		function save_options( $params ) {
			if ( wp_verify_nonce( $params['_wpnonce'], 'verify' ) ) {
				/* Remove unwanted parameters */
				unset( $params['_wpnonce'], $params['_wp_http_referer'], $params['save'] );
				/* Update options by merging the old ones */
				$options = $this->get_options();
				$options = array_merge( $options, array( $params['key'] => $params ) );
				update_option( $this->options_name, $options );
			} else {
				die( __( 'Security check failed!', $this->text_domain ) );
			}
		}

		/**
		 * Get plugin options.
		 *
		 * @param  string|NULL $key The key for that plugin option.
		 *
		 * @return array $options Plugin options or empty array if no options are found
		 **/
		function get_options( $key = null ) {
			$options = get_option( $this->options_name );
			$options = is_array( $options ) ? $options : array();
			/* Check if specific plugin option is requested and return it */
			if ( isset( $key ) && array_key_exists( $key, $options ) ) {
				return $options[ $key ];
			} else {
				return $options;
			}
		}

		/**
		 * Process post status.
		 *
		 * @global object $wpdb
		 *
		 * @param  string $post_id
		 * @param  string $status
		 *
		 * @return void
		 **/
		function process_status( $post_id, $status ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'ID' => $post_id ), array( '%s' ), array( '%d' ) );
		}


		function comments_closed_text( $text ) {
			return '';
		}

		//filters the edit post button
		function delete_edit_post_link( $text ) {
			return '';
		}

		//filters the titles for our custom pages
		function page_title_output( $title, $id = false ) {
			global $wp_query, $post;

			//filter out nav titles
			if ( ! is_object( $post ) || ( $post->ID != $id ) || $post->post_type != 'directory_listing' ) {
				return $title;
			}

			//taxonomy pages
			$tax_key = ( empty( $wp_query->query_vars['taxonomy'] ) ) ? '' : $wp_query->query_vars['taxonomy'];

			$taxonomies = get_object_taxonomies( 'classifieds', 'objects' );
			if ( array_key_exists( $tax_key, $taxonomies ) ) {
				$term = get_term_by( 'slug', get_query_var( $tax_key ), $tax_key );

				return $taxonomies[ $tax_key ]->labels->singular_name . ': ' . $term->name;
			}

			//title for listings page
			if ( is_post_type_archive( 'classifieds' ) ) {
				return post_type_archive_title( '', false );
			}
			if ( '' != get_query_var( 'cf_author_name' ) || isset( $_REQUEST['cf_author'] ) && '' != $_REQUEST['cf_author'] ) {
				$title = sprintf( __( '%s classifieds', CF_TEXT_DOMAIN ), get_query_var( 'cf_author_name' ) );
			}

			return $title;
		}

		/**
		 * Format date.
		 *
		 * @param int $date unix timestamp
		 *
		 * @return string formatted date
		 **/
		function format_date( $date ) {
			return date_i18n( get_option( 'date_format' ), $date );
		}

		/**
		 * Validate fields
		 *
		 * @param array $params $_POST data
		 * @param array|NULL $file $_FILES data
		 *
		 * @return void
		 **/
		function validate_fields( $params, $file = null ) {
			if ( empty( $params['title'] ) || empty( $params['description'] ) || empty( $params['terms'] ) || empty( $params['status'] ) ) {
				$this->form_valid = false;
			}

			$options = $this->get_options( 'general' );

			//do image field not required
			if ( ! isset( $options['field_image_req'] ) || '1' != $options['field_image_req'] ) {
				if ( $file['image']['error'] !== 0 ) {
					$this->form_valid = false;
				}
			}

		}

		/**
		 * Filter the template path to single{}.php templates.
		 * Load from theme classifieds primary if it doesn't exist load from plugin dir.
		 *
		 * Learn more: http://codex.wordpress.org/Template_Hierarchy
		 * Learn more: http://codex.wordpress.org/Plugin_API/Filter_Reference#Template_Filters
		 *
		 * @global <type> $post Post object
		 *
		 * @param string Templatepath to filter
		 *
		 * @return string Templatepath
		 **/
		function get_single_template( $template ) {
			global $post;
			$tpldir   = get_template_directory();
			$template = ( file_exists( "{$tpldir}/single-{$template}.php" ) )
				? "{$tpldir}/single-{$template}.php"
				: ( file_exists( "{$this->plugin_dir}ui-front/general/single-{$template}.php" ) ? "{$this->plugin_dir}ui-front/general/single-{$template}.php" : $template );

			return $template;
		}

		function template_file( $template ) {
			$tpldir   = get_template_directory();
			$template = file_exists( "{$tpldir}/page-{$template}.php" )
				? "{$tpldir}/page-{$template}.php"
				: ( file_exists( "{$this->plugin_dir}ui-front/general/page-{$template}.php" ) 
					? "{$this->plugin_dir}ui-front/general/page-{$template}.php"
					: $template );
		
			return $template;
		}
		
		/**
		 * Filter the template path to page{}.php templates.
		 * Load from theme directory primary if it doesn't exist load from plugin dir.
		 *
		 * Learn more: http://codex.wordpress.org/Template_Hierarchy
		 * Learn more: http://codex.wordpress.org/Plugin_API/Filter_Reference#Template_Filters
		 *
		 * @global <type> $post Post object
		 *
		 * @param string Templatepath to filter
		 *
		 * @return string Templatepath
		 **/
		function get_page_template( $template ) {
			global $post, $paged;
			/*
		//get page number for pagination
		if ( get_query_var('paged') ) {
		$paged = get_query_var('paged'); //Usually paged
		} elseif ( get_query_var('page') ) { //But if front page it's page
		$paged = get_query_var('page');
		} else {
		$paged = 1;
		}

		$this->cf_page = $paged;
		*/

			//Translate back to standard names.
			$name = $post->post_name;
			if ( $post->ID == $this->classifieds_page_id ) {
				$name = $this->classifieds_page_name;
			}
			if ( $post->ID == $this->my_classifieds_page_id ) {
				$name = $this->my_classifieds_page_name;
			}
			if ( $post->ID == $this->checkout_page_id ) {
				$name = $this->checkout_page_name;
			}

			if ( ! file_exists( get_template_directory() . "/page-{$name}.php" ) && file_exists( "{$this->plugin_dir}ui-front/general/page-{$name}.php" ) ) {
				return "{$this->plugin_dir}ui-front/general/page-{$name}.php";
			} else {
				return $template;
			}
		}

		/**
		 * Filter the template path to taxonomy{}.php templates.
		 * Load from theme directory primary if it doesn't exist load from plugin dir.
		 *
		 * Learn more: http://codex.wordpress.org/Template_Hierarchy
		 * Learn more: http://codex.wordpress.org/Plugin_API/Filter_Reference#Template_Filters
		 *
		 * @global <type> $post Post object
		 *
		 * @param string Templatepath to filter
		 *
		 * @return string Templatepath
		 **/
		function get_taxonomy_template( $template ) {

			$taxonomy = get_query_var( 'taxonomy' );
			$term     = get_query_var( 'term' );

			if ( ! in_array( $taxonomy, array( 'kleinenanzeigen-cat', 'kleinanzeigen-region' ), true ) ) {
				return $template;
			}


			/* Check whether the files doesn't exist in the active theme directory,
		* also check for file to load in our general template directory */
			if ( ! file_exists( get_template_directory() . "/taxonomy-{$taxonomy}-{$term}.php" )
			     && file_exists( "{$this->plugin_dir}ui-front/general/taxonomy-{$taxonomy}-{$term}.php" )
			) {
				return "{$this->plugin_dir}ui-front/general/taxonomy-{$taxonomy}-{$term}.php";
			} elseif ( ! file_exists( get_template_directory() . "/taxonomy-{$taxonomy}.php" )
			           && file_exists( "{$this->plugin_dir}ui-front/general/taxonomy-{$taxonomy}.php" )
			) {
				return "{$this->plugin_dir}ui-front/general/taxonomy-{$taxonomy}.php";
			} elseif ( ! file_exists( get_template_directory() . "/taxonomy.php" )
			           && file_exists( "{$this->plugin_dir}ui-front/general/taxonomy.php" )
			) {
				return "{$this->plugin_dir}ui-front/general/taxonomy.php";
			} else {
				return $template;
			}
		}

		/**
		 * Renders a section of user display code.  The code is first checked for in the current theme display directory
		 * before defaulting to the plugin
		 *
		 * @param  string $name Name of the admin file(without extension)
		 * @param  string $vars Array of variable name=>value that is available to the display code(optional)
		 *
		 * @return void
		 **/
		function render_front( $name, $vars = array() ) {
			/* Construct extra arguments */
			foreach ( $vars as $key => $val ) {
				$$key = $val;
			}
			/* Include templates */

			/** allow custom template */
			$result = $this->custom_classifieds_template( $name );
			if ( file_exists( $result ) ) {
				include( $result );

				return;
			}
			/*		
		$result = get_template_directory() . "/{$name}.php";
		if ( file_exists( $result ) ){
			include($result);
			return;
		}

		$result = "{$this->plugin_dir}ui-front/buddypress/members/single/classifieds/{$name}.php";
		if ( file_exists( $result ) && $this->bp_active ){
			include($result);
			return;
		}

		$result = "{$this->plugin_dir}ui-front/general/{$name}.php";

		if ( file_exists( $result ) ) {
			include($result);
			return;
		}
*/
			echo "<p>Rendering of template $result {$name}.php failed</p>";
		}

		/**
		 * Redirect using JavaScript. Useful if headers are already sent.
		 *
		 * @param string $url The URL to which the function should redirect
		 **/
		function js_redirect( $url, $silent = false ) {
			if ( ! $silent ):
				?>
				<p><?php _e( 'Du wirst weitergeleitet. Einen Moment bitte.', $this->text_domain ); ?></p>
				<img src="<?php echo $this->plugin_url . '/ui-front/general/images/loader.gif'; ?>"
				     alt="<?php _e( 'Du wirst weitergeleitet. Einen Moment bitte.', $this->text_domain ); ?>"/>
			<?php endif; ?>
			<script type="text/javascript">//<![CDATA[
				window.location = '<?php echo $url; ?>';	//]]>
			</script>
			<?php
		}


		/**
		 * return fancy pagination links.
		 * @uses $wp_query
		 *
		 */
		function pagination( $show = true ) {
			global $wp_query;

			if ( ! $show ) {
				return '';
			}

			ob_start();

			include( $this->plugin_dir . 'ui-front/general/pagination.php' );

			$result = apply_filters( 'cf_pagination', ob_get_contents() );
			ob_end_clean();

			return $result;
		}

		/**
		 *  on_admin_post_thumbnail_html adds a hidden required field if the feature image is empty
		 *
		 */
		function on_admin_post_thumbnail_html( $content = '' ) {
			if ( get_post_type() != 'classifieds' ) {
				return $content;
			}

			$options  = $this->get_options( 'general' );
			$required = empty( $options['field_image_req'] );

			if ( ! $required || ( stripos( $content, 'remove-post-thumbnail' ) !== false ) ) {
				return $content;
			}

			$content = str_replace( '<a', '<input type="text" style="visibility: hidden;width:0;" value="" class="required" /><a', $content );

			return $content;
		}

		/**
		 * Shortcode definitions
		 */

		function classifieds_categories_sc( $atts, $content = null ) {
			return $this->shortcode_service->classifieds_categories_sc( $atts, $content );
		}

		function classifieds_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->classifieds_btn_sc( $atts, $content );
		}

		function add_classified_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->add_classified_btn_sc( $atts, $content );
		}

		function edit_classified_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->edit_classified_btn_sc( $atts, $content );
		}

		function checkout_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->checkout_btn_sc( $atts, $content );
		}

		function my_credits_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->my_credits_btn_sc( $atts, $content );
		}

		function my_classifieds_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->my_classifieds_btn_sc( $atts, $content );
		}

		function profile_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->profile_btn_sc( $atts, $content );
		}

		function signin_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->signin_btn_sc( $atts, $content );
		}

		function logout_btn_sc( $atts, $content = null ) {
			return $this->shortcode_service->logout_btn_sc( $atts, $content );
		}

		function custom_fields_sc( $atts, $content = null ) {
			return $this->shortcode_service->custom_fields_sc( $atts, $content );
		}

		//close comments
		function close_comments( $open, $id = 0 ) {
			return false;
		}

		/**
	 * Custom Template path - Simplified for v2.0.0 (BuddyPress removed)
	 *
	 * @param string $template The template to find.
	 *
	 * @return string Template path.
	 **/
	function custom_classifieds_template( $template ) {
		return $this->template_content_service->custom_classifieds_template( $template );
	}
	/**
	 * Classifieds content.
		 *
		 * @return void
		 **/
		function classifieds_content( $content = null ) {
			return $this->template_content_service->classifieds_content( $content );
		}

		/**
		 * Update Classifieds.
		 *
		 * @return void
		 **/
		function update_classified_content( $content = null ) {
			return $this->template_content_service->update_classified_content( $content );
		}

		/**
		 * My Classifieds.
		 *
		 * @return void
		 **/
		function my_classifieds_content( $content = null ) {
			return $this->template_content_service->my_classifieds_content( $content );
		}

		/**
		 * My Classifieds.
		 *
		 * @return void
		 **/
		function checkout_content( $content = null ) {
			return $this->template_content_service->checkout_content( $content );
		}

		/**
		 * Signin.
		 *
		 * @return void
		 **/
		function signin_content( $content = null ) {
			return $this->template_content_service->signin_content( $content );
		}

		/**
		 * My Classifieds Credits.
		 *
		 * @return void
		 **/
		function my_credits_content( $content = null ) {
			return $this->template_content_service->my_credits_content( $content );
		}

		/**
		 * Single Classifieds.
		 *
		 * @return void
		 **/
		function single_content( $content = null ) {
			return $this->template_content_service->single_content( $content );
		}

		function no_title( $content = '' ) {
			if ( ! in_the_loop() ) {
				return $content;
			}

			return '';
		}

		function update_classified( $params ) {
			/* Construct args for the new post */
			$args = array(
				/* If empty ID insert Listing instead of updating it */
				'ID'           => ( isset( $params['classified_data']['ID'] ) ) ? $params['classified_data']['ID'] : '',
				'post_title'   => wp_strip_all_tags( $params['classified_data']['post_title'] ),
				'post_content' => $params['classified_data']['post_content'],
				'post_excerpt' => ( isset( $params['classified_data']['post_excerpt'] ) ) ? $params['classified_data']['post_excerpt'] : '',
				'post_status'  => $params['classified_data']['post_status'],
				//'post_author'    => get_current_user_id(),
				'post_type'    => 'classifieds',
				'ping_status'  => 'closed',
				//'comment_status' => 'closed'
			);

			/* Insert page and get the ID */
			if ( empty( $args['ID'] ) ) {
				$post_id = wp_insert_post( $args );
			} else {
				$post_id = wp_update_post( $args );
			}

			if ( ! empty( $post_id ) ) {

				//Save custom tags
				if ( is_array( $params['tag_input'] ) ) {
					foreach ( $params['tag_input'] as $key => $tags ) {
						wp_set_post_terms( $post_id, $params['tag_input'][ $key ], $key );
					}
				}

				//Save categories
				if ( is_array( $params['post_category'] ) ) {
					wp_set_post_terms( $post_id, $params['post_category'], 'category' );
				}

				//Save custom terms
				if ( is_array( $params['tax_input'] ) ) {
					foreach ( $params['tax_input'] as $key => $term_ids ) {
						if ( is_array( $params['tax_input'][ $key ] ) ) {
							wp_set_post_terms( $post_id, $params['tax_input'][ $key ], $key );
						}
					}
				}

// Save custom fields using native meta handling
			$this->save_post_custom_fields_native( $post_id, $_POST );

				return $post_id;
			}
		}

		/**
		 * Restrict Media library to current user's files
		 *
		 */

		function on_pre_get_posts( $wp_query_obj ) {
			global $current_user;

			if ( $current_user->ID == 0 ) {
				return;
			}

			if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'query-attachments'
			     && ! current_user_can( 'administrator' )
			     && ! current_user_can( 'edit_others_classifieds' )
			) {
				$wp_query_obj->set( 'author', $current_user->ID );
			}
		}

		function imagettftext_cr( &$im, $size, $angle, $x, $y, $color, $fontfile, $text ) {
			// retrieve boundingbox
			$bbox = imagettfbbox( $size, $angle, $fontfile, $text );
			// calculate deviation
			$dx = ( $bbox[2] - $bbox[0] ) / 2.0 - ( $bbox[2] - $bbox[4] ) / 2.0;         // deviation left-right
			$dy = ( $bbox[3] - $bbox[1] ) / 2.0 + ( $bbox[7] - $bbox[1] ) / 2.0;        // deviation top-bottom
			// new pivotpoint
			$px = $x - $dx;
			$py = $y - $dy;

			return imagettftext( $im, $size, $angle, $px, $y, $color, $fontfile, $text );
		}

		function on_captcha() {
			//exit;


			$alphanum = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
			$rand     = substr( str_shuffle( $alphanum ), 0, 5 );

			$image      = imagecreate( 120, 36 );
			$black      = imagecolorallocate( $image, 0, 0, 0 );
			$grey_shade = imagecolorallocate( $image, 128, 128, 128 );
			$white      = imagecolorallocate( $image, 255, 255, 255 );

			$otherFont = 'fonts/StardosStencil-Regular.ttf';
			$font      = $this->plugin_dir . 'ui-front/fonts/StardosStencil-Bold.ttf';

			//imagestring( $image, 5, 28, 4, $rand, $white );
			//BG text for Name
			$i = 1;
			while ( $i < 10 ) {
				$this->imagettftext_cr( $image, rand( 2, 20 ), rand( - 50, 50 ), rand( 10, 120 ), rand( 0, 40 ), $grey_shade, $font, $rand );
				$i ++;
			}

			$this->imagettftext_cr( $image, 14, 0, 60, 26, $white, $font, $rand );

			//Use transient
			set_transient( CF_CAPTCHA . $_SERVER['REMOTE_ADDR'], md5( $rand ), 600 );

			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
			header( "Cache-Control: no-store, no-cache, must-revalidate" );
			header( "Cache-Control: post-check=0, pre-check=0", false );
			header( "Pragma: no-cache" );
			header( "Content-type: image/png" );

			imagepng( $image );
			imagedestroy( $image );
			exit;
		}

} // end class Classifieds_Core

// Set flag on activation to trigger initial data
add_action( 'activated_plugin', 'cf_flag_activation', 1 );
function cf_flag_activation( $plugin = '' ) {
	//Flag we're activating
	if ( $plugin == 'classifieds/loader.php' ) {
		add_site_option( 'cf_activate', true );
	}
}

// Entscheide welche Version geladen wird
add_action( 'plugins_loaded', 'cf_on_plugins_loaded' );
function cf_on_plugins_loaded() {

	// Note: BuddyPress integration removed in v2.0.0
	// Replaced with ps-community integration in class-native-community.php

	if ( is_admin() ) {
		include_once CF_PLUGIN_DIR . 'core/admin.php';
	} else {
		include_once CF_PLUGIN_DIR . 'core/main.php';
	}

	// Sprachdateien laden + ggf. Erststart-Daten initialisieren
	global $Classifieds_Core;
	if ( is_object( $Classifieds_Core ) && method_exists( $Classifieds_Core, 'on_plugins_loaded' ) ) {
		$Classifieds_Core->on_plugins_loaded();
	}
}

endif;

