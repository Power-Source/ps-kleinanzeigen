<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Shortcode_Service {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * @param array $atts
	 * @param mixed $content
	 * @return string
	 */
	public function classifieds_categories_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'style' => '',
				'ccats' => '',
			),
			(array) $atts
		);

		$style  = (string) $atts['style'];
		$result = '';

		if ( $style === 'grid' ) {
			$result .= PHP_EOL . '<div class="cf_list_grid">' . PHP_EOL;
		} elseif ( $style === 'list' ) {
			$result .= '<div class="cf_list">' . PHP_EOL;
		} else {
			$result .= "<ul>\n";
		}

		$result .= the_cf_categories_home( false, $atts );
		$result .= "</div><!--.cf_list-->\n";

		return $result;
	}

	public function classifieds_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'Classifieds', $this->core->text_domain ),
				'view' => 'both',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button classifieds_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( get_permalink( $this->core->classifieds_page_id ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function add_classified_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'Add Classified', $this->core->text_domain ),
				'view' => 'both',
			),
			(array) $atts
		);

		if ( ! current_user_can( 'create_classifieds' ) ) {
			return '';
		}
		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button create-new-btn add_classified_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( get_permalink( $this->core->add_classified_page_id ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function edit_classified_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'Edit Classified', $this->core->text_domain ),
				'view' => 'both',
				'post' => '0',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$post_id     = absint( $atts['post'] );
		$button_text = empty( $content ) ? $atts['text'] : $content;
		$target_url  = add_query_arg( 'post_id', $post_id, get_permalink( $this->core->edit_classified_page_id ) );
		ob_start();
		?>
		<button class="cf_button add_classified_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( $target_url ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function checkout_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'Zur Kasse', $this->core->text_domain ),
				'view' => 'both',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button checkout_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( get_permalink( $this->core->checkout_page_id ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function my_credits_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'My Classifieds Credits', $this->core->text_domain ),
				'view' => 'both',
			),
			(array) $atts
		);

		if ( ! $this->core->use_credits ) {
			return '';
		}
		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button credits_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( get_permalink( $this->core->my_credits_page_id ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function my_classifieds_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'My Classifieds', $this->core->text_domain ),
				'view' => 'loggedin',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button my_classified_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( get_permalink( $this->core->my_classifieds_page_id ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function profile_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text' => __( 'Go to Profile', $this->core->text_domain ),
				'view' => 'both',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button profile_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( admin_url( 'profile.php' ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function signin_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text'     => __( 'Signin', $this->core->text_domain ),
				'redirect' => '',
				'view'     => 'loggedout',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$redirect = (string) $atts['redirect'];
		$options  = $this->core->get_options( 'general' );
		if ( empty( $redirect ) ) {
			$redirect = empty( $options['signin_url'] ) ? home_url() : $options['signin_url'];
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		$target_url  = get_permalink( $this->core->signin_page_id ) . '?redirect_to=' . urlencode( $redirect );
		ob_start();
		?>
		<button class="cf_button signin_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( $target_url ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function logout_btn_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text'     => __( 'Logout', $this->core->text_domain ),
				'redirect' => '',
				'view'     => 'loggedin',
			),
			(array) $atts
		);

		if ( ! $this->should_render_for_view( $atts['view'] ) ) {
			return '';
		}

		$redirect = (string) $atts['redirect'];
		$options  = $this->core->get_options( 'general' );
		if ( empty( $redirect ) ) {
			$redirect = empty( $options['logout_url'] ) ? home_url() : $options['logout_url'];
		}

		$button_text = empty( $content ) ? $atts['text'] : $content;
		ob_start();
		?>
		<button class="cf_button logout_btn" type="button"
		        onclick="window.location.href='<?php echo esc_url( wp_logout_url( $redirect ) ); ?>';"><?php echo esc_html( $button_text ); ?></button>
		<?php
		return (string) ob_get_clean();
	}

	public function custom_fields_sc( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'text'     => __( 'Logout', $this->core->text_domain ),
				'redirect' => '',
				'view'     => 'loggedin',
			),
			(array) $atts
		);

		$render_text = empty( $content ) ? $atts['text'] : $content;
		if ( empty( $render_text ) ) {
			$render_text = '';
		}

		ob_start();
		$this->core->display_custom_fields_values();
		return (string) ob_get_clean();
	}

	/**
	 * @param string $view
	 * @return bool
	 */
	private function should_render_for_view( $view ) {
		$view = strtolower( (string) $view );

		if ( is_user_logged_in() ) {
			return $view !== 'loggedout';
		}

		return $view !== 'loggedin';
	}
}
