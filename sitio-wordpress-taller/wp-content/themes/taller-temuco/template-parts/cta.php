<?php
/**
 * Llamado a la acción final (reutilizado en Home y páginas de servicio).
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

$service = isset( $args['service'] ) ? $args['service'] : '';
$msg     = $service ? sprintf( 'Hola, quiero cotizar el servicio de %s para mi vehículo.', $service ) : '';
?>
<section class="cta-band">
	<div class="container cta-inner">
		<div>
			<h2><?php echo esc_html( tt_opt( 'final_title' ) ); ?></h2>
			<p><?php echo esc_html( tt_opt( 'final_text' ) ); ?></p>
		</div>
		<div class="cta-buttons">
			<a class="btn btn-light btn-lg" href="<?php echo esc_url( tt_booking_url() ); ?>" data-track="booking_cta"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php esc_html_e( 'Agendar hora', 'taller-temuco' ); ?></a>
			<a class="btn btn-wa btn-lg" href="<?php echo esc_url( tt_whatsapp_url( $msg ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp_cta"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a>
		</div>
	</div>
</section>
