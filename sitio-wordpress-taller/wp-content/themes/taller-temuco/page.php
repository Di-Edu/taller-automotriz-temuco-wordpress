<?php
/**
 * Página genérica. Si es la página "Servicios", agrega la grilla de servicios.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$is_services = ( (int) get_option( 'tt_services_page_id' ) === get_the_ID() );
	?>
	<header class="page-hero">
		<div class="container">
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container narrow entry-content"><?php the_content(); ?></div>

		<?php if ( $is_services ) : ?>
			<div class="container">
				<div class="cards">
					<?php foreach ( tt_services() as $s ) : ?>
						<a class="card service-card" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
							<span class="card-icon"><?php echo tt_icon( 'wrench' ); // phpcs:ignore ?></span>
							<h2 class="h3"><?php echo esc_html( get_the_title( $s ) ); ?></h2>
							<p><?php echo esc_html( wp_trim_words( tt_service_summary( $s ), 22 ) ); ?></p>
							<span class="card-link"><?php esc_html_e( 'Ver servicio', 'taller-temuco' ); ?> <?php echo tt_icon( 'arrow' ); // phpcs:ignore ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_template_part( 'template-parts/cta' );

get_footer();
