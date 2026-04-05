<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Classified_Submit_Validator {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Prueft, ob eine Anzeige fuer die gewaehlte Laufzeit veroeffentlicht werden darf.
	 *
	 * @param array $request
	 * @return array
	 */
	public function validate_update_submission( $request ) {
		$duration_key = isset( $this->core->custom_fields['duration'] ) ? $this->core->custom_fields['duration'] : 'duration';
		$duration     = isset( $request[ $duration_key ] ) ? $request[ $duration_key ] : ( $request['duration'] ?? '' );

		$credits_required = (int) $this->core->get_credits_from_duration( $duration );
		$has_credits      = $this->core->is_full_access() || ( (int) $this->core->user_credits >= $credits_required );

		return array(
			'has_credits'      => (bool) $has_credits,
			'credits_required' => $credits_required,
			'duration'         => $duration,
		);
	}
}
