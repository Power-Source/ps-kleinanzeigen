<?php
/**
* The template for displaying Taxonomy pages.
*
* Learn more: http://codex.wordpress.org/Template_Hierarchy
*
* @package Classifieds
* @subpackage Taxonomy
* @since Classifieds 2.0
*/

get_header(); 

?>

<div id="container">
	<div id="content" role="main">

		<?php /* Legacy compatibility marker */ ?>
		<?php global $bp; if ( isset( $bp ) ): ?>
		<div class="cf-padder">
			<?php endif; ?>

			<h1 class="page-title"><?php _e( 'Kleinanzeigen', CF_TEXT_DOMAIN ); ?> / <?php echo get_query_var('taxonomy'); ?> / <?php echo get_query_var('term'); ?></h1>

			<?php
			global $Classifieds_Core;
			load_template( $Classifieds_Core->custom_classifieds_template( 'loop-taxonomy' ) );?>

			<?php /* Legacy compatibility marker */ ?>
			<?php if ( isset( $bp ) ): ?>
		</div>
		<?php endif; ?>

	</div><!-- #content -->

	<?php /* Legacy compatibility marker */ ?>
	<?php if ( isset( $bp ) ): ?>
	<?php locate_template( array( 'sidebar.php' ), true ); ?>
	<?php endif; ?>

</div><!-- #container -->

<?php /* Legacy compatibility marker */ ?>
<?php if ( !isset( $bp ) ): ?>
<?php get_sidebar(); ?>
<?php endif; ?>

<?php get_footer(); ?>
