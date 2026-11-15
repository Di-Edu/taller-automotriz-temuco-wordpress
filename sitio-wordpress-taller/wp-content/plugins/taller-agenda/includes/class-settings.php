<?php
/**
 * Ajustes de la agenda: Citas → Ajustes.
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajustes.
 */
class TTA_Settings {

	const OPTION = 'tta_settings';

	/**
	 * Valores por defecto.
	 */
	public static function defaults() {
		return array(
			'notify_email'  => get_option( 'admin_email' ),
			'days'          => array( '1', '2', '3', '4', '5', '6' ), // 0 = domingo.
			'slots_week'    => "09:00\n10:00\n11:00\n12:00\n14:30\n15:30\n16:30\n17:30",
			'slots_sat'     => "09:00\n10:00\n11:00\n12:00",
			'capacity'      => 2,
			'min_hours'     => 12,
			'max_days'      => 30,
			'blocked'       => '',
			'services'      => "Mantención automotriz\nFrenos\nScanner y diagnóstico electrónico\nSuspensión y dirección\nAlineación y balanceo\nEmbrague y transmisión\nAire acondicionado y climatización\nPre revisión técnica\nOtro / no sé qué tiene",
			'confirm_text'  => '¡Listo! Recibimos tu solicitud. Te confirmaremos la hora por WhatsApp o teléfono a la brevedad.',
			'client_email'  => 1,
		);
	}

