<?php
/**
 * Formulario público [taller_agenda].
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formulario.
 */
class TTA_Form {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_shortcode( 'taller_agenda', array( __CLASS__, 'render' ) );
		add_action( 'admin_post_nopriv_tta_submit', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_tta_submit', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Procesa el envío (patrón POST → redirección → GET para evitar reenvíos).
	 */
	public static function handle() {
		$back = isset( $_POST['tta_back'] ) ? esc_url_raw( wp_unslash( $_POST['tta_back'] ) ) : home_url( '/' );
		$back = wp_validate_redirect( $back, home_url( '/' ) );
		$back = remove_query_arg( array( 'tta', 'tta_k' ), $back );

		$fail = function ( $msg, $data = array() ) use ( $back ) {
			$key = wp_generate_password( 12, false );
			set_transient( 'tta_f_' . $key, array( 'msg' => $msg, 'data' => $data ), 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( array( 'tta' => 'error', 'tta_k' => $key ), $back ) . '#agenda' );
			exit;
		};

		if ( ! isset( $_POST['tta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_nonce'] ) ), 'tta_submit' ) ) {
			$fail( __( 'La página expiró. Recárgala e inténtalo de nuevo.', 'taller-agenda' ) );
		}

		// Trampa anti-spam: campo oculto que solo llenan los bots.
		if ( ! empty( $_POST['tta_web'] ) ) {
			wp_safe_redirect( add_query_arg( 'tta', 'ok', $back ) . '#agenda' );
			exit;
		}

		$field = function ( $k, $textarea = false ) {
			if ( ! isset( $_POST[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return '';
			}
			$v = wp_unslash( $_POST[ $k ] ); // phpcs:ignore
			return $textarea ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
		};

		$data = array(
			'nombre'   => $field( 'tta_nombre' ),
			'telefono' => preg_replace( '/[^\d+ ]/', '', $field( 'tta_telefono' ) ),
			'email'    => sanitize_email( $field( 'tta_email' ) ),
			'patente'  => strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $field( 'tta_patente' ) ) ),
			'vehiculo' => $field( 'tta_vehiculo' ),
			'servicio' => $field( 'tta_servicio' ),
			'fecha'    => $field( 'tta_fecha' ),
			'hora'     => $field( 'tta_hora' ),
			'mensaje'  => mb_substr( $field( 'tta_mensaje', true ), 0, 1000 ),
		);

		// Límite: 5 solicitudes por hora por IP.
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$rkey  = 'tta_rl_' . md5( $ip );
		$count = (int) get_transient( $rkey );
		if ( $count >= 5 ) {
			$fail( __( 'Recibimos varias solicitudes desde tu conexión. Escríbenos por WhatsApp para agendar.', 'taller-agenda' ), $data );
		}

		$result = TTA_Booking::create( $data );
		if ( is_wp_error( $result ) ) {
			$fail( $result->get_error_message(), $data );
		}
		set_transient( $rkey, $count + 1, HOUR_IN_SECONDS );

		$key = wp_generate_password( 12, false );
		set_transient(
			'tta_f_' . $key,
			array(
				'ok'   => 1,
				'data' => array(
					'servicio' => get_post_meta( $result, '_tta_servicio', true ),
					'fecha'    => $data['fecha'],
					'hora'     => $data['hora'],
					'nombre'   => $data['nombre'],
				),
			),
			30 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect( add_query_arg( array( 'tta' => 'ok', 'tta_k' => $key ), $back ) . '#agenda' );
		exit;
	}

