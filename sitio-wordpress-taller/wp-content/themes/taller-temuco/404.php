<?php
/**
 * Página 404: en vez de perder la visita, ofrece servicios y contacto.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-hero">
	<div class="container">
		<h1><?php esc_html_e( 'No encontramos esta página', 'taller-temuco' ); ?></h1>
		<p class="page-hero-text"><?php esc_html_e( 'Pero sí podemos ayudarte con tu vehículo. Revisa nuestros servicios o escríbenos.', 'taller-temuco' ); ?></p>
		<div class="hero-ctas">
			<a class="btn btn-primary btn-lg" href="<?php echo esc_url( tt_booking_url() ); ?>"><?php esc_html_e( 'Agendar hora', 'taller-temuco' ); ?></a>
			<a class="btn btn-wa btn-lg" href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener">WhatsApp</a>
		</div>
	</div>
</header>
<div class="section">
	<div class="container">
		<div class="cards">
			<?php foreach ( tt_services() as $s ) : ?>
				<a class="card service-card" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
					<h2 class="h3"><?php echo esc_html( get_the_title( $s ) ); ?></h2>
					<span class="card-link"><?php esc_html_e( 'Ver servicio', 'taller-temuco' ); ?> <?php echo tt_icon( 'arrow' ); // phpcs:ignore ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php
get_footer();
