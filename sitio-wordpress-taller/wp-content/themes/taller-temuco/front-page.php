<?php
/**
 * Home orientado a conversión:
 * 1) Portada con propuesta de valor + 2 CTA (agendar / WhatsApp)  2) Cifras de confianza
 * 3) Servicios  4) Por qué elegirnos  5) Cómo funciona (3 pasos)  6) Testimonios
 * 7) Texto editable de la página Inicio  8) Zona de cobertura + mapa  9) CTA final
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hero_img = tt_opt( 'hero_image' );
?>

<section class="hero<?php echo $hero_img ? ' has-image' : ''; ?>"<?php echo $hero_img ? ' style="--hero-img:url(' . esc_url( $hero_img ) . ')"' : ''; ?>>
	<div class="container hero-inner">
		<div class="hero-copy">
			<?php if ( tt_opt( 'hero_kicker' ) ) : ?><p class="kicker"><?php echo esc_html( tt_opt( 'hero_kicker' ) ); ?></p><?php endif; ?>
			<h1><?php echo esc_html( tt_opt( 'hero_title' ) ); ?></h1>
			<p class="hero-text"><?php echo esc_html( tt_opt( 'hero_text' ) ); ?></p>
			<div class="hero-ctas">
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( tt_booking_url() ); ?>" data-track="booking_hero"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'hero_cta' ) ); ?></a>
				<a class="btn btn-wa btn-lg" href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener" data-track="whatsapp_hero"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> <?php esc_html_e( 'Cotizar por WhatsApp', 'taller-temuco' ); ?></a>
			</div>
			<ul class="hero-trust">
				<li><?php echo tt_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Presupuesto sin compromiso', 'taller-temuco' ); ?></li>
				<li><?php echo tt_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Garantía por escrito', 'taller-temuco' ); ?></li>
				<li><?php echo tt_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Todas las marcas', 'taller-temuco' ); ?></li>
			</ul>
		</div>

		<aside class="hero-card" aria-label="<?php esc_attr_e( 'Agenda rápida', 'taller-temuco' ); ?>">
			<p class="hero-card-title"><?php esc_html_e( '¿Qué necesita tu vehículo?', 'taller-temuco' ); ?></p>
			<ul class="quick-services">
				<?php foreach ( array_slice( tt_services(), 0, 6 ) as $s ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'servicio', $s->ID, tt_booking_url() ) ); ?>" data-track="booking_quick"><?php echo esc_html( get_the_title( $s ) ); ?> <?php echo tt_icon( 'arrow' ); // phpcs:ignore ?></a></li>
				<?php endforeach; ?>
			</ul>
			<p class="hero-card-foot"><?php echo tt_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'hours' ) ); ?></p>
		</aside>
	</div>
</section>

<section class="stats" aria-label="<?php esc_attr_e( 'Cifras', 'taller-temuco' ); ?>">
	<div class="container stats-grid">
		<?php
		for ( $i = 1; $i <= 4; $i++ ) :
			$p = tt_parts( 'stat_' . $i );
			if ( empty( $p[0] ) ) {
				continue;
			}
			?>
			<div class="stat"><strong><?php echo esc_html( $p[0] ); ?></strong><span><?php echo esc_html( isset( $p[1] ) ? $p[1] : '' ); ?></span></div>
		<?php endfor; ?>
	</div>
</section>

<section class="section" id="servicios">
	<div class="container">
		<header class="section-head">
			<p class="kicker"><?php esc_html_e( 'Servicios', 'taller-temuco' ); ?></p>
			<h2><?php esc_html_e( 'Servicios de taller mecánico en Temuco', 'taller-temuco' ); ?></h2>
			<p><?php esc_html_e( 'Todo lo que tu auto, SUV o camioneta necesita, en un solo lugar.', 'taller-temuco' ); ?></p>
		</header>
		<div class="cards">
			<?php foreach ( tt_services() as $s ) : ?>
				<a class="card service-card" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
					<?php if ( has_post_thumbnail( $s ) ) : ?>
						<?php echo get_the_post_thumbnail( $s, 'tt-card', array( 'loading' => 'lazy' ) ); ?>
					<?php else : ?>
						<span class="card-icon"><?php echo tt_icon( 'wrench' ); // phpcs:ignore ?></span>
					<?php endif; ?>
					<h3><?php echo esc_html( get_the_title( $s ) ); ?></h3>
					<p><?php echo esc_html( wp_trim_words( tt_service_summary( $s ), 18 ) ); ?></p>
					<span class="card-link"><?php esc_html_e( 'Ver servicio', 'taller-temuco' ); ?> <?php echo tt_icon( 'arrow' ); // phpcs:ignore ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section-alt">
	<div class="container">
		<header class="section-head">
			<p class="kicker"><?php esc_html_e( 'Por qué elegirnos', 'taller-temuco' ); ?></p>
			<h2><?php esc_html_e( 'Confianza desde el primer diagnóstico', 'taller-temuco' ); ?></h2>
		</header>
		<div class="features">
			<?php
			for ( $i = 1; $i <= 4; $i++ ) :
				$p = tt_parts( 'why_' . $i );
				if ( empty( $p[0] ) ) {
					continue;
				}
				?>
				<div class="feature">
					<span class="feature-icon"><?php echo tt_icon( 'shield' ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $p[0] ); ?></h3>
					<p><?php echo esc_html( isset( $p[1] ) ? $p[1] : '' ); ?></p>
				</div>
			<?php endfor; ?>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<header class="section-head">
			<p class="kicker"><?php esc_html_e( 'Así de simple', 'taller-temuco' ); ?></p>
			<h2><?php esc_html_e( 'Agenda en 3 pasos', 'taller-temuco' ); ?></h2>
		</header>
		<ol class="steps">
			<li><strong><?php esc_html_e( 'Agenda online o por WhatsApp', 'taller-temuco' ); ?></strong><span><?php esc_html_e( 'Elige servicio, día y horario en menos de un minuto.', 'taller-temuco' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Diagnóstico y presupuesto', 'taller-temuco' ); ?></strong><span><?php esc_html_e( 'Revisamos tu vehículo y te enviamos el presupuesto antes de reparar.', 'taller-temuco' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Reparación con garantía', 'taller-temuco' ); ?></strong><span><?php esc_html_e( 'Te avisamos cuando esté listo y te entregamos el detalle del trabajo.', 'taller-temuco' ); ?></span></li>
		</ol>
		<p class="center"><a class="btn btn-primary btn-lg" href="<?php echo esc_url( tt_booking_url() ); ?>" data-track="booking_steps"><?php esc_html_e( 'Quiero agendar', 'taller-temuco' ); ?></a></p>
	</div>
</section>

<?php
if ( function_exists( 'tt_has_woo' ) && tt_has_woo() ) :
	$tt_products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 4,
			'featured' => true,
		)
	);
	if ( ! $tt_products ) {
		$tt_products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 4,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
	}
	if ( $tt_products ) :
		?>
		<section class="section section-alt" id="tienda">
			<div class="container">
				<header class="section-head">
					<p class="kicker"><?php esc_html_e( 'Tienda online', 'taller-temuco' ); ?></p>
					<h2><?php esc_html_e( 'Repuestos y packs de servicio', 'taller-temuco' ); ?></h2>
					<p><?php esc_html_e( 'Compra online y retira en tu sucursal, o déjalo instalado en tu próxima hora.', 'taller-temuco' ); ?></p>
				</header>
				<div class="cards">
					<?php foreach ( $tt_products as $p ) : ?>
						<a class="card product-card" href="<?php echo esc_url( $p->get_permalink() ); ?>">
							<?php
							$img = $p->get_image_id();
							if ( $img ) {
								echo wp_get_attachment_image( $img, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) );
							} else {
								echo '<span class="card-icon">' . tt_icon( 'wrench' ) . '</span>'; // phpcs:ignore
							}
							?>
							<h3><?php echo esc_html( $p->get_name() ); ?></h3>
							<p class="product-card-price"><?php echo wp_kses_post( $p->get_price_html() ); ?></p>
							<p class="product-card-stock <?php echo $p->is_in_stock() ? 'in' : 'out'; ?>"><?php echo $p->is_in_stock() ? esc_html__( 'Disponible', 'taller-temuco' ) : esc_html__( 'Agotado', 'taller-temuco' ); ?></p>
							<span class="card-link"><?php esc_html_e( 'Ver producto', 'taller-temuco' ); ?> <?php echo tt_icon( 'arrow' ); // phpcs:ignore ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<p class="center"><a class="btn btn-primary btn-lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver toda la tienda', 'taller-temuco' ); ?></a></p>
			</div>
		</section>
		<?php
	endif;
endif;
?>

<section class="section section-dark">
	<div class="container">
		<header class="section-head">
			<p class="kicker"><?php esc_html_e( 'Clientes', 'taller-temuco' ); ?></p>
			<h2><?php esc_html_e( 'Lo que dicen quienes ya nos visitaron', 'taller-temuco' ); ?></h2>
		</header>
		<div class="testimonials">
			<?php
			for ( $i = 1; $i <= 3; $i++ ) :
				$p = tt_parts( 'testi_' . $i );
				if ( empty( $p[0] ) ) {
					continue;
				}
				?>
				<figure class="testimonial">
					<div class="stars" aria-label="5 estrellas"><?php echo str_repeat( tt_icon( 'star' ), 5 ); // phpcs:ignore ?></div>
					<blockquote><?php echo esc_html( $p[0] ); ?></blockquote>
					<figcaption><strong><?php echo esc_html( isset( $p[1] ) ? $p[1] : '' ); ?></strong><?php echo isset( $p[2] ) ? ' · ' . esc_html( $p[2] ) : ''; ?></figcaption>
				</figure>
			<?php endfor; ?>
		</div>
		<?php if ( tt_opt( 'google_reviews' ) ) : ?>
			<p class="center"><a class="btn btn-ghost-light" href="<?php echo esc_url( tt_opt( 'google_reviews' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver reseñas en Google', 'taller-temuco' ); ?></a></p>
		<?php endif; ?>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( trim( get_the_content() ) ) :
		?>
		<section class="section">
			<div class="container narrow entry-content"><?php the_content(); ?></div>
		</section>
		<?php
	endif;
endwhile;
?>

<section class="section section-alt" id="ubicacion">
	<div class="container">
		<header class="section-head">
			<p class="kicker"><?php echo count( tt_branches() ) > 1 ? esc_html__( 'Sucursales', 'taller-temuco' ) : esc_html__( 'Ubicación', 'taller-temuco' ); ?></p>
			<h2><?php esc_html_e( 'Encuéntranos en Temuco y alrededores', 'taller-temuco' ); ?></h2>
			<ul class="chips chips-center">
				<?php foreach ( array_filter( array_map( 'trim', explode( ',', (string) tt_opt( 'areas' ) ) ) ) as $a ) : ?>
					<li><?php echo esc_html( $a ); ?></li>
				<?php endforeach; ?>
			</ul>
		</header>
		<?php get_template_part( 'template-parts/map' ); ?>
	</div>
</section>

<?php get_template_part( 'template-parts/cta' ); ?>

<?php
get_footer();
