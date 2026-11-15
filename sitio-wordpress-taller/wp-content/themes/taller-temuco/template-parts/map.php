<?php
/**
 * Mapa interactivo de sucursales (OpenStreetMap + Leaflet, sin API key ni costo).
 * El mapa se carga solo cuando el visitante llega a esta sección, para no afectar la velocidad.
 * Al lado, la lista de sucursales con "Cómo llegar" (Google Maps), Waze, WhatsApp y llamar.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

$branches = tt_branches();
$points   = array();
foreach ( $branches as $i => $b ) {
	if ( null !== $b['lat'] ) {
		$points[] = array(
			'i'    => $i,
			'name' => $b['name'],
			'addr' => $b['full_address'],
			'lat'  => $b['lat'],
			'lng'  => $b['lng'],
			'dir'  => $b['directions'],
		);
	}
}
$multi = count( $branches ) > 1;
?>
<div class="branches<?php echo $multi ? ' is-multi' : ''; ?>">
	<div class="branch-map"
		data-points="<?php echo esc_attr( wp_json_encode( $points ) ); ?>"
		data-leaflet-css="<?php echo esc_url( TT_LEAFLET_CSS ); ?>"
		data-leaflet-js="<?php echo esc_url( TT_LEAFLET_JS ); ?>"
		data-leaflet-css-sri="<?php echo esc_attr( TT_LEAFLET_CSS_SRI ); ?>"
		data-leaflet-js-sri="<?php echo esc_attr( TT_LEAFLET_JS_SRI ); ?>"
		role="region" aria-label="<?php esc_attr_e( 'Mapa de ubicación', 'taller-temuco' ); ?>">
		<div class="branch-map-placeholder"><?php echo tt_icon( 'pin' ); // phpcs:ignore ?> <?php esc_html_e( 'Cargando mapa…', 'taller-temuco' ); ?></div>
	</div>

	<ul class="branch-list">
		<?php foreach ( $branches as $i => $b ) : ?>
			<li class="branch" data-i="<?php echo (int) $i; ?>">
				<?php if ( $multi || $b['id'] ) : ?>
					<h3 class="branch-name">
						<?php if ( null !== $b['lat'] ) : ?>
							<button type="button" class="branch-focus" data-i="<?php echo (int) $i; ?>" title="<?php esc_attr_e( 'Ver en el mapa', 'taller-temuco' ); ?>"><?php echo tt_icon( 'pin' ); // phpcs:ignore ?> <?php echo esc_html( $b['name'] ); ?></button>
						<?php else : ?>
							<?php echo esc_html( $b['name'] ); ?>
						<?php endif; ?>
					</h3>
				<?php endif; ?>
				<p class="branch-addr"><?php echo esc_html( $b['full_address'] ); ?></p>
				<p class="branch-meta"><?php echo tt_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( $b['hours'] ); ?></p>
				<div class="branch-actions">
					<a class="btn btn-primary btn-sm" href="<?php echo esc_url( $b['directions'] ); ?>" target="_blank" rel="noopener" data-track="directions"><?php echo tt_icon( 'arrow' ); // phpcs:ignore ?> <?php esc_html_e( 'Cómo llegar', 'taller-temuco' ); ?></a>
					<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( $b['waze'] ); ?>" target="_blank" rel="noopener" data-track="waze">Waze</a>
					<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( $b['tel'] ); ?>" data-track="call_branch"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?> <?php esc_html_e( 'Llamar', 'taller-temuco' ); ?></a>
					<a class="btn btn-wa btn-sm" href="<?php echo esc_url( $b['wa'] ); ?>" target="_blank" rel="noopener" data-track="whatsapp_branch"><?php echo tt_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
