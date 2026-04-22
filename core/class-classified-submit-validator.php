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

	/**
	 * Validate required parent/child selections for hierarchical classifieds taxonomies.
	 *
	 * Parent is always required. Child is required when the chosen parent has children.
	 *
	 * @param array $request
	 * @return array{valid: bool, message: string}
	 */
	public function validate_hierarchical_taxonomy_selection( $request ) {
		$taxonomies = get_object_taxonomies( 'classifieds', 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( $taxonomy->hierarchical ) ) {
				continue;
			}

			$tax_name  = (string) $taxonomy->name;
			$tax_label = ! empty( $taxonomy->labels->name ) ? (string) $taxonomy->labels->name : $tax_name;
			$submitted = isset( $request['tax_input'][ $tax_name ] ) ? (array) $request['tax_input'][ $tax_name ] : array();
			$term_ids  = array_values( array_unique( array_filter( array_map( 'absint', $submitted ) ) ) );

			if ( empty( $term_ids ) ) {
				return array(
					'valid'   => false,
					'message' => sprintf( __( 'Bitte waehle eine Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
				);
			}

			$parent_ids = array();
			$child_ids  = array();
			$child_map  = array();

			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, $tax_name );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}

				if ( (int) $term->parent === 0 ) {
					$parent_ids[] = (int) $term->term_id;
				} else {
					$child_ids[] = (int) $term->term_id;
					$child_map[ (int) $term->term_id ] = (int) $term->parent;
				}
			}

			$parent_ids = array_values( array_unique( $parent_ids ) );
			$child_ids  = array_values( array_unique( $child_ids ) );

			if ( 1 !== count( $parent_ids ) ) {
				return array(
					'valid'   => false,
					'message' => sprintf( __( 'Bitte waehle genau eine Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
				);
			}

			if ( count( $child_ids ) > 1 ) {
				return array(
					'valid'   => false,
					'message' => sprintf( __( 'Bitte waehle genau eine Unterkategorie in %s.', $this->core->text_domain ), $tax_label ),
				);
			}

			$selected_parent_id = (int) $parent_ids[0];
			$has_children = ! empty(
				get_terms(
					array(
						'taxonomy'   => $tax_name,
						'hide_empty' => false,
						'parent'     => $selected_parent_id,
						'fields'     => 'ids',
						'number'     => 1,
					)
				)
			);

			if ( $has_children && 1 !== count( $child_ids ) ) {
				return array(
					'valid'   => false,
					'message' => sprintf( __( 'Bitte waehle eine Unterkategorie in %s.', $this->core->text_domain ), $tax_label ),
				);
			}

			if ( 1 === count( $child_ids ) ) {
				$selected_child_id = (int) $child_ids[0];
				$child_parent_id   = isset( $child_map[ $selected_child_id ] ) ? (int) $child_map[ $selected_child_id ] : 0;

				if ( $child_parent_id !== $selected_parent_id ) {
					return array(
						'valid'   => false,
						'message' => sprintf( __( 'Unterkategorie passt nicht zur gewaehlten Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
					);
				}
			}
		}

		return array(
			'valid'   => true,
			'message' => '',
		);
	}

	/**
	 * Validate required frontend fields and return field-specific error messages.
	 *
	 * @param array $request
	 * @param int   $post_id
	 * @return array{valid: bool, errors: array<string, string>}
	 */
	public function validate_frontend_required_fields( $request, $post_id = 0 ) {
		$errors = array();

		$title = isset( $request['classified_data']['post_title'] ) ? trim( (string) $request['classified_data']['post_title'] ) : '';
		if ( '' === $title ) {
			$errors['post_title'] = __( 'Bitte gib einen Titel ein.', $this->core->text_domain );
		}

		$content_raw = isset( $request['classified_data']['post_content'] ) ? (string) $request['classified_data']['post_content'] : '';
		$content     = trim( wp_strip_all_tags( $content_raw ) );
		if ( '' === $content ) {
			$errors['post_content'] = __( 'Bitte gib eine Beschreibung ein.', $this->core->text_domain );
		}

		$cost_raw = isset( $request['cost'] ) ? (string) $request['cost'] : ( isset( $request['_cf_cost'] ) ? (string) $request['_cf_cost'] : '' );
		$cost_val = trim( str_replace( ',', '.', $cost_raw ) );
		if ( '' === $cost_val || ! is_numeric( $cost_val ) || (float) $cost_val <= 0 ) {
			$errors['cost'] = __( 'Bitte gib einen gueltigen Preis ein.', $this->core->text_domain );
		}

		$required_taxonomies = array( 'kleinenanzeigen-cat', 'kleinanzeigen-region' );
		foreach ( $required_taxonomies as $tax_name ) {
			if ( ! taxonomy_exists( $tax_name ) ) {
				continue;
			}

			$tax_result = $this->validate_single_hierarchical_taxonomy( $request, $tax_name );
			if ( ! $tax_result['valid'] ) {
				$errors[ 'tax_' . $tax_name ] = $tax_result['message'];
			}
		}

		$post_status = isset( $request['classified_data']['post_status'] ) ? sanitize_key( (string) $request['classified_data']['post_status'] ) : 'draft';
		if ( 'publish' === $post_status ) {
			$has_feature_image_upload = isset( $_FILES['feature_image']['name'] ) && ! empty( $_FILES['feature_image']['name'] );
			$has_gallery_upload = false;
			if ( isset( $_FILES['feature_gallery']['name'] ) && is_array( $_FILES['feature_gallery']['name'] ) ) {
				foreach ( $_FILES['feature_gallery']['name'] as $gallery_name ) {
					if ( ! empty( $gallery_name ) ) {
						$has_gallery_upload = true;
						break;
					}
				}
			}

			$has_existing_featured = $post_id > 0 ? has_post_thumbnail( $post_id ) : false;
			$existing_gallery_ids  = $post_id > 0 ? get_post_meta( $post_id, '_cf_gallery_ids', true ) : array();
			if ( ! is_array( $existing_gallery_ids ) ) {
				$existing_gallery_ids = array();
			}
			$has_existing_gallery = ! empty( array_filter( array_map( 'absint', $existing_gallery_ids ) ) );

			if ( ! $has_feature_image_upload && ! $has_gallery_upload && ! $has_existing_featured && ! $has_existing_gallery ) {
				$errors['feature_image'] = __( 'Bitte lade mindestens ein Bild hoch, um zu veroeffentlichen.', $this->core->text_domain );
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Validate one hierarchical taxonomy selection (exactly one parent and child if parent has children).
	 *
	 * @param array  $request
	 * @param string $tax_name
	 * @return array{valid: bool, message: string}
	 */
	private function validate_single_hierarchical_taxonomy( $request, $tax_name ) {
		$taxonomy = get_taxonomy( $tax_name );
		$tax_label = ( $taxonomy && ! empty( $taxonomy->labels->name ) ) ? (string) $taxonomy->labels->name : $tax_name;

		$submitted = isset( $request['tax_input'][ $tax_name ] ) ? (array) $request['tax_input'][ $tax_name ] : array();
		$term_ids  = array_values( array_unique( array_filter( array_map( 'absint', $submitted ) ) ) );

		if ( empty( $term_ids ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf( __( 'Bitte waehle eine Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
			);
		}

		$parent_ids = array();
		$child_ids  = array();
		$child_map  = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $tax_name );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			if ( (int) $term->parent === 0 ) {
				$parent_ids[] = (int) $term->term_id;
			} else {
				$child_ids[] = (int) $term->term_id;
				$child_map[ (int) $term->term_id ] = (int) $term->parent;
			}
		}

		$parent_ids = array_values( array_unique( $parent_ids ) );
		$child_ids  = array_values( array_unique( $child_ids ) );

		if ( 1 !== count( $parent_ids ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf( __( 'Bitte waehle genau eine Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
			);
		}

		if ( count( $child_ids ) > 1 ) {
			return array(
				'valid'   => false,
				'message' => sprintf( __( 'Bitte waehle genau eine Unterkategorie in %s.', $this->core->text_domain ), $tax_label ),
			);
		}

		$selected_parent_id = (int) $parent_ids[0];
		$has_children = ! empty(
			get_terms(
				array(
					'taxonomy'   => $tax_name,
					'hide_empty' => false,
					'parent'     => $selected_parent_id,
					'fields'     => 'ids',
					'number'     => 1,
				)
			)
		);

		if ( $has_children && 1 !== count( $child_ids ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf( __( 'Bitte waehle eine Unterkategorie in %s.', $this->core->text_domain ), $tax_label ),
			);
		}

		if ( 1 === count( $child_ids ) ) {
			$selected_child_id = (int) $child_ids[0];
			$child_parent_id   = isset( $child_map[ $selected_child_id ] ) ? (int) $child_map[ $selected_child_id ] : 0;

			if ( $child_parent_id !== $selected_parent_id ) {
				return array(
					'valid'   => false,
					'message' => sprintf( __( 'Unterkategorie passt nicht zur gewaehlten Hauptkategorie in %s.', $this->core->text_domain ), $tax_label ),
				);
			}
		}

		return array(
			'valid'   => true,
			'message' => '',
		);
	}
}
