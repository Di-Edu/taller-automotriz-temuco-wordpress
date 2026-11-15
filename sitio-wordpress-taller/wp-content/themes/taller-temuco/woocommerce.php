<?php
/**
 * Contenedor de las páginas de WooCommerce (tienda, categorías, producto) dentro del diseño del tema.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-hero page-hero-shop">
	<div class="container">
		<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
			<?php
			woocommerce_breadcrumb(
				array(
					'wrap_before' => '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Migas de pan', 'taller-temuco' ) . '">',
					'wrap_after'  => '</nav>',
					'delimiter'   => ' <span aria-hidden="true">/</span> ',
				)
			);
			?>
		<?php endif; ?>
		<?php if ( ! is_product() ) : ?>
			<h1><?php woocommerce_page_title(); ?></h1>
			<?php if ( is_shop() ) : ?>
				<p class="page-hero-text"><?php esc_html_e( 'Repuestos, lubricantes y packs de servicio. Compra online y retira en tu sucursal más cercana.', 'taller-temuco' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</header>

<div class="section section-shop">
	<div class="container woocommerce-wrap">
		<?php woocommerce_content(); ?>
	</div>
</div>
<?php
get_footer();
