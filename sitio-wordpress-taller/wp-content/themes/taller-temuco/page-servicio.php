<?php
/**
 * Template Name: Página de servicio (SEO local)
 *
 * Estructura pensada para posicionar "servicio + Temuco" y convertir:
 * H1 con el servicio, contenido editable, barra lateral fija con CTA (agendar / WhatsApp con
 * mensaje del servicio / llamar), otros servicios (enlazado interno) y CTA final.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$service_name = get_the_title();
	$wa_msg       = sprintf( 'Hola, quiero cotizar el servicio de %s para mi vehículo.', $service_name );
	?>
	<header class="page-hero page-hero-service">
		<div class="container">
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<p class="kicker"><?php esc_html_e( 'Servicio en Temuco', 'taller-temuco' ); ?></p>
			<h1><?php echo esc_html( $service_name ); ?> <span class="h1-suffix"><?php esc_html_e( 'en Temuco', 'taller-temuco' ); ?></span></h1>
			<?php if ( has_excerpt() ) : ?><p class="page-hero-text"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="hero-ctas">
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( add_query_arg( 'servicio', get_the_ID(), tt_booking_url() ) ); ?>" data-track="booking_service_hero"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php esc_html_e( 'Agendar este servicio', 'taller-temuco' ); ?></a>
				<a class="btn btn-wa btn-lg" href="<?php echo esc_url( tt_whatsapp_url( $wa_msg ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp_service_hero"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> <?php esc_html_e( 'Cotizar por WhatsApp', 'taller-temuco' ); ?></a>
			</div>
		</div>
	</header>

	<div class="section">
		<div class="container with-sidebar">
			<article class="entry-content">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="service-image"><?php the_post_thumbnail( 'tt-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure>
				<?php endif; ?>
				<?php the_content(); ?>
			</article>

			<aside class="sidebar">
				<div class="sidebar-card">
					<p class="sidebar-title"><?php esc_html_e( 'Agenda tu hora', 'taller-temuco' ); ?></p>
					<p><?php esc_html_e( 'Elige día y horario online. Te confirmamos por WhatsApp.', 'taller-temuco' ); ?></p>
					<a class="btn btn-primary btn-block" href="<?php echo esc_url( add_query_arg( 'servicio', get_the_ID(), tt_booking_url() ) ); ?>" data-track="booking_sidebar"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php esc_html_e( 'Agendar', 'taller-temuco' ); ?></a>
					<a class="btn btn-wa btn-block" href="<?php echo esc_url( tt_whatsapp_url( $wa_msg ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp_sidebar"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a>
					<a class="btn btn-ghost btn-block" href="<?php echo esc_url( tt_tel_href() ); ?>" data-track="call_sidebar"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'phone' ) ); ?></a>
					<ul class="sidebar-list">
						<li><?php echo tt_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'hours' ) ); ?></li>
						<li><?php echo tt_icon( 'pin' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'address' ) ); ?></li>
					</ul>
				</div>

				<div class="sidebar-card">
					<p class="sidebar-title"><?php esc_html_e( 'Otros servicios', 'taller-temuco' ); ?></p>
					<ul class="other-services">
						<?php
						foreach ( tt_services() as $s ) :
							if ( $s->ID === get_the_ID() ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( get_the_title( $s ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
		</div>
	</div>
	<?php
	get_template_part( 'template-parts/cta', null, array( 'service' => $service_name ) );
endwhile;

get_footer();
