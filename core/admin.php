<?php

/**
* Classifieds Core Admin Class
*/
if ( !class_exists('Classifieds_Core_Admin') ):
class Classifieds_Core_Admin extends Classifieds_Core {

	/** @var string $hook The hook for the current admin page */
	var $hook;
	/** @var string $menu_slug The main menu slug */
	var $menu_slug        = 'classifieds';
	/** @var string $sub_menu_slug Submenu slug @todo better way of handling this */
	var $sub_menu_slug    = 'classifieds_credits';

	/** @var string $message Return message after save settings operation */
	var $message  = '';

	var $tutorial_id = 0;

	var $tutorial_script = '';

	function __construct(){

		parent::__construct();

	}

	/**
	* Initiate the plugin.
	*
	* @return void
	**/
	function init() {

		parent::init();

		/* Init if admin only */
		if ( is_admin() ) {
			/* Initiate admin menus and admin head */
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_action( 'admin_init', array( &$this, 'admin_head' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'block_external_cdn_assets' ), 999 );
			add_action( 'save_post',  array( &$this, 'save_expiration_date' ), 1, 1 );
			add_action( 'restrict_manage_posts', array($this,'on_restrict_manage_posts') );

			add_action( 'wp_ajax_cf_get_caps', array( &$this, 'ajax_get_caps' ) );
			add_action( 'wp_ajax_cf_save', array( &$this, 'ajax_save' ) );

			add_action('admin_init', array($this, 'tutorial_script') );
			add_action('admin_print_footer_scripts', array($this, 'print_tutorial_script') );

            /**
             * @since 2.3.6.7
             * @author DerN3rd
             */
            add_filter('user_has_cap', array(&$this,'determine_backend_cap'), 10, 3);
		}
	}

	function print_tutorial_script(){
		echo $this->tutorial_script;
	}

	function tutorial_script(){

		if(file_exists($this->plugin_dir . 'tutorial/classifieds-tutorial.js') ){
			$this->tutorial_script = file_get_contents($this->plugin_dir . 'tutorial/classifieds-tutorial.js');

			preg_match('/data-kera-tutorial="(.+)">/', $this->tutorial_script, $matches);

			$this->tutorial_id = $matches[1];

			$this->tutorial_script = strstr($this->tutorial_script, '<script');
		}
	}

	function launch_tutorial(){
		?>
		<h2>Classifieds Tutorial</h2>
		<a href="#" data-kera-tutorial="<?php echo $this->tutorial_id; ?>">Launch Tutorial</a>
		<?php
	}

	/**
	* Add plugin main menu
	*
	* @return void
	**/
	function admin_menu() {

		if ( ! current_user_can('unfiltered_html') ) {
			remove_submenu_page('edit.php?post_type=classifieds', 'post-new.php?post_type=classifieds' );
			add_submenu_page(
			'edit.php?post_type=classifieds',
			__( 'Neue hinzufügen', $this->text_domain ),
			__( 'Neue hinzufügen', $this->text_domain ),
			'create_classifieds',
			'classifieds_add',
			array( &$this, 'redirect_add' ) );
		}

		add_submenu_page(
		'edit.php?post_type=classifieds',
		__( 'Uebersicht', $this->text_domain ),
		__( 'Uebersicht', $this->text_domain ),
		'read',
		$this->menu_slug,
		array( &$this, 'handle_admin_requests' ) );

		$settings_page = add_submenu_page(
		'edit.php?post_type=classifieds',
		__( 'Kleinanzeigen-Einstellungen', $this->text_domain ),
		__( 'Einstellungen', $this->text_domain ),
		'create_users', //create_users so on multisite you can turn on and off Settings with the Admin add users switch
		'classifieds_settings',
		array( &$this, 'handle_admin_requests' ) );

		add_action( 'admin_print_styles-' .  $settings_page, array( &$this, 'enqueue_scripts' ) );

		if ( $this->use_credits && current_user_can( 'manage_options' ) ) {
			$settings_page = add_submenu_page(
			'edit.php?post_type=classifieds',
			__( 'Kleinanzeigen-Credits', $this->text_domain ),
			__( 'Credits', $this->text_domain ),
			'read',
			'classifieds_credits',
			array( &$this, 'handle_credits_requests' ) );

			add_action( 'admin_print_styles-' .  $settings_page, array( &$this, 'enqueue_scripts' ) );
		}

		if(file_exists($this->plugin_dir . 'tutorial/classifieds-tutorial.js') ){
			add_submenu_page( 'edit.php?post_type=classifieds', __( 'Tutorial', $this->text_domain ), __( 'Tutorial', $this->text_domain ), 'read', 'classifieds_tutorial', array( &$this, 'launch_tutorial' ) );
		}
	}


	function redirect_add(){
		echo '<script>window.location = "' . get_permalink($this->add_classified_page_id) . '";</script>';
	}


	function enqueue_scripts(){
		wp_enqueue_style( 'cf-admin-styles', $this->plugin_url . 'ui-admin/css/ui-styles.css');
		wp_enqueue_script( 'cf-admin-scripts', $this->plugin_url . 'ui-admin/js/ui-scripts.js', array( 'jquery' ) );
	}

