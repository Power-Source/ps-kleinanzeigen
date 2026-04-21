<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Template_Content_Service {
	/** @var Classifieds_Core */
	private $core;

	/** @var array<string> */
	private $supported_presets = array( 'b2c', 'premium', 'community' );

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * @param string $template
	 * @return string
	 */
	public function custom_classifieds_template( $template ) {
		if ( '' != get_query_var( 'cf_author_name' ) || ( isset( $_REQUEST['cf_author'] ) && '' != $_REQUEST['cf_author'] ) ) {
			if ( 'loop-author' != $template ) {
				$template = 'page-author';
			}
		}

		$tpldir = get_stylesheet_directory();
		$subdir = apply_filters( 'classifieds_custom_templates_dir', $tpldir . '/classifieds' );

		$preset = $this->get_frontend_preset();
		$preset_templates = array( 'loop-taxonomy', 'single-classifieds' );

		$candidates = array();
		if ( '' !== $preset && in_array( $template, $preset_templates, true ) ) {
			$preset_template = $template . '-' . $preset;
			$candidates = array(
				$tpldir . '/' . $preset_template . '.php',
				$tpldir . '/page-' . $preset_template . '.php',
				$subdir . '/' . $preset_template . '.php',
				$subdir . '/page-' . $preset_template . '.php',
				CF_PLUGIN_DIR . 'ui-front/general/page-' . $preset_template . '.php',
				CF_PLUGIN_DIR . 'ui-front/general/' . $preset_template . '.php',
			);
		}

		$candidates = array_merge( $candidates, array(
			$tpldir . '/' . $template . '.php',
			$tpldir . '/page-' . $template . '.php',
			$subdir . '/' . $template . '.php',
			$subdir . '/page-' . $template . '.php',
			CF_PLUGIN_DIR . 'ui-front/general/page-' . $template . '.php',
			CF_PLUGIN_DIR . 'ui-front/general/' . $template . '.php',
		) );

		foreach ( $candidates as $template_path ) {
			if ( file_exists( $template_path ) ) {
				return $template_path;
			}
		}

		$page_template = get_page_template();
		return ! empty( $page_template ) ? $page_template : $template;
	}

	/**
	 * Resolve active frontend preset from options.
	 *
	 * @return string
	 */
	private function get_frontend_preset() {
		$frontend_options = $this->core->get_options( 'frontend' );
		if ( ! is_array( $frontend_options ) ) {
			return '';
		}

		$preset = isset( $frontend_options['frontend_preset'] ) ? sanitize_key( (string) $frontend_options['frontend_preset'] ) : '';
		if ( in_array( $preset, $this->supported_presets, true ) ) {
			return $preset;
		}

		return '';
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function classifieds_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}

		ob_start();
		remove_filter( 'the_title', array( $this->core, 'page_title_output' ), 10, 2 );
		remove_filter( 'the_content', array( $this->core, 'classifieds_content' ) );
		require $this->custom_classifieds_template( 'classifieds' );
		wp_reset_query();

		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function update_classified_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		ob_start();
		require $this->custom_classifieds_template( 'update-classified' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function my_classifieds_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		ob_start();
		require $this->custom_classifieds_template( 'my-classifieds' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function checkout_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		remove_filter( 'the_content', array( $this->core, 'checkout_content' ) );
		ob_start();
		require $this->custom_classifieds_template( 'checkout' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function signin_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		remove_filter( 'the_title', array( $this->core, 'delete_post_title' ) );
		remove_filter( 'the_content', array( $this->core, 'signin_content' ) );
		ob_start();
		require $this->custom_classifieds_template( 'signin' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function my_credits_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}

		remove_filter( 'the_content', array( $this->core, 'my_credits_content' ) );
		ob_start();
		require $this->custom_classifieds_template( 'page-my-credits' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function single_content( $content = null ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		remove_filter( 'the_content', array( $this->core, 'single_content' ) );
		ob_start();
		require $this->custom_classifieds_template( 'single-classifieds' );
		$new_content = ob_get_contents();
		ob_end_clean();

		return $new_content;
	}

	/**
	 * Magic method: delegate property access to core.
	 *
	 * @param string $name Property name
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( isset( $this->core->$name ) ) {
			return $this->core->$name;
		}
		return null;
	}

	/**
	 * Magic method: delegate method calls to core.
	 *
	 * @param string $name Method name
	 * @param array  $args Method arguments
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		if ( method_exists( $this->core, $name ) ) {
			return call_user_func_array( array( $this->core, $name ), $args );
		}
		trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $name . '()', E_USER_ERROR );
	}
}
