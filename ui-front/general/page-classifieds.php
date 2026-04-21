<?php
/**
* The template for displaying Classifieds Archive page.
* You can override this file in your active theme.
*
* @package Classifieds
* @subpackage UI Front
* @since Classifieds 2.0
*/

global $bp, $post, $wp_query, $paged;

$options = $this->get_options( 'general' );

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

remove_filter( 'the_title', array( &$this, 'page_title_output' ), 10 , 2 );
remove_filter('the_content', array(&$this, 'classifieds_content'));

$search_text  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$min_price_in = isset( $_GET['min_price'] ) ? wp_unslash( $_GET['min_price'] ) : '';
$max_price_in = isset( $_GET['max_price'] ) ? wp_unslash( $_GET['max_price'] ) : '';
$cat_slug     = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : '';
$region_slug  = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$sort         = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'newest';

$min_price = ( '' !== $min_price_in ) ? floatval( $this->normalize_price_value( $min_price_in ) ) : '';
$max_price = ( '' !== $max_price_in ) ? floatval( $this->normalize_price_value( $max_price_in ) ) : '';

if ( '' !== $min_price || '' !== $max_price || in_array( $sort, array( 'price_asc', 'price_desc' ), true ) ) {
	$this->ensure_cost_numeric_index();
}

$query_args = array(
'paged' => $paged,
'post_status' => 'publish',
'post_type' => 'classifieds',
//'author' => get_query_var('author'),
);

if ( '' !== $search_text ) {
	$query_args['s'] = $search_text;
}

$tax_query = array();
if ( '' !== $cat_slug ) {
	$tax_query[] = array(
		'taxonomy' => 'kleinenanzeigen-cat',
		'field'    => 'slug',
		'terms'    => $cat_slug,
	);
}
if ( '' !== $region_slug ) {
	$tax_query[] = array(
		'taxonomy' => 'kleinanzeigen-region',
		'field'    => 'slug',
		'terms'    => $region_slug,
	);
}

if ( ! empty( $tax_query ) ) {
	$query_args['tax_query'] = $tax_query;
}

$meta_query = array();
if ( '' !== $min_price ) {
	$meta_query[] = array(
		'key'     => '_cf_cost_num',
		'value'   => $min_price,
		'compare' => '>=',
		'type'    => 'NUMERIC',
	);
}

if ( '' !== $max_price ) {
	$meta_query[] = array(
		'key'     => '_cf_cost_num',
		'value'   => $max_price,
		'compare' => '<=',
		'type'    => 'NUMERIC',
	);
}

if ( ! empty( $meta_query ) ) {
	$query_args['meta_query'] = $meta_query;
}

switch ( $sort ) {
	case 'price_asc':
		$query_args['meta_key'] = '_cf_cost_num';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'ASC';
		break;
	case 'price_desc':
		$query_args['meta_key'] = '_cf_cost_num';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

//setup taxonomy if applicable
$tax_key = (empty($wp_query->query_vars['taxonomy'])) ? '' : $wp_query->query_vars['taxonomy'];
$taxonomies = array_values(get_object_taxonomies($query_args['post_type'], 'names') );

if ( in_array($tax_key, $taxonomies) ) {
	if ( empty( $query_args['tax_query'] ) ) {
		$query_args['tax_query'] = array();
	}
	$query_args['tax_query'][] = array(
		'taxonomy' => $tax_key,
		'field'    => 'slug',
		'terms'    => get_query_var( $tax_key),
	);
}

query_posts($query_args);

if ( is_object( $wp_query ) && ! empty( $wp_query->posts ) ) {
	$premium_meta_keys = apply_filters(
		'cf_premium_meta_keys',
		array(
			'_cf_is_premium',
			'_cf_premium',
			'cf_is_premium',
			'is_premium',
		)
	);

	$is_premium_post = function( $post_id ) use ( $premium_meta_keys ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( method_exists( $this, 'is_premium_post' ) ) {
			return (bool) $this->is_premium_post( $post_id );
		}

		foreach ( (array) $premium_meta_keys as $meta_key ) {
			$raw = get_post_meta( $post_id, (string) $meta_key, true );
			$normalized = strtolower( trim( (string) $raw ) );
			if ( in_array( $normalized, array( '1', 'yes', 'true', 'premium' ), true ) ) {
				return true;
			}
		}

		return false;
	};

	$is_featured_post = function( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( method_exists( $this, 'is_featured' ) ) {
			return (bool) $this->is_featured( $post_id );
		}

		return '1' === (string) get_post_meta( $post_id, '_cf_is_featured', true );
	};

	$has_premium_posts = false;
	foreach ( $wp_query->posts as $candidate_post ) {
		if ( isset( $candidate_post->ID ) && $is_premium_post( $candidate_post->ID ) ) {
			$has_premium_posts = true;
			break;
		}
	}

	usort(
		$wp_query->posts,
		function ( $a, $b ) use ( $is_premium_post, $is_featured_post, $has_premium_posts ) {
			$a_premium = $is_premium_post( $a->ID ) ? 1 : 0;
			$b_premium = $is_premium_post( $b->ID ) ? 1 : 0;
			if ( $a_premium !== $b_premium ) {
				return ( $a_premium < $b_premium ) ? 1 : -1;
			}

			if ( ! $has_premium_posts ) {
				$a_featured = $is_featured_post( $a->ID ) ? 1 : 0;
				$b_featured = $is_featured_post( $b->ID ) ? 1 : 0;
				if ( $a_featured !== $b_featured ) {
					return ( $a_featured < $b_featured ) ? 1 : -1;
				}
			}

			$a_date = strtotime( ! empty( $a->post_date_gmt ) ? $a->post_date_gmt : $a->post_date );
			$b_date = strtotime( ! empty( $b->post_date_gmt ) ? $b->post_date_gmt : $b->post_date );
			if ( $a_date === $b_date ) {
				return 0;
			}

			return ( $a_date < $b_date ) ? 1 : -1;
		}
	);
}


load_template( $this->custom_classifieds_template( 'loop-taxonomy' ) );

if(is_object($wp_query)) $wp_query->post_count = 0; 
