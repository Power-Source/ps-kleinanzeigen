<?php

class CF_Transactions{

	public $text_domain = CF_TEXT_DOMAIN;
	public $options_name = CF_OPTIONS_NAME;
	public $plugin_dir = CF_PLUGIN_DIR;
	public $plugin_url = CF_PLUGIN_URL;

	public $user_id = 0;
	public $blog_id = 0;

	protected $_transactions = null;
	protected $_credits = 0;
	protected $_credits_log = array();
	protected $_order = 0;
	protected $_status = null;
	protected $_expires = 0;
	protected $_billing_type = '';
	protected $_ordeer_info = '';

	protected $struc =
	array(
	'credits' => 0,

	//credits_log - array of credit purchases.
	'credits_log' => array(),

	//order- Information about the last successful order. Use expires and status to decide wheter user can add classifieds.
	'order' => array('billing_type' => '', 'billing_frequency' => '', 'billing_period' => '','payment_method' => '', 'status' => '', 'expires' => 0, 'order_info' => array() ),
	);

	function __construct($user_id = 0, $blogid=0){
		global $blog_id;

		$this->user_id = (empty($user_id)) ? get_current_user_id() : $user_id;
		$this->blog_id = (empty($blogid)) ? $blog_id : $blogid;

		//Convert from old style
		$cf_order = get_user_meta( $this->user_id, 'cf_order', true );
		$cf_credits = get_user_meta( $this->user_id, 'cf_credits', true );
		$cf_credits_log = get_user_meta( $this->user_id, 'cf_credits_log', true );

		//Blog specfic version
		$cf_transactions = get_user_option( 'cf_transactions', $this->user_id );

		// $cf_blog has entire transaction array
		if(! empty($cf_order) || ! empty($cf_credits) || ! empty($cf_credits_log) ) { // Need to convert
			$cf_transactions = $this->struc;
			$cf_transactions['credits'] = (empty($cf_credits) ) ? 0 : $cf_credits;
			$status = (empty($cf_order['order_info']['status']) ) ? '' : $cf_order['order_info']['status'];
			$expires = (empty($cf_order['time_end_annual']) ) ? 0 : $cf_order['time_end_annual'];
			$billing = (empty($cf_order['billing']) ) ? '' : $cf_order['billing'];
			$cf_transactions['order']['status'] = $status;
			$cf_transactions['order']['expires'] = $expires;
			$cf_transactions['order']['billing_type'] = $billing;
			update_user_option($this->user_id, 'cf_transactions', $cf_transactions);

			delete_user_meta($this->user_id, 'cf_order');
			delete_user_meta($this->user_id, 'cf_credits');
			delete_user_meta($this->user_id, 'cf_credits_log');
		}

		if(! $cf_transactions ){ //First time transactions
			$cf_transactions = $this->struc;
			$options = $this->get_options('payments');
			$cf_transactions['credits'] = (empty($options['signup_credits']) ) ? 0 : $options['signup_credits'];
			update_user_option($this->user_id, 'cf_transactions', $cf_transactions);
		}

	}

	/**
	* Get plugin options.
	*
	* @param  string|NULL $key The key for that plugin option.
	* @return array $options Plugin options or empty array if no options are found
	*/
	function get_options( $key = null ) {
		$options = get_option( $this->options_name );
		$options = is_array( $options ) ? $options : array();
		/* Check if specific plugin option is requested and return it */
		if ( isset( $key ) && array_key_exists( $key, $options ) )
		return $options[$key];
		else
		return $options;
	}

	function __get( $property = '' ){

		$this->_transactions = $this->get_transactions();

		switch ($property) {
			case 'transactions' :
			return $this->_transactions;
			break;

			case 'credits' :
			$this->_credits = (empty($this->_transactions['credits']) ) ? 0 : $this->_transactions['credits'];
			return $this->_credits;
			break;

			case 'credits_log' :
			$this->_credits_log = $this->_transactions['credits_log'];
			return $this->_credits_log;
			break;

			case 'order' :
			$this->_order = $this->_transactions['order'];
			return $this->_order;
			break;

			case 'status' :
			$this->_status = $this->_transactions['order']['status'];
			return $this->_status;
			break;

			case 'expires' :
			$this->_expires = $this->_transactions['order']['expires'];
			return $this->_expires;
			break;

			case 'order_info' :
			$this->_order_info = $this->_transactions['order']['order_info'];
			return $this->_order_info;
			break;

			case 'billing_type' :
			$this->_billing_type = $this->_transactions['order']['billing_type'];
			return $this->_billing_type;
			break;
		}
	}

	function __set($property, $value){

		$this->_transactions = $this->get_transactions();

		switch ($property) {

			case 'transactions' :
			$this->_transactions = $value;
			break;

			case 'credits' :
			$added = $value - $this->_transactions['credits'];
			$this->_transactions['credits'] = $value;
			$this->_transactions['credits_log'][] = array('credits' => $added, 'date' => time() ); //Log the change
			break;

			case 'credits_log' :
			$this->_transactions['credits_log'] = $value;
			break;

			case 'status' :
			$this->_transactions['order']['status'] = $value;
			break;

			case 'expires' :
			$this->_transactions['order']['expires'] = $value;
			break;

			case 'billing_type' :
			$this->_transactions['order']['billing_type'] = $value;
			break;

			case 'order_info' :
			$this->_transactions['order']['order_info'] = $value;
			break;

		}
		return $this->update_transactions($this->_transactions);
	}

	function get_expiration_date($billing_period, $billing_frequency, $from_date = null){
		if(empty($from_date) ) $from_date = new DateTime(); // assume now.
		
		$from_date->modify("+{$billing_frequency} {$billing_period}" );

		//$period = 'P' . $billing_frequency . substr($billing_period, 0, 1 ); //ISO interval
		//$from_date->add(new DateInterval($period) );
		//$from_date->add(new DateInterval('P3D') ); // 3 day grace period
		return $from_date;
	}

	function __isset($property){
		return ( in_array($property, array(
		'transactions',
		'credits',
		'credits_log',
		'order',
		'order_info',
		'status',
		'expires',
		'billing_type',
		'billing_period',
		'billing_frequency',
		)
		) );
	}
	/**
	* Get Classifieds Transactions
	*
	*/

	protected function get_transactions(){
		return get_user_option( 'cf_transactions', $this->user_id );
	}

	/**
	* Update Classifieds Transactions
	*
	*/
	protected function update_transactions($transactions= null){
		if(empty($transactions) ) return;
		return update_user_option($this->user_id, 'cf_transactions', $transactions );
	}
}