	function block_external_cdn_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $this->is_classifieds_admin_screen( $screen ) ) {
			return;
		}

		$this->dequeue_external_cdn_handles( wp_styles(), 'style' );
		$this->dequeue_external_cdn_handles( wp_scripts(), 'script' );
	}

	function is_classifieds_admin_screen( $screen ) {
		if ( empty( $screen ) ) {
			return false;
		}

		if ( ! empty( $screen->post_type ) && 'classifieds' === $screen->post_type ) {
			return true;
		}

		$screen_id = ! empty( $screen->id ) ? $screen->id : '';
		$screen_base = ! empty( $screen->base ) ? $screen->base : '';

		return in_array( $screen_id, array(
			'classifieds_page_classifieds',
			'classifieds_page_classifieds_settings',
			'classifieds_page_classifieds_credits',
			'classifieds_page_classifieds_tutorial',
			'edit-classifieds',
			'classifieds',
		), true ) || in_array( $screen_base, array( 'post', 'edit' ), true );
	}

	function dequeue_external_cdn_handles( $dependency_manager, $type ) {
		if ( empty( $dependency_manager ) || empty( $dependency_manager->registered ) ) {
			return;
		}

		$cdn_hosts = array(
			'cdnjs.cloudflare.com',
			'cdn.jsdelivr.net',
			'unpkg.com',
			'ajax.googleapis.com',
			'fonts.googleapis.com',
			'fonts.gstatic.com',
			'maxcdn.bootstrapcdn.com',
			'use.fontawesome.com',
		);

		foreach ( $dependency_manager->registered as $handle => $dependency ) {
			if ( empty( $dependency->src ) ) {
				continue;
			}

			$src = $dependency->src;
			if ( 0 === strpos( $src, '//' ) ) {
				$src = ( is_ssl() ? 'https:' : 'http:' ) . $src;
			}

			$host = wp_parse_url( $src, PHP_URL_HOST );
			if ( empty( $host ) || ! in_array( $host, $cdn_hosts, true ) ) {
				continue;
			}

			if ( 'style' === $type ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			} else {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	/**
	* Renders an admin section of display code.
	*
	* @param  string $name Name of the admin file(without extension)
	* @param  string $vars Array of variable name=>value that is available to the display code(optional)
	* @return void
	**/
	function render_admin( $name, $vars = array() ) {
		foreach ( $vars as $key => $val )
		$$key = $val;
		if ( file_exists( "{$this->plugin_dir}ui-admin/{$name}.php" ) )
		include "{$this->plugin_dir}ui-admin/{$name}.php";
		else
		echo "<p>Das Rendern der Admin-Vorlage {$this->plugin_dir}ui-admin/{$name}.php ist fehlgeschlagen</p>";
	}

	/**
	* Flow of a typical admin page request.
	*
	* @return void
	**/
	function handle_admin_requests() {
		$valid_tabs = array(
		'general',
		'frontend',
		'capabilities',
		'payments',
		'affiliate',
		'shortcodes',
		);

		$params = stripslashes_deep($_POST);

		$page = empty( $_GET['page'] ) ? '' : sanitize_key( wp_unslash( $_GET['page'] ) );

		if ( $page == $this->menu_slug ) {
			if ( isset( $params['confirm'] ) ) {
				/* Change post status */
				if ( $params['action'] == 'end' )
				$this->process_status( $params['post_id'], 'private' );
				/* Change post status */
				if ( $params['action'] == 'publish' ) {
					$this->save_expiration_date( $params['post_id'] );
					$this->process_status( $params['post_id'], 'publish' );
				}
				/* Delete post */
				if ( $params['action'] == 'delete' )
				wp_delete_post( $params['post_id'] );
				/* Render admin template */
				$this->render_admin( 'dashboard' );
			} else {
				/* Render admin template */
				$this->render_admin( 'dashboard' );
			}
		}
		elseif ( $page == 'classifieds_settings' ) {
			$tab = empty( $_GET['tab'] ) ? 'general' : sanitize_key( wp_unslash( $_GET['tab'] ) ); //default tab
			if ( 'payment-types' === $tab ) {
				$tab = 'payments';
			}
			if ( in_array( $tab, $valid_tabs)) {
				/* Save options */
				if ( isset( $params['add_role'] ) ) {
					check_admin_referer('verify');
					$name = sanitize_file_name($params['new_role']);
					$slug = sanitize_key(preg_replace('/\W+/','_',$name) );
					$result = add_role($slug, $name, array('read' => true) );
					if (empty($result) ) $this->message = __('ROLLE EXISTIERT BEREITS' , $this->text_domain);
					else $this->message = sprintf(__('Neue Rolle "%s" hinzugefügt' , $this->text_domain), $name);
				}
				if ( isset( $params['remove_role'] ) ) {
					check_admin_referer('verify');
					$name = $params['delete_role'];
					remove_role($name);
					$this->message = sprintf(__('Rolle "%s" entfernt' , $this->text_domain), $name);
				}
				if ( isset( $params['save'] ) ) {
					check_admin_referer('verify');
					if ( 'general' === $tab && isset( $params['trust_block_content'] ) ) {
						$params['trust_block_content'] = wp_kses_post( $params['trust_block_content'] );
					}
					if ( 'payments' === $tab ) {
						$params['use_free'] = empty( $params['use_free'] ) ? 0 : 1;
						if ( isset( $params['tos_txt'] ) ) {
							$params['tos_txt'] = wp_kses_post( $params['tos_txt'] );
						}
						$memberships_available = class_exists( 'MS_Model_Membership' ) && class_exists( 'MS_Model_Member' );
						$params['enable_recurring'] = ( $memberships_available && ! empty( $params['enable_recurring'] ) ) ? 1 : 0;
						$required_membership_ids = isset( $params['required_membership_ids'] ) ? (array) $params['required_membership_ids'] : array();
						$required_membership_ids = array_values( array_unique( array_filter( array_map( 'absint', $required_membership_ids ) ) ) );
						$params['required_membership_ids'] = $memberships_available ? $required_membership_ids : array();
						$params['enable_one_time'] = empty( $params['enable_one_time'] ) ? 0 : 1;
						$params['enable_credits'] = empty( $params['enable_credits'] ) ? 0 : 1;
						$params['enable_marketpress_bridge'] = class_exists( 'MP_Product' ) ? 1 : 0;
						$params['mp_one_time_product_id'] = isset( $params['mp_one_time_product_id'] ) ? absint( $params['mp_one_time_product_id'] ) : 0;
						$params['mp_credit_meta_key'] = isset( $params['mp_credit_meta_key'] ) ? sanitize_key( $params['mp_credit_meta_key'] ) : 'cf_credit_amount';
						$params['mp_credit_packages'] = $this->sanitize_credit_packages( isset( $params['mp_credit_packages'] ) ? $params['mp_credit_packages'] : array() );
						$params['dashboard_show_credit_status'] = empty( $params['dashboard_show_credit_status'] ) ? 0 : 1;
						$params['dashboard_credit_warning_threshold'] = isset( $params['dashboard_credit_warning_threshold'] ) ? absint( $params['dashboard_credit_warning_threshold'] ) : 5;
						$params['featured_credit_package_id'] = isset( $params['featured_credit_package_id'] ) ? absint( $params['featured_credit_package_id'] ) : 0;
						$params['expired_restart_mode'] = isset( $params['expired_restart_mode'] ) ? sanitize_key( $params['expired_restart_mode'] ) : 'credits';
						if ( ! in_array( $params['expired_restart_mode'], array( 'none', 'free', 'credits' ), true ) ) {
							$params['expired_restart_mode'] = 'credits';
						}
						// Featured settings
						$params['enable_featured'] = empty( $params['enable_featured'] ) ? 0 : 1;
						$params['featured_cost_type'] = isset( $params['featured_cost_type'] ) ? sanitize_key( $params['featured_cost_type'] ) : 'credits';
						if ( ! in_array( $params['featured_cost_type'], array( 'credits', 'money' ), true ) ) {
							$params['featured_cost_type'] = 'credits';
						}
						$params['featured_credit_cost'] = isset( $params['featured_credit_cost'] ) ? absint( $params['featured_credit_cost'] ) : 50;
						$params['featured_money_cost'] = isset( $params['featured_money_cost'] ) ? sanitize_text_field( $params['featured_money_cost'] ) : '2.99';
						$params['featured_duration_days'] = isset( $params['featured_duration_days'] ) ? absint( $params['featured_duration_days'] ) : 7;
						$existing_payments = $this->get_options( 'payments' );
						$existing_payments = is_array( $existing_payments ) ? $existing_payments : array();
						$params['featured_money_product_id'] = isset( $params['featured_money_product_id'] )
							? absint( $params['featured_money_product_id'] )
							: ( ! empty( $existing_payments['featured_money_product_id'] ) ? absint( $existing_payments['featured_money_product_id'] ) : 0 );
						$params['featured_expire_with_classified'] = empty( $params['featured_expire_with_classified'] ) ? 0 : 1;
						$params = $this->sync_marketpress_checkout_products( $params );
						$this->sync_legacy_payment_types( $params );
					}
					if ( 'affiliate' === $tab ) {
						$params['cf_credit_commissions'] = $this->sanitize_affiliate_credit_commissions(
							isset( $params['cf_credit_commission_mode'] ) ? $params['cf_credit_commission_mode'] : array(),
							isset( $params['cf_credit_commission_value'] ) ? $params['cf_credit_commission_value'] : array()
						);
						$params['cf_credit_future_commissions'] = $this->sanitize_affiliate_credit_commissions(
							isset( $params['cf_credit_future_commission_mode'] ) ? $params['cf_credit_future_commission_mode'] : array(),
							isset( $params['cf_credit_future_commission_value'] ) ? $params['cf_credit_future_commission_value'] : array()
						);
						$params['cf_one_time_commission'] = $this->sanitize_affiliate_commission_rule(
							isset( $params['cf_one_time_commission_mode'] ) ? $params['cf_one_time_commission_mode'] : 'fixed',
							isset( $params['cf_one_time_commission_value'] ) ? $params['cf_one_time_commission_value'] : ''
						);
						$params['cf_credit_pay_future'] = empty( $params['cf_credit_pay_future'] ) ? 0 : 1;
					}
					if ( 'frontend' === $tab ) {
						if ( isset( $params['archive_intro'] ) ) {
							$params['archive_intro'] = wp_kses_post( $params['archive_intro'] );
						}
						if ( isset( $params['user_intro'] ) ) {
							$params['user_intro'] = wp_kses_post( $params['user_intro'] );
						}
						if ( isset( $params['trust_block_content'] ) ) {
							$params['trust_block_content'] = wp_kses_post( $params['trust_block_content'] );
						}
						$params['archive_auto_restore'] = empty( $params['archive_auto_restore'] ) ? 0 : 1;
						$params['archive_show_filter_tools'] = empty( $params['archive_show_filter_tools'] ) ? 0 : 1;
						$params['archive_show_quickview'] = empty( $params['archive_show_quickview'] ) ? 0 : 1;
						$params['archive_show_favorites'] = empty( $params['archive_show_favorites'] ) ? 0 : 1;
						$params['archive_show_contact_cta'] = empty( $params['archive_show_contact_cta'] ) ? 0 : 1;
						$params['archive_show_reserved_badge'] = empty( $params['archive_show_reserved_badge'] ) ? 0 : 1;
						$params['single_show_gallery'] = empty( $params['single_show_gallery'] ) ? 0 : 1;
						$params['single_show_trust_block'] = empty( $params['single_show_trust_block'] ) ? 0 : 1;
						$params['single_show_seller_card'] = empty( $params['single_show_seller_card'] ) ? 0 : 1;
						$params['single_show_sticky_actions'] = empty( $params['single_show_sticky_actions'] ) ? 0 : 1;
						$params['single_show_reserved_badge'] = empty( $params['single_show_reserved_badge'] ) ? 0 : 1;
						$params['user_show_favorites_tab'] = empty( $params['user_show_favorites_tab'] ) ? 0 : 1;
						$params['user_allow_reserve_toggle'] = empty( $params['user_allow_reserve_toggle'] ) ? 0 : 1;
						$raw_single_accent = isset( $params['single_accent_color'] ) ? $params['single_accent_color'] : '';
						$params['single_accent_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_single_accent ) ? $raw_single_accent : '#0f6cbd';
						$gallery_layout_allowed = array( 'image_only', 'slider', 'mosaic' );
						$hero_media_mode_allowed = array( 'featured_only', 'slider', 'mosaic' );
						$extra_gallery_position_allowed = array( 'above_description', 'below_description' );
						$extra_gallery_display_allowed = array( 'grid', 'slider' );
						$raw_gallery_layout_b2c = isset( $params['single_gallery_layout_b2c'] ) ? sanitize_key( (string) $params['single_gallery_layout_b2c'] ) : 'image_only';
						$raw_gallery_layout_premium = isset( $params['single_gallery_layout_premium'] ) ? sanitize_key( (string) $params['single_gallery_layout_premium'] ) : 'slider';
						$raw_gallery_layout_community = isset( $params['single_gallery_layout_community'] ) ? sanitize_key( (string) $params['single_gallery_layout_community'] ) : 'mosaic';
						$params['single_gallery_layout_b2c'] = in_array( $raw_gallery_layout_b2c, $gallery_layout_allowed, true ) ? $raw_gallery_layout_b2c : 'image_only';
						$params['single_gallery_layout_premium'] = in_array( $raw_gallery_layout_premium, $gallery_layout_allowed, true ) ? $raw_gallery_layout_premium : 'slider';
						$params['single_gallery_layout_community'] = in_array( $raw_gallery_layout_community, $gallery_layout_allowed, true ) ? $raw_gallery_layout_community : 'mosaic';

						$raw_single_hero_media_mode_b2c = isset( $params['single_hero_media_mode_b2c'] ) ? sanitize_key( (string) $params['single_hero_media_mode_b2c'] ) : 'featured_only';
						$raw_single_hero_media_mode_premium = isset( $params['single_hero_media_mode_premium'] ) ? sanitize_key( (string) $params['single_hero_media_mode_premium'] ) : 'slider';
						$raw_single_hero_media_mode_community = isset( $params['single_hero_media_mode_community'] ) ? sanitize_key( (string) $params['single_hero_media_mode_community'] ) : 'mosaic';
						$params['single_hero_media_mode_b2c'] = in_array( $raw_single_hero_media_mode_b2c, $hero_media_mode_allowed, true ) ? $raw_single_hero_media_mode_b2c : 'featured_only';
						$params['single_hero_media_mode_premium'] = in_array( $raw_single_hero_media_mode_premium, $hero_media_mode_allowed, true ) ? $raw_single_hero_media_mode_premium : 'slider';
						$params['single_hero_media_mode_community'] = in_array( $raw_single_hero_media_mode_community, $hero_media_mode_allowed, true ) ? $raw_single_hero_media_mode_community : 'mosaic';

						$raw_single_extra_gallery_position_b2c = isset( $params['single_extra_gallery_position_b2c'] ) ? sanitize_key( (string) $params['single_extra_gallery_position_b2c'] ) : 'below_description';
						$raw_single_extra_gallery_position_premium = isset( $params['single_extra_gallery_position_premium'] ) ? sanitize_key( (string) $params['single_extra_gallery_position_premium'] ) : 'below_description';
						$raw_single_extra_gallery_position_community = isset( $params['single_extra_gallery_position_community'] ) ? sanitize_key( (string) $params['single_extra_gallery_position_community'] ) : 'below_description';
						$params['single_extra_gallery_position_b2c'] = in_array( $raw_single_extra_gallery_position_b2c, $extra_gallery_position_allowed, true ) ? $raw_single_extra_gallery_position_b2c : 'below_description';
						$params['single_extra_gallery_position_premium'] = in_array( $raw_single_extra_gallery_position_premium, $extra_gallery_position_allowed, true ) ? $raw_single_extra_gallery_position_premium : 'below_description';
						$params['single_extra_gallery_position_community'] = in_array( $raw_single_extra_gallery_position_community, $extra_gallery_position_allowed, true ) ? $raw_single_extra_gallery_position_community : 'below_description';

						$raw_single_extra_gallery_display_mode_b2c = isset( $params['single_extra_gallery_display_mode_b2c'] ) ? sanitize_key( (string) $params['single_extra_gallery_display_mode_b2c'] ) : 'grid';
						$raw_single_extra_gallery_display_mode_premium = isset( $params['single_extra_gallery_display_mode_premium'] ) ? sanitize_key( (string) $params['single_extra_gallery_display_mode_premium'] ) : 'grid';
						$raw_single_extra_gallery_display_mode_community = isset( $params['single_extra_gallery_display_mode_community'] ) ? sanitize_key( (string) $params['single_extra_gallery_display_mode_community'] ) : 'grid';
						$params['single_extra_gallery_display_mode_b2c'] = in_array( $raw_single_extra_gallery_display_mode_b2c, $extra_gallery_display_allowed, true ) ? $raw_single_extra_gallery_display_mode_b2c : 'grid';
						$params['single_extra_gallery_display_mode_premium'] = in_array( $raw_single_extra_gallery_display_mode_premium, $extra_gallery_display_allowed, true ) ? $raw_single_extra_gallery_display_mode_premium : 'grid';
						$params['single_extra_gallery_display_mode_community'] = in_array( $raw_single_extra_gallery_display_mode_community, $extra_gallery_display_allowed, true ) ? $raw_single_extra_gallery_display_mode_community : 'grid';
						$raw_frontend_preset = isset( $params['frontend_preset'] ) ? sanitize_key( (string) $params['frontend_preset'] ) : '';
						$params['frontend_preset'] = in_array( $raw_frontend_preset, array( 'b2c', 'premium', 'community' ), true ) ? $raw_frontend_preset : '';

						// Tarif-Status-Box Einstellungen (mit Legacy-Fallback von tariff_* auf tarif_*)
					$legacy_enabled = isset( $params['tariff_status_enabled'] ) ? (int) $params['tariff_status_enabled'] : 0;
					$params['tarif_status_enabled'] = isset( $params['tarif_status_enabled'] ) ? ( empty( $params['tarif_status_enabled'] ) ? 0 : 1 ) : ( $legacy_enabled ? 1 : 0 );

					$raw_bg_color = isset( $params['tarif_status_bg_color'] ) ? $params['tarif_status_bg_color'] : ( isset( $params['tariff_status_bg_color'] ) ? $params['tariff_status_bg_color'] : '' );
					$params['tarif_status_bg_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_bg_color ) ? $raw_bg_color : '#f0f4f8';

					$raw_border_color = isset( $params['tarif_status_border_color'] ) ? $params['tarif_status_border_color'] : ( isset( $params['tariff_status_border_color'] ) ? $params['tariff_status_border_color'] : '' );
					$params['tarif_status_border_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_border_color ) ? $raw_border_color : '#2271b1';

					$raw_text_color = isset( $params['tarif_status_text_color'] ) ? $params['tarif_status_text_color'] : ( isset( $params['tariff_status_text_color'] ) ? $params['tariff_status_text_color'] : '' );
					$params['tarif_status_text_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_text_color ) ? $raw_text_color : '#333333';

					$raw_heading_color = isset( $params['tarif_status_heading_color'] ) ? $params['tarif_status_heading_color'] : ( isset( $params['tariff_status_heading_color'] ) ? $params['tariff_status_heading_color'] : '' );
					$params['tarif_status_heading_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_heading_color ) ? $raw_heading_color : '#1a1a1a';

					$raw_warning_color = isset( $params['tarif_status_warning_color'] ) ? $params['tarif_status_warning_color'] : ( isset( $params['tariff_status_warning_color'] ) ? $params['tariff_status_warning_color'] : '' );
					$params['tarif_status_warning_color'] = preg_match( '/^#[0-9a-f]{6}$/i', $raw_warning_color ) ? $raw_warning_color : '#d32f2f';

					$raw_text_size = isset( $params['tarif_status_text_size'] ) ? $params['tarif_status_text_size'] : ( isset( $params['tariff_status_text_size'] ) ? $params['tariff_status_text_size'] : '' );
					$params['tarif_status_text_size'] = in_array( $raw_text_size, array( 'small', 'normal', 'medium', 'large' ), true ) ? $raw_text_size : 'normal';

					$raw_padding = isset( $params['tarif_status_padding'] ) ? $params['tarif_status_padding'] : ( isset( $params['tariff_status_padding'] ) ? $params['tariff_status_padding'] : 15 );
					$params['tarif_status_padding'] = absint( $raw_padding );
						$archive_columns = isset( $params['archive_columns'] ) ? (int) $params['archive_columns'] : 3;
						$params['archive_columns'] = in_array( $archive_columns, array( 2, 3, 4 ), true ) ? $archive_columns : 3;
					}
					unset($params['new_role'],
					$params['add_role'],
					$params['delete_role'],
					$params['save']
					);

					$this->save_options( $params );
					$this->message = __( 'Einstellungen gespeichert.', $this->text_domain );
				}
				/* Render admin template */
				$this->render_admin( "settings-{$tab}" );

			}
		}
	}

	/**
	 * Build a clean package array from request values.
	 *
	 * @param array $packages
	 * @return array
	 */
	function sanitize_credit_packages( $packages ) {
		if ( ! is_array( $packages ) ) {
			return array();
		}

		$labels = isset( $packages['label'] ) && is_array( $packages['label'] ) ? $packages['label'] : array();
		$credits = isset( $packages['credits'] ) && is_array( $packages['credits'] ) ? $packages['credits'] : array();
		$prices = isset( $packages['price'] ) && is_array( $packages['price'] ) ? $packages['price'] : array();
		$product_ids = isset( $packages['product_id'] ) && is_array( $packages['product_id'] ) ? $packages['product_id'] : array();

		$max = max( count( $labels ), count( $credits ), count( $prices ), count( $product_ids ) );
		$result = array();

		for ( $i = 0; $i < $max; $i++ ) {
			$label = isset( $labels[ $i ] ) ? sanitize_text_field( $labels[ $i ] ) : '';
			$credit_count = isset( $credits[ $i ] ) ? absint( $credits[ $i ] ) : 0;
			$price = isset( $prices[ $i ] ) ? $this->sanitize_decimal_string( $prices[ $i ] ) : '0.00';
			$product_id = isset( $product_ids[ $i ] ) ? absint( $product_ids[ $i ] ) : 0;

			if ( $credit_count <= 0 ) {
				continue;
			}

			if ( '' === $label ) {
				$label = sprintf( '%d Credits', $credit_count );
			}

			$result[] = array(
				'label'      => $label,
				'credits'    => $credit_count,
				'price'      => $price,
				'product_id' => $product_id,
			);
		}

		return $result;
	}

	/**
	 * Sanitize per-package affiliate commissions.
	 *
	 * @param array $commissions
	 * @return array
	 */
	function sanitize_affiliate_credit_commissions( $modes, $values ) {
		if ( ! is_array( $modes ) && ! is_array( $values ) ) {
			return array();
		}

		$result = array();
		$modes = is_array( $modes ) ? $modes : array();
		$values = is_array( $values ) ? $values : array();

		foreach ( array_unique( array_merge( array_keys( $modes ), array_keys( $values ) ) ) as $product_id ) {
			$product_id = absint( $product_id );
			if ( $product_id <= 0 ) {
				continue;
			}

			$rule = $this->sanitize_affiliate_commission_rule(
				isset( $modes[ $product_id ] ) ? $modes[ $product_id ] : 'fixed',
				isset( $values[ $product_id ] ) ? $values[ $product_id ] : ''
			);

			if ( empty( $rule ) ) {
				continue;
			}

			$result[ $product_id ] = $rule;
		}

		return $result;
	}

	/**
	 * Sanitize a single affiliate commission rule.
	 *
	 * @param string $mode
	 * @param mixed  $value
	 * @return array
	 */
	function sanitize_affiliate_commission_rule( $mode, $value ) {
		$mode = ( 'percent' === $mode ) ? 'percent' : 'fixed';
		$normalized = $this->sanitize_decimal_string( $value );
		if ( (float) $normalized <= 0 ) {
			return array();
		}

		if ( 'percent' === $mode ) {
			$normalized = sprintf( '%.2f', min( 100, max( 0, (float) $normalized ) ) );
		}

		return array(
			'mode'  => $mode,
			'value' => $normalized,
		);
	}

	/**
	 * Keep decimal text normalized for storage.
	 *
	 * @param mixed $value
	 * @return string
	 */
	function sanitize_decimal_string( $value ) {
		$value = (string) $value;
		$value = str_replace( ',', '.', $value );
		$value = preg_replace( '/[^0-9\.]/', '', $value );
		if ( '' === $value ) {
			return '0.00';
		}

		return sprintf( '%.2f', (float) $value );
	}

	/**
	 * Auto-create/update MarketPress products for one-time and credit packages.
	 *
	 * @param array $params
	 * @return array
	 */
	function sync_marketpress_checkout_products( $params ) {
		if ( empty( $params['enable_marketpress_bridge'] ) ) {
			return $params;
		}

		if ( ! class_exists( 'MP_Product' ) ) {
			return $params;
		}

		$post_type = MP_Product::get_post_type();
		$existing = $this->get_options( 'payments' );
		$existing = is_array( $existing ) ? $existing : array();

		if ( ! empty( $params['enable_one_time'] ) ) {
			$one_time_id = ! empty( $params['mp_one_time_product_id'] ) ? absint( $params['mp_one_time_product_id'] ) : 0;
			if ( $one_time_id <= 0 && ! empty( $existing['mp_one_time_product_id'] ) ) {
				$one_time_id = absint( $existing['mp_one_time_product_id'] );
			}

			$title = ! empty( $params['one_time_txt'] ) ? sanitize_text_field( $params['one_time_txt'] ) : __( 'Kleinanzeigen Einmalzahlung', $this->text_domain );
			$one_time_id = $this->upsert_marketpress_product( $one_time_id, $title, $params['one_time_cost'], $post_type );
			$params['mp_one_time_product_id'] = $one_time_id;
		}

		if ( ! empty( $params['enable_credits'] ) ) {
			$packages = isset( $params['mp_credit_packages'] ) && is_array( $params['mp_credit_packages'] ) ? $params['mp_credit_packages'] : array();
			$updated_packages = array();

			foreach ( $packages as $package ) {
				$product_id = empty( $package['product_id'] ) ? 0 : absint( $package['product_id'] );
				$title = sprintf( __( 'Credits Paket: %s', $this->text_domain ), sanitize_text_field( $package['label'] ) );
				$product_id = $this->upsert_marketpress_product( $product_id, $title, $package['price'], $post_type );

				if ( $product_id > 0 ) {
					update_post_meta( $product_id, 'cf_credit_amount', absint( $package['credits'] ) );
				}

				$package['product_id'] = $product_id;
				$updated_packages[] = $package;
			}

			$params['mp_credit_packages'] = $updated_packages;
		}

		if ( ! empty( $params['enable_featured'] ) && isset( $params['featured_cost_type'] ) && 'money' === $params['featured_cost_type'] ) {
			$featured_product_id = ! empty( $params['featured_money_product_id'] ) ? absint( $params['featured_money_product_id'] ) : 0;
			if ( $featured_product_id <= 0 && ! empty( $existing['featured_money_product_id'] ) ) {
				$featured_product_id = absint( $existing['featured_money_product_id'] );
			}

			$featured_title = __( 'Kleinanzeigen Featured Upgrade', $this->text_domain );
			$featured_price = isset( $params['featured_money_cost'] ) ? $params['featured_money_cost'] : '2.99';
			$featured_product_id = $this->upsert_marketpress_product( $featured_product_id, $featured_title, $featured_price, $post_type );
			$params['featured_money_product_id'] = $featured_product_id;

			if ( $featured_product_id > 0 ) {
				update_post_meta( $featured_product_id, 'cf_featured_upgrade', 1 );
			}
		}

		return $params;
	}

	/**
	 * Create or update a simple MarketPress digital product.
	 *
	 * @param int    $product_id
	 * @param string $title
	 * @param string $price
	 * @param string $post_type
	 * @return int
	 */
	function upsert_marketpress_product( $product_id, $title, $price, $post_type ) {
		$product_id = absint( $product_id );
		$title = sanitize_text_field( $title );
		$price = $this->sanitize_decimal_string( $price );

		if ( $product_id <= 0 || ! get_post( $product_id ) ) {
			$product_id = wp_insert_post( array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => $title,
			) );
		} else {
			wp_update_post( array(
				'ID'         => $product_id,
				'post_title' => $title,
			) );
		}

		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			return 0;
		}

		update_post_meta( $product_id, 'regular_price', $price );
		update_post_meta( $product_id, 'product_type', 'digital' );
		update_post_meta( $product_id, 'track_inventory', 0 );

		return $product_id;
	}

	/**
	 * Keep legacy gateway options disabled after migration to MarketPress flow.
	 *
	 * @param array $payments_params
	 * @return void
	 */
	function sync_legacy_payment_types( $payments_params ) {
		$options = $this->get_options();
		$payment_types = ( isset( $options['payment_types'] ) && is_array( $options['payment_types'] ) ) ? $options['payment_types'] : array();

		$payment_types['use_free'] = empty( $payments_params['use_free'] ) ? 0 : 1;
		unset(
			$payment_types['use_paypal'],
			$payment_types['use_authorizenet'],
			$payment_types['paypal'],
			$payment_types['authorizenet']
		);
		unset( $options['paypal'], $options['authorizenet'] );

		$options['payment_types'] = $payment_types;
		update_option( $this->options_name, $options );
	}

	/**
	* Handles $_GET and $_POST requests for the credits page.
	*
	* @return void
	*/
	function handle_credits_requests(){
		$valid_tabs = array(
		'my-credits',
		'send-credits',
		);

		$params = stripslashes_deep($_POST);

		$page = (empty($_GET['page'])) ? '' : $_GET['page'] ;
		$tab = (empty($_GET['tab'])) ? 'my-credits' : $_GET['tab']; //default tab

		if($page == 'classifieds_credits' && in_array($tab, $valid_tabs) ) {
			if ( $tab == 'send-credits' ) {
				if(!empty($params)) check_admin_referer('verify');
				$send_to = ( empty($params['manage_credits'])) ? '' : $params['manage_credits'];
				$send_to_user = ( empty($params['manage_credits_user'])) ? '' : $params['manage_credits_user'];
				$send_to_count = ( empty($params['manage_credits_count'])) ? '' : $params['manage_credits_count'];

				$credits = (is_numeric($send_to_count)) ? (intval($send_to_count)) : 0;

				if(is_multisite()) $blog_id = get_current_blog_id();

				if ($send_to == 'send_single'){
					$user = get_user_by('login', $send_to_user);
					if($user){
						$transaction = new CF_Transactions($user->ID, $blog_id);
						$transaction->credits += $credits;
						unset($transaction);
						$this->message = sprintf(__('Benutzer "%s" hat %s Guthaben auf sein Kleinanzeigen-Konto erhalten',$this->text_domain), $send_to_user, $credits);

					} else {
						$this->message = sprintf(__('Benutzer "%s" wurde nicht gefunden oder ist kein Kleinanzeigen-Mitglied',$this->text_domain), $send_to_user);
					}
				}

				if ($send_to == 'send_all'){
					$search = array();
					if(is_multisite()) $search['blog_id'] = get_current_blog_id();
					$users = get_users($search);
					foreach($users as $user){
						$transaction = new CF_Transactions($user->ID, $blog_id);
						$transaction->credits += $credits;
						unset($transaction);
					}
					$this->message = sprintf(__('Allen Benutzern wurde "%s" Guthaben zu ihren Konten hinzugefügt.',$this->text_domain), $credits);

				}
			} else {
				if ( isset( $params['purchase'] ) ) {
					$this->js_redirect( get_permalink($this->checkout_page_id) );
				}
			}
		}

		$this->render_admin( "credits-{$tab}" );

		do_action( 'cf_handle_credits_requests' );
	}


	/**
	* Hook styles and scripts into plugin admin head
	*
	* @return void
	**/
	function admin_head() {
		/* Get plugin hook */
		$this->hook = '';
		if ( isset( $_GET['page'] ) )
		$this->hook = get_plugin_page_hook( $_GET['page'], $this->menu_slug );
		/* Add actions for printing the styles and scripts of the document */
		add_action( 'admin_print_scripts-' . $this->hook, array( &$this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_head-' . $this->hook, array( &$this, 'admin_print_scripts' ) );
	}

	/**
	* Enqueue scripts.
	*
	* @return void
	**/
	function admin_enqueue_scripts() {
		wp_enqueue_script('jquery');
	}

	/**
	 * Print document scripts
	 */
	function admin_print_scripts() {
		?>
		<script type="text/javascript">//<![CDATA[
			jQuery(document).ready(function($) {
				$('form.cf-form').hide();
			});
			var classifieds = {
				toggle_end: function(key) {
					$('#form-' + key).show();
					$('.action-links-' + key).hide();
					$('.separators-' + key).hide();
					$('input[name="action"]').val('end');
				},
				toggle_publish: function(key) {
					$('#form-' + key).show();
					$('#form-' + key + ' select').show();
					$('.action-links-' + key).hide();
					$('.separators-' + key).hide();
					$('input[name="action"]').val('publish');
				},
				toggle_delete: function(key) {
					$('#form-' + key).show();
					$('#form-' + key + ' select').hide();
					$('.action-links-' + key).hide();
					$('.separators-' + key).hide();
					$('input[name="action"]').val('delete');
				},
				cancel: function(key) {
					$('#form-' + key).hide();
					$('.action-links-' + key).show();
					$('.separators-' + key).show();
				}
			};
		//]]>
		</script>
		<?php
	}

	/**
	* Ajax callback which gets the post types associated with each page.
	*
	* @return JSON Encoded string
	*/
	function ajax_get_caps() {
		check_ajax_referer( 'verify' );
		if ( !current_user_can( 'manage_options' ) ) die(-1);
		if(empty($_POST['role'])) die(-1);

		global $wp_roles;

		$role = sanitize_key( wp_unslash( $_POST['role'] ) );

		if ( !$wp_roles->is_role( $role ) )
		die(-1);

		$role_obj = $wp_roles->get_role( $role );

		$response = array_intersect( array_keys( $role_obj->capabilities ), array_keys( $this->capability_map ) );
		$response = array_flip( $response );

		// response output
		header( "Content-Type: application/json" );
		echo json_encode( $response );
		die();
	}

	/**
	* Save admin options.
	*
	* @return void die() if _wpnonce is not verified
	*/
	function ajax_save() {

		check_admin_referer( 'verify' );

		if ( !current_user_can( 'manage_options' ) )
		die(-1);

		// add/remove capabilities
		global $wp_roles;

		$role = sanitize_key( wp_unslash( $_POST['roles'] ?? '' ) );

		$all_caps = array_keys( $this->capability_map );
		$raw_caps = isset( $_POST['capabilities'] ) && is_array( $_POST['capabilities'] ) ? $_POST['capabilities'] : array();
		$to_add = array_map( 'sanitize_key', array_keys( $raw_caps ) );
		$to_remove = array_diff( $all_caps, $to_add );

		foreach ( $to_remove as $capability ) {
			$wp_roles->remove_cap( $role, $capability );
		}

		foreach ( $to_add as $capability ) {
			$wp_roles->add_cap( $role, $capability );
		}

		die(1);
	}

	function on_restrict_manage_posts( $post_type = '' ) {
		global $typenow;
		$taxonomy = 'classifieds_categories';
		if( $typenow == "classifieds" ){

			$filters = array($taxonomy);
			foreach ($filters as $tax_slug) {
				$tax_obj = get_taxonomy( $tax_slug );
				if ( ! is_object( $tax_obj ) || empty( $tax_obj->labels ) ) {
					continue;
				}

				$terms = get_terms( array( 'taxonomy' => $tax_slug ) );
				if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
					$terms = array();
				}
				$current_tax = isset( $_GET[ $tax_slug ] ) ? sanitize_text_field( wp_unslash( $_GET[ $tax_slug ] ) ) : '';
				echo "<select name='" . esc_attr( $tax_slug ) . "' id='" . esc_attr( $tax_slug ) . "' class='postform'>";
				echo '<option value="">' . esc_html( $tax_obj->labels->all_items ) . '&nbsp;</option>';
				foreach ($terms as $term) {
					$term_slug = '';
					$term_name = '';
					$term_count = 0;

					if ( is_object( $term ) ) {
						$term_slug = isset( $term->slug ) ? (string) $term->slug : '';
						$term_name = isset( $term->name ) ? (string) $term->name : '';
						$term_count = isset( $term->count ) ? (int) $term->count : 0;
					} elseif ( is_array( $term ) ) {
						$term_slug = isset( $term['slug'] ) ? (string) $term['slug'] : '';
						$term_name = isset( $term['name'] ) ? (string) $term['name'] : '';
						$term_count = isset( $term['count'] ) ? (int) $term['count'] : 0;
					}

					if ( '' === $term_slug ) {
						continue;
					}

					echo '<option value="' . esc_attr( $term_slug ) . '"' . selected( $current_tax, $term_slug, false ) . '>' . esc_html( $term_name ) . ' (' . esc_html( (string) $term_count ) . ')</option>';
				}
				echo "</select>";
			}
		}
	}

    /**
     * Fix the bug user still can publish in backend
     * @since 2.3.6.7
     * @author Hoang
     */
    function determine_backend_cap($data, $cap, $args)
    {
        if (!is_admin()) {
            return $data;
        }
        if (!in_array('publish_classifieds', $cap)) {
            return $data;
        }
        global $current_user;
        //check does this page is add classifield
        if (!isset($current_user->allcaps['manage_options'])) {
            //user is normal user
            global $Classifieds_Core;
            $options = $Classifieds_Core->get_options();
            if (!isset($options['moderation']['publish'])) {
                //no publish allowed, we will remove the publish classifield cap, admin only
                unset($data['publish_classifieds']);
            }
        }

        return $data;
    }
}

global $Classifieds_Core;

$Classifieds_Core = new Classifieds_Core_Admin();

endif;

?>