	/**
	 * Lee un ajuste.
	 *
	 * @param string $key Clave.
	 */
	public static function get( $key ) {
		$opts = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	/**
	 * Lista de líneas no vacías.
	 *
	 * @param string $key Clave.
	 * @return string[]
	 */
	public static function lines( $key ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) self::get( $key ) ) ) ) );
	}

	/**
	 * Servicios disponibles: si el tema "Taller Temuco" está activo usa sus páginas de servicio,
	 * así el cliente agrega un servicio creando una página y aparece solo en la agenda.
	 *
	 * @return array id/clave => nombre
	 */
	public static function services() {
		$out = array();
		if ( function_exists( 'tt_services' ) ) {
			foreach ( tt_services() as $p ) {
				$out[ (string) $p->ID ] = get_the_title( $p );
			}
		}
		if ( ! $out ) {
			foreach ( self::lines( 'services' ) as $s ) {
				$out[ sanitize_title( $s ) ] = $s;
			}
		} else {
			$out['otro'] = __( 'Otro / no sé qué tiene', 'taller-agenda' );
		}
		return $out;
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Submenú.
	 */
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=tta_cita', __( 'Ajustes de la agenda', 'taller-agenda' ), __( 'Ajustes', 'taller-agenda' ), 'manage_options', 'tta-settings', array( __CLASS__, 'page' ) );
	}

	/**
	 * Registro de la opción.
	 */
	public static function register() {
		register_setting(
			'tta_settings',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitiza.
	 *
	 * @param array $in Entrada.
	 */
	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? $in : array();
		$out = array();

		$out['notify_email'] = sanitize_email( isset( $in['notify_email'] ) ? $in['notify_email'] : '' );
		$out['days']         = isset( $in['days'] ) ? array_values( array_intersect( array_map( 'strval', (array) $in['days'] ), array( '0', '1', '2', '3', '4', '5', '6' ) ) ) : array();

		foreach ( array( 'slots_week', 'slots_sat' ) as $k ) {
			$lines = preg_split( '/\r\n|\r|\n/', isset( $in[ $k ] ) ? (string) $in[ $k ] : '' );
			$valid = array();
			foreach ( $lines as $l ) {
				$l = trim( $l );
				if ( preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $l ) ) {
					$valid[] = str_pad( $l, 5, '0', STR_PAD_LEFT );
				}
			}
			sort( $valid );
			$out[ $k ] = implode( "\n", array_unique( $valid ) );
		}

		$out['capacity']  = max( 1, absint( isset( $in['capacity'] ) ? $in['capacity'] : 1 ) );
		$out['min_hours'] = absint( isset( $in['min_hours'] ) ? $in['min_hours'] : 0 );
		$out['max_days']  = max( 1, absint( isset( $in['max_days'] ) ? $in['max_days'] : 30 ) );

		$blocked = array();
		foreach ( preg_split( '/\r\n|\r|\n/', isset( $in['blocked'] ) ? (string) $in['blocked'] : '' ) as $l ) {
			$l = trim( $l );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $l ) ) {
				$blocked[] = $l;
			}
		}
		$out['blocked']      = implode( "\n", $blocked );
		$out['services']     = sanitize_textarea_field( isset( $in['services'] ) ? $in['services'] : '' );
		$out['confirm_text'] = sanitize_textarea_field( isset( $in['confirm_text'] ) ? $in['confirm_text'] : '' );
		$out['client_email'] = empty( $in['client_email'] ) ? 0 : 1;
		return $out;
	}

	/**
	 * Pantalla de ajustes.
	 */
	public static function page() {
		$o    = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		$name = self::OPTION;
		$days = array( '1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '0' => 'Domingo' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ajustes de la agenda', 'taller-agenda' ); ?></h1>
			<p><?php esc_html_e( 'Para mostrar el formulario en cualquier página, agrega el bloque "Código corto" con:', 'taller-agenda' ); ?> <code>[taller_agenda]</code></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'tta_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="tta_email"><?php esc_html_e( 'Correo que recibe las citas', 'taller-agenda' ); ?></label></th>
						<td><input type="email" id="tta_email" class="regular-text" name="<?php echo esc_attr( $name ); ?>[notify_email]" value="<?php echo esc_attr( $o['notify_email'] ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Días de atención', 'taller-agenda' ); ?></th>
						<td><?php foreach ( $days as $k => $label ) : ?>
							<label style="margin-right:12px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[days][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( (string) $k, (array) $o['days'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?></td></tr>
					<tr><th><label for="tta_sw"><?php esc_html_e( 'Horarios lunes a viernes', 'taller-agenda' ); ?></label></th>
						<td><textarea id="tta_sw" rows="8" cols="12" name="<?php echo esc_attr( $name ); ?>[slots_week]"><?php echo esc_textarea( $o['slots_week'] ); ?></textarea><p class="description"><?php esc_html_e( 'Una hora por línea, formato 24 h (ej: 09:30).', 'taller-agenda' ); ?></p></td></tr>
					<tr><th><label for="tta_ss"><?php esc_html_e( 'Horarios sábado y domingo', 'taller-agenda' ); ?></label></th>
						<td><textarea id="tta_ss" rows="5" cols="12" name="<?php echo esc_attr( $name ); ?>[slots_sat]"><?php echo esc_textarea( $o['slots_sat'] ); ?></textarea></td></tr>
					<tr><th><label for="tta_cap"><?php esc_html_e( 'Vehículos por horario', 'taller-agenda' ); ?></label></th>
						<td><input type="number" min="1" id="tta_cap" name="<?php echo esc_attr( $name ); ?>[capacity]" value="<?php echo esc_attr( $o['capacity'] ); ?>" class="small-text"> <span class="description"><?php esc_html_e( 'Cuántas citas se aceptan en el mismo bloque (ej: número de elevadores).', 'taller-agenda' ); ?></span></td></tr>
					<tr><th><label for="tta_min"><?php esc_html_e( 'Anticipación mínima (horas)', 'taller-agenda' ); ?></label></th>
						<td><input type="number" min="0" id="tta_min" name="<?php echo esc_attr( $name ); ?>[min_hours]" value="<?php echo esc_attr( $o['min_hours'] ); ?>" class="small-text"></td></tr>
					<tr><th><label for="tta_max"><?php esc_html_e( 'Agenda abierta hasta (días)', 'taller-agenda' ); ?></label></th>
						<td><input type="number" min="1" id="tta_max" name="<?php echo esc_attr( $name ); ?>[max_days]" value="<?php echo esc_attr( $o['max_days'] ); ?>" class="small-text"></td></tr>
					<tr><th><label for="tta_bl"><?php esc_html_e( 'Días bloqueados (feriados, vacaciones)', 'taller-agenda' ); ?></label></th>
						<td><textarea id="tta_bl" rows="5" cols="14" name="<?php echo esc_attr( $name ); ?>[blocked]" placeholder="2026-12-25"><?php echo esc_textarea( $o['blocked'] ); ?></textarea><p class="description"><?php esc_html_e( 'Una fecha por línea, formato AAAA-MM-DD.', 'taller-agenda' ); ?></p></td></tr>
					<tr><th><label for="tta_srv"><?php esc_html_e( 'Servicios (si el tema no los define)', 'taller-agenda' ); ?></label></th>
						<td><textarea id="tta_srv" rows="6" class="large-text" name="<?php echo esc_attr( $name ); ?>[services]"><?php echo esc_textarea( $o['services'] ); ?></textarea><p class="description"><?php esc_html_e( 'Con el tema Taller Temuco activo, la lista se toma automáticamente de las páginas hijas de "Servicios".', 'taller-agenda' ); ?></p></td></tr>
					<tr><th><label for="tta_ct"><?php esc_html_e( 'Mensaje al enviar', 'taller-agenda' ); ?></label></th>
						<td><textarea id="tta_ct" rows="3" class="large-text" name="<?php echo esc_attr( $name ); ?>[confirm_text]"><?php echo esc_textarea( $o['confirm_text'] ); ?></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Correo al cliente', 'taller-agenda' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[client_email]" value="1" <?php checked( $o['client_email'] ); ?>> <?php esc_html_e( 'Enviar comprobante al cliente si deja su correo', 'taller-agenda' ); ?></label></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
