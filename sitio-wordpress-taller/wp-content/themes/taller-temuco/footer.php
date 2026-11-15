<?php
/**
 * Pie de página + botón flotante de WhatsApp + barra de acción en móvil.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div>
			<p class="footer-brand"><?php echo esc_html( tt_opt( 'business_name' ) ); ?></p>
			<p><?php echo esc_html( tt_opt( 'footer_text' ) ); ?></p>
			<p class="social">
				<?php if ( tt_opt( 'instagram' ) ) : ?><a href="<?php echo esc_url( tt_opt( 'instagram' ) ); ?>" rel="noopener" target="_blank">Instagram</a><?php endif; ?>
				<?php if ( tt_opt( 'facebook' ) ) : ?><a href="<?php echo esc_url( tt_opt( 'facebook' ) ); ?>" rel="noopener" target="_blank">Facebook</a><?php endif; ?>
				<?php if ( tt_opt( 'google_reviews' ) ) : ?><a href="<?php echo esc_url( tt_opt( 'google_reviews' ) ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Reseñas en Google', 'taller-temuco' ); ?></a><?php endif; ?>
			</p>
		</div>
		<div>
			<h3 class="footer-title"><?php esc_html_e( 'Servicios', 'taller-temuco' ); ?></h3>
			<ul class="footer-links">
				<?php foreach ( tt_services() as $s ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( get_the_title( $s ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<h3 class="footer-title"><?php esc_html_e( 'Contacto', 'taller-temuco' ); ?></h3>
			<ul class="footer-links">
				<li><?php echo tt_icon( 'pin' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'address' ) ); ?></li>
				<li><?php echo tt_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'hours' ) ); ?></li>
				<li><a href="<?php echo esc_url( tt_tel_href() ); ?>"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'phone' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a></li>
				<?php if ( tt_opt( 'email' ) ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( tt_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( tt_opt( 'email' ) ) ); ?></a></li><?php endif; ?>
			</ul>
		</div>
		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div><?php dynamic_sidebar( 'footer-1' ); ?></div>
		<?php endif; ?>
	</div>
	<div class="container footer-bottom">
		<span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . tt_opt( 'business_name' ) ); ?> · Temuco, Chile</span>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'footer-menu',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);
		?>
	</div>
</footer>

<?php if ( tt_opt( 'whatsapp_show' ) && tt_whatsapp_number() ) : ?>
	<a class="wa-float" href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Escríbenos por WhatsApp', 'taller-temuco' ); ?>" data-track="whatsapp_float">
		<?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?>
		<span class="wa-float-label"><?php esc_html_e( '¿Cotizamos?', 'taller-temuco' ); ?></span>
	</a>
<?php endif; ?>

<nav class="mobile-cta" aria-label="<?php esc_attr_e( 'Acciones rápidas', 'taller-temuco' ); ?>">
	<a href="<?php echo esc_url( tt_tel_href() ); ?>" data-track="call_bar"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?><span><?php esc_html_e( 'Llamar', 'taller-temuco' ); ?></span></a>
	<a href="<?php echo esc_url( tt_whatsapp_url() ); ?>" target="_blank" rel="noopener" data-track="whatsapp_bar"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?><span>WhatsApp</span></a>
	<a href="<?php echo esc_url( tt_booking_url() ); ?>" class="is-primary" data-track="booking_bar"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?><span><?php esc_html_e( 'Agendar', 'taller-temuco' ); ?></span></a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