	/**
	 * HTML del formulario.
	 */
	public static function render() {
		$state = isset( $_GET['tta'] ) ? sanitize_key( $_GET['tta'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$key   = isset( $_GET['tta_k'] ) ? sanitize_key( $_GET['tta_k'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$flash = $key ? get_transient( 'tta_f_' . $key ) : false;
		$old   = ( $flash && isset( $flash['data'] ) ) ? $flash['data'] : array();

		$v = function ( $k ) use ( $old ) {
			return isset( $old[ $k ] ) ? $old[ $k ] : '';
		};

		$services = TTA_Settings::services();
		$selected = $v( 'servicio' );
		if ( ! $selected && isset( $_GET['servicio'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$selected = sanitize_text_field( wp_unslash( $_GET['servicio'] ) ); // phpcs:ignore
		}

		$now  = current_datetime();
		$min  = $now->modify( '+' . (int) TTA_Settings::get( 'min_hours' ) . ' hours' )->format( 'Y-m-d' );
		$max  = $now->modify( '+' . (int) TTA_Settings::get( 'max_days' ) . ' days' )->format( 'Y-m-d' );
		$back = get_permalink();

		ob_start();
		?>
		<div class="tta" id="agenda">
		<?php if ( 'ok' === $state ) : ?>
			<div class="tta-alert tta-alert-ok" role="status">
				<p><?php echo esc_html( TTA_Settings::get( 'confirm_text' ) ); ?></p>
				<?php if ( $flash && ! empty( $flash['ok'] ) ) : ?>
					<p><strong><?php echo esc_html( $old['servicio'] ); ?></strong> · <?php echo esc_html( TTA_Booking::human_date( $old['fecha'] ) . ' · ' . $old['hora'] ); ?></p>
					<?php
					if ( function_exists( 'tt_whatsapp_url' ) ) :
						$msg = sprintf( 'Hola, soy %s. Acabo de solicitar hora para %s el %s a las %s.', $old['nombre'], $old['servicio'], TTA_Booking::human_date( $old['fecha'] ), $old['hora'] );
						?>
						<p><a class="btn btn-wa" href="<?php echo esc_url( tt_whatsapp_url( $msg ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Confirmar más rápido por WhatsApp', 'taller-agenda' ); ?></a></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php if ( 'error' === $state && $flash && isset( $flash['msg'] ) ) : ?>
				<div class="tta-alert tta-alert-err" role="alert"><?php echo esc_html( $flash['msg'] ); ?></div>
			<?php endif; ?>
			<form class="tta-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-rest="<?php echo esc_url( rest_url( 'taller-agenda/v1/horarios' ) ); ?>" data-hora="<?php echo esc_attr( $v( 'hora' ) ); ?>">
				<input type="hidden" name="action" value="tta_submit">
				<input type="hidden" name="tta_back" value="<?php echo esc_url( $back ); ?>">
				<?php wp_nonce_field( 'tta_submit', 'tta_nonce' ); ?>
				<div class="tta-hp" aria-hidden="true"><label>Web <input type="text" name="tta_web" tabindex="-1" autocomplete="off"></label></div>

				<div class="tta-grid">
					<div class="tta-full">
						<label for="tta_servicio"><?php esc_html_e( 'Servicio', 'taller-agenda' ); ?> *</label>
						<select id="tta_servicio" name="tta_servicio" required>
							<option value=""><?php esc_html_e( 'Selecciona un servicio…', 'taller-agenda' ); ?></option>
							<?php foreach ( $services as $id => $name ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( (string) $selected, (string) $id ); ?>><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="tta_fecha"><?php esc_html_e( 'Día', 'taller-agenda' ); ?> *</label>
						<input type="date" id="tta_fecha" name="tta_fecha" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( $v( 'fecha' ) ); ?>" required>
					</div>
					<div>
						<label for="tta_hora"><?php esc_html_e( 'Horario', 'taller-agenda' ); ?> *</label>
						<select id="tta_hora" name="tta_hora" required>
							<option value=""><?php esc_html_e( 'Primero elige el día', 'taller-agenda' ); ?></option>
						</select>
					</div>
					<div>
						<label for="tta_nombre"><?php esc_html_e( 'Nombre', 'taller-agenda' ); ?> *</label>
						<input type="text" id="tta_nombre" name="tta_nombre" autocomplete="name" value="<?php echo esc_attr( $v( 'nombre' ) ); ?>" required>
					</div>
					<div>
						<label for="tta_telefono"><?php esc_html_e( 'Celular / WhatsApp', 'taller-agenda' ); ?> *</label>
						<input type="tel" id="tta_telefono" name="tta_telefono" autocomplete="tel" inputmode="tel" placeholder="9 1234 5678" value="<?php echo esc_attr( $v( 'telefono' ) ); ?>" required>
					</div>
					<div>
						<label for="tta_patente"><?php esc_html_e( 'Patente', 'taller-agenda' ); ?></label>
						<input type="text" id="tta_patente" name="tta_patente" maxlength="8" placeholder="ABCD12" style="text-transform:uppercase" value="<?php echo esc_attr( $v( 'patente' ) ); ?>">
					</div>
					<div>
						<label for="tta_vehiculo"><?php esc_html_e( 'Marca y modelo', 'taller-agenda' ); ?></label>
						<input type="text" id="tta_vehiculo" name="tta_vehiculo" placeholder="Ej: Toyota Yaris 2018" value="<?php echo esc_attr( $v( 'vehiculo' ) ); ?>">
					</div>
					<div class="tta-full">
						<label for="tta_email"><?php esc_html_e( 'Correo (opcional, para enviarte el comprobante)', 'taller-agenda' ); ?></label>
						<input type="email" id="tta_email" name="tta_email" autocomplete="email" value="<?php echo esc_attr( $v( 'email' ) ); ?>">
					</div>
					<div class="tta-full">
						<label for="tta_mensaje"><?php esc_html_e( '¿Qué le pasa al vehículo? (opcional)', 'taller-agenda' ); ?></label>
						<textarea id="tta_mensaje" name="tta_mensaje" rows="3" maxlength="1000"><?php echo esc_textarea( $v( 'mensaje' ) ); ?></textarea>
					</div>
				</div>
				<button type="submit" class="btn btn-primary btn-lg"><?php esc_html_e( 'Solicitar hora', 'taller-agenda' ); ?></button>
				<p class="tta-note"><?php esc_html_e( 'Sin costo ni compromiso. Te confirmaremos la hora por WhatsApp o teléfono.', 'taller-agenda' ); ?></p>
			</form>
			<script>
			(function () {
				var form = document.currentScript.previousElementSibling;
				var day = form.querySelector('#tta_fecha'), hour = form.querySelector('#tta_hora');
				var wanted = form.getAttribute('data-hora');
				function opt(v, t, dis) { var o = document.createElement('option'); o.value = v; o.textContent = t; if (dis) o.disabled = true; return o; }
				function load() {
					hour.innerHTML = ''; hour.appendChild(opt('', 'Cargando horarios…', true));
					if (!day.value) { hour.innerHTML = ''; hour.appendChild(opt('', 'Primero elige el día')); return; }
					fetch(form.getAttribute('data-rest') + '?fecha=' + encodeURIComponent(day.value), { credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (d) {
							var list = (d && d.horarios) || [], free = list.filter(function (s) { return s.disponible; });
							hour.innerHTML = '';
							if (!list.length) { hour.appendChild(opt('', 'No atendemos este día, elige otro')); return; }
							if (!free.length) { hour.appendChild(opt('', 'Día completo, elige otro')); return; }
							hour.appendChild(opt('', 'Elige un horario'));
							list.forEach(function (s) {
								var o = opt(s.hora, s.hora + (s.disponible ? '' : ' (ocupado)'), !s.disponible);
								if (s.hora === wanted && s.disponible) o.selected = true;
								hour.appendChild(o);
							});
						})
						.catch(function () { hour.innerHTML = ''; hour.appendChild(opt('', 'No se pudieron cargar los horarios')); });
				}
				day.addEventListener('change', function () { wanted = ''; load(); });
				if (day.value) load();
			})();
			</script>
		<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
