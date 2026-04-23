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
	 * Shortcode: [cf_regions_map preview="3" zoom="6" regions="1,2,3"]
	 *
	 * @param array $atts
	 * @param mixed $content
	 * @return string
	 */
	public function regions_map_sc( $atts, $content = null ) {
		if ( ! class_exists( 'AgmMapModel' ) || ! class_exists( 'AgmMarkerReplacer' ) ) {
			return '';
		}

		$options = (array) $this->core->get_options( 'maps' );
		$enabled = isset( $options['maps_enable_regions_map'] ) ? (int) $options['maps_enable_regions_map'] : 1;
		if ( 1 !== $enabled ) {
			return '';
		}

		$default_preview = isset( $options['maps_region_preview_count'] ) ? (int) $options['maps_region_preview_count'] : 3;
		$default_preview = max( 1, min( 12, $default_preview ) );
		$default_zoom = isset( $options['maps_default_zoom'] ) ? (int) $options['maps_default_zoom'] : 6;
		$default_zoom = max( 2, min( 18, $default_zoom ) );
		$auto_geocode = isset( $options['maps_auto_geocode_regions'] ) ? (int) $options['maps_auto_geocode_regions'] : 1;
		$geocode_hint = isset( $options['maps_geocode_hint'] ) ? (string) $options['maps_geocode_hint'] : '';

		$atts = shortcode_atts(
			array(
				'taxonomy' => 'kleinanzeigen-region',
				'preview'  => (string) $default_preview,
				'zoom'     => (string) $default_zoom,
				'regions'  => '',
			),
			(array) $atts
		);

		$taxonomy = sanitize_key( (string) $atts['taxonomy'] );
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$preview_limit = max( 1, min( 12, (int) $atts['preview'] ) );
		$zoom = max( 2, min( 18, (int) $atts['zoom'] ) );

		$term_ids = array();
		if ( ! empty( $atts['regions'] ) ) {
			$term_ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $atts['regions'] ) ) ) ) );
		}

		$terms_query = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		);
		if ( ! empty( $term_ids ) ) {
			$terms_query['include'] = $term_ids;
		}

		$terms = get_terms( $terms_query );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$map_model_class = 'AgmMapModel';
		$model = new $map_model_class();
		$map = $model->get_map_defaults();
		$map['defaults'] = $model->get_map_defaults();
		$map['id'] = 'cf-regions-map-' . md5( (string) microtime() . wp_rand() );
		$map['show_map'] = 1;
		$map['zoom'] = $zoom;
		$map['markers'] = array();

		foreach ( $terms as $term ) {
			if ( ! isset( $term->term_id ) ) {
				continue;
			}

			$term_id = (int) $term->term_id;
			$lat = get_term_meta( $term_id, '_cf_region_lat', true );
			$lng = get_term_meta( $term_id, '_cf_region_lng', true );
			$postcode = trim( (string) get_term_meta( $term_id, '_cf_region_postcode', true ) );
			$city = trim( (string) get_term_meta( $term_id, '_cf_region_city', true ) );

			if ( ( '' === (string) $lat || '' === (string) $lng ) && 1 === $auto_geocode ) {
				$main_location = trim( $postcode . ' ' . $city );
				if ( '' === $main_location ) {
					$main_location = trim( (string) $term->name );
				}
				$address = trim( $main_location . ( '' !== $geocode_hint ? ', ' . $geocode_hint : '' ) );
				if ( '' !== $address ) {
					$result = $model->geocode_address( $address );
					$location = ( is_object( $result ) && isset( $result->geometry ) && isset( $result->geometry->location ) ) ? $result->geometry->location : null;
					if ( is_object( $location ) && isset( $location->lat ) && isset( $location->lng ) ) {
						$lat = (float) $location->lat;
						$lng = (float) $location->lng;
						update_term_meta( $term_id, '_cf_region_lat', $lat );
						update_term_meta( $term_id, '_cf_region_lng', $lng );
					}
				}
			}

			if ( '' === (string) $lat || '' === (string) $lng ) {
				continue;
			}

			$posts = get_posts(
				array(
					'post_type'      => 'classifieds',
					'post_status'    => 'publish',
					'posts_per_page' => $preview_limit,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'tax_query'      => array(
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => array( $term_id ),
						),
					),
					'no_found_rows'  => true,
				)
			);

			if ( empty( $posts ) ) {
				continue;
			}

			$total_count = isset( $term->count ) ? (int) $term->count : count( $posts );
			$term_link = get_term_link( $term_id, $taxonomy );
			if ( is_wp_error( $term_link ) ) {
				$term_link = '';
			}

			$title_html = '<strong>' . esc_html( $term->name ) . '</strong>';
			$count_html = '<p>' . sprintf( esc_html__( '%d Anzeigen in dieser Region', $this->core->text_domain ), $total_count ) . '</p>';

			$list_items = '';
			foreach ( $posts as $classified_post ) {
				$post_title = get_the_title( $classified_post );
				$post_url = get_permalink( $classified_post );
				$list_items .= '<li><a href="' . esc_url( $post_url ) . '">' . esc_html( $post_title ) . '</a></li>';
			}

			$more_html = '';
			if ( $total_count > $preview_limit && ! empty( $term_link ) ) {
				$more_html = '<p><a href="' . esc_url( $term_link ) . '">' . esc_html__( 'Alle Anzeigen anzeigen', $this->core->text_domain ) . '</a></p>';
			}

			$body = $title_html . $count_html . '<ul>' . $list_items . '</ul>' . $more_html;

			$map['markers'][] = array(
				'title'       => $term->name,
				'body'        => $body,
				'icon'        => 'marker.png',
				'position'    => array( (float) $lat, (float) $lng ),
				'disposition' => 'post_marker',
			);
		}

		if ( empty( $map['markers'] ) ) {
			return '';
		}

		$marker_replacer_class = 'AgmMarkerReplacer';
		$codec = new $marker_replacer_class();
		return $codec->create_tag(
			$map,
			array(
				'show_posts'   => 0,
				'show_markers' => 1,
			)
		);
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
