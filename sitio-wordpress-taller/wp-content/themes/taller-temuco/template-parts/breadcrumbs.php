<?php
/**
 * Migas de pan (ayudan a la navegación y al SEO; su schema se imprime en inc/seo.php).
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

if ( is_front_page() ) {
	return;
}
$parent = wp_get_post_parent_id( get_the_ID() );
?>
<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Migas de pan', 'taller-temuco' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'taller-temuco' ); ?></a>
	<?php if ( $parent ) : ?>
		<span aria-hidden="true">/</span> <a href="<?php echo esc_url( get_permalink( $parent ) ); ?>"><?php echo esc_html( get_the_title( $parent ) ); ?></a>
	<?php endif; ?>
	<span aria-hidden="true">/</span> <span aria-current="page"><?php the_title(); ?></span>
</nav>
