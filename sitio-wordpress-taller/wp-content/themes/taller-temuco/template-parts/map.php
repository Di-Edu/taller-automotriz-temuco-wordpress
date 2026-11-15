<?php
/**
 * Mapa liviano: muestra un botón y solo carga el iframe de Google Maps al hacer clic
 * (ahorra ~500 KB en la carga inicial y mejora la velocidad).
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

$q   = rawurlencode( tt_opt( 'map_query' ) ? tt_opt( 'map_query' ) : tt_opt( 'address' ) );
$src = 'https://www.google.com/maps?q=' . $q . '&output=embed';
?>
<div class="map" data-map-src="<?php echo esc_url( $src ); ?>">
	<button type="button" class="map-load">
		<?php echo tt_icon( 'pin' ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'Ver mapa', 'taller-temuco' ); ?></span>
	</button>
	<a class="map-directions" href="<?php echo esc_url( 'https://www.google.com/maps/dir/?api=1&destination=' . $q ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Cómo llegar', 'taller-temuco' ); ?></a>
</div>
