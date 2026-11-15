<?php
/**
 * Template Name: Contacto
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<header class="page-hero">
		<div class="container">
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container narrow">
			<div class="entry-content"><?php the_content(); ?></div>
			<ul class="contact-list contact-list-lg">
				<li><a href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener" data-track="whatsapp_contact"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a></li>
				<li><a href="<?php echo esc_url( tt_tel_href() ); ?>" data-track="call_contact"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'phone' ) ); ?></a></li>
				<?php if ( tt_opt( 'email' ) ) : ?>
					<li><a href="mailto:<?php echo esc_attr( antispambot( tt_opt( 'email' ) ) ); ?>">✉ <?php echo esc_html( antispambot( tt_opt( 'email' ) ) ); ?></a></li>
				<?php endif; ?>
			</ul>
			<p><a class="btn btn-primary btn-lg" href="<?php echo esc_url( tt_booking_url() ); ?>"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php esc_html_e( 'Agendar hora online', 'taller-temuco' ); ?></a></p>
		</div>
		<div class="container" id="ubicacion">
			<h2><?php echo count( tt_branches() ) > 1 ? esc_html__( 'Nuestras sucursales', 'taller-temuco' ) : esc_html__( 'Cómo llegar', 'taller-temuco' ); ?></h2>
			<?php get_template_part( 'template-parts/map' ); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
