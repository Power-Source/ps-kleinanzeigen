<?php

/**
* Load core DB data. Only loaded during Activation
*/
if ( !class_exists('Classifieds_Core_Data') ):
class Classifieds_Core_Data {

	/**
	* Constructor.
	*
	* @return void
	**/

	function __construct() {
		add_action( 'init', array( &$this, 'load_data' ) );
		add_action( 'init', array( &$this, 'load_payment_data' ) );
		add_action( 'init', array( &$this, 'load_mu_plugins' ) );
		add_action( 'init', array( &$this, 'rewrite_rules' ) );
	}

	/**
	* Load initial Content Types data for plugin
	*
	* @return void
	*/
	function load_data() {
		/* Ensure options array exists; all content types and fields are native now. */
		$options = get_site_option( CF_OPTIONS_NAME );
		if ( ! is_array( $options ) ) {
			update_site_option( CF_OPTIONS_NAME, array() );
		}

	}

	function load_payment_data() {

		$options = ( get_option( CF_OPTIONS_NAME ) ) ? get_option( CF_OPTIONS_NAME ) : array();
		$options = ( is_array($options) ) ? $options : array();

		//General default
		if(empty($options['general']) ){
			$options['general'] = array(
			'member_role'             => 'subscriber',
			'moderation'              => array('publish' => 1, 'pending' => 1, 'draft' => 1 ),
			'custom_fields_structure' => 'table',
			'welcome_redirect'        => 'true',
			'key'                     => 'general'
			);
		}

		//Update from older version
		if (! empty($options['general_settings']) ) {
			$options['general'] = array_replace($options['general_settings']);
			unset($options['general_settings']);
		}

		//Default Payments settings
		if ( empty( $options['payments'] ) ) {
			$options['payments'] = array(
			'use_free'           => '1',
			'enable_recurring'    => '1',
			'recurring_cost'      => '9.99',
			'recurring_name'      => 'Subscription',
			'billing_period'      => 'Month',
			'billing_frequency'   => '1',
			'billing_agreement'   => 'Customer will be billed at &ldquo;9.99 per month for 2 years&rdquo;',
			'required_membership_ids' => array(),
			'enable_one_time'     => '1',
			'one_time_cost'       => '99.99',
			'one_time_name'       => 'One Time Only',
			'enable_credits'      => '1',
			'cost_credit'         => '.99',
			'credits_per_week'    => 1,
			'signup_credits'      => 0,
			'credits_description' => '',
			'tos_txt'             => 'Mit dem Absenden einer Kleinanzeige bestaetigst Du, dass Deine Angaben wahrheitsgemaess sind und keine Rechte Dritter verletzen. Unzulaessige, irrefuehrende oder rechtswidrige Inhalte sind nicht erlaubt und koennen entfernt werden. Kostenpflichtige Optionen werden vor Abschluss transparent angezeigt. Es gelten unsere <a href="/datenschutz" target="_blank" rel="noopener noreferrer">Datenschutzhinweise</a> und das <a href="/impressum" target="_blank" rel="noopener noreferrer">Impressum</a>.',
			'key'                 => 'payments'
			);
		}

		if (! empty($options['payment_settings']) ) {
			$options['payments'] = array_replace($options['payment_settings']);
			unset($options['payment_settings']);
		}

		if ( ! isset( $options['payments']['use_free'] ) ) {
			$options['payments']['use_free'] = ( ! empty( $options['payment_types']['use_free'] ) ) ? '1' : '0';
		}

		if(empty($options['payment_types']) ) {
			$options['payment_types'] = array(
			'use_free'         => 1,
			'use_paypal'       => 0,
			'use_authorizenet' => 0,
			'paypal'           => array('api_url' => 'sandbox', 'api_username' => '', 'api_password' => '', 'api_signature' => '', 'currency' => 'USD'),
			'authorizenet'     => array('mode' => 'sandbox', 'delim_char' => ',', 'encap_char' => '', 'email_customer' => 'yes', 'header_email_receipt' => 'Thanks for your payment!', 'delim_data' => 'yes'),
			);
		}

		if ( ! empty($options['paypal']) ){
			$options['payment_types']['paypal'] = array_replace($options['paypal']);
			unset($options['paypal']);
		}

		update_option( CF_OPTIONS_NAME, $options );
	}

	function load_mu_plugins(){

		if(!is_dir(WPMU_PLUGIN_DIR . '/logs')):
		mkdir(WPMU_PLUGIN_DIR . '/logs', 0755, true);
		endif;

		copy(	CF_PLUGIN_DIR . 'mu-plugins/gateway-relay.php', WPMU_PLUGIN_DIR .'/gateway-relay.php');
		copy(	CF_PLUGIN_DIR . 'mu-plugins/wpmu-assist.php', WPMU_PLUGIN_DIR .'/wpmu-assist.php');

	}

	function rewrite_rules() {

		add_rewrite_rule("classifieds/author/([^/]+)/page/?([2-9][0-9]*)",
		"index.php?post_type=classifieds&author_name=\$matches[1]&paged=\$matches[2]", 'top');

		add_rewrite_rule("classifieds/author/([^/]+)",
		"index.php?post_type=classifieds&author_name=\$matches[1]", 'top');

			// Do not flush rules on every request.
	}

}

endif;
