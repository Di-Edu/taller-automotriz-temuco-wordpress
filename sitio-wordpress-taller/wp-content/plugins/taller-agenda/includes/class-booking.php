<?php
/**
 * Modelo de citas: tipo de contenido, disponibilidad, creación y correos.
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

/**
 * Citas.
 */
class TTA_Booking {

	const CPT = 'tta_cita';

	/**
	 * Estados posibles.
	 */
	public static function statuses() {
		return array(
			'pendiente'  => __( 'Pendiente', 'taller-agenda' ),
			'confirmada' => __( 'Confirmada', 'taller-agenda' ),
			'atendida'   => __( 'Atendida', 'taller-agenda' ),
			'cancelada'  => __( 'Cancelada', 'taller-agenda' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'rest' ) );
	}

	/**
	 * Tipo de contenido privado "Citas".
	 */
	public static function register_post_type() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Citas', 'taller-agenda' ),
					'singular_name' => __( 'Cita', 'taller-agenda' ),
					'add_new'       => __( 'Agregar cita', 'taller-agenda' ),
					'add_new_item'  => __( 'Agregar cita', 'taller-agenda' ),
					'edit_item'     => __( 'Editar cita', 'taller-agenda' ),
					'all_items'     => __( 'Todas las citas', 'taller-agenda' ),
					'search_items'  => __( 'Buscar citas', 'taller-agenda' ),
					'not_found'     => __( 'No hay citas', 'taller-agenda' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 25,
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Horarios configurados para una fecha (sin considerar cupos).
	 *
	 * @param string $date AAAA-MM-DD.
	 * @return string[]
	 */
	public static function slots_for( $date ) {
		$dt = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date ) {
			return array();
		}
		$dow = $dt->format( 'w' );
		if ( ! in_array( $dow, (array) TTA_Settings::get( 'days' ), true ) ) {
			return array();
		}
		if ( in_array( $date, TTA_Settings::lines( 'blocked' ), true ) ) {
			return array();
		}
		$now   = current_datetime();
		$first = $now->modify( '+' . (int) TTA_Settings::get( 'min_hours' ) . ' hours' );
		$last  = $now->setTime( 23, 59 )->modify( '+' . (int) TTA_Settings::get( 'max_days' ) . ' days' );

		$out = array();
		foreach ( TTA_Settings::lines( in_array( $dow, array( '0', '6' ), true ) ? 'slots_sat' : 'slots_week' ) as $hm ) {
			$parts = explode( ':', $hm );
			$slot  = $dt->setTime( (int) $parts[0], (int) $parts[1] );
			if ( $slot >= $first && $slot <= $last ) {
				$out[] = $hm;
			}
		}
		return $out;
	}

	/**
	 * Citas activas (no canceladas) por horario en una fecha.
	 *
	 * @param string $date Fecha.
	 * @return array hora => cantidad
	 */
	public static function taken( $date ) {
		$q     = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'   => '_tta_fecha',
						'value' => $date,
					),
					array(
						'key'     => '_tta_estado',
						'value'   => 'cancelada',
						'compare' => '!=',
					),
				),
			)
		);
		$count = array();
		foreach ( $q->posts as $id ) {
			$h           = get_post_meta( $id, '_tta_hora', true );
			$count[ $h ] = isset( $count[ $h ] ) ? $count[ $h ] + 1 : 1;
		}
		return $count;
	}

	/**
	 * Horarios con disponibilidad.
	 *
	 * @param string $date Fecha.
	 * @return array[] [ ['hora'=>'09:00','disponible'=>true], ... ]
	 */
	public static function availability( $date ) {
		$cap   = (int) TTA_Settings::get( 'capacity' );
		$taken = self::taken( $date );
		$out   = array();
		foreach ( self::slots_for( $date ) as $h ) {
			$out[] = array(
				'hora'       => $h,
				'disponible' => ( isset( $taken[ $h ] ) ? $taken[ $h ] : 0 ) < $cap,
			);
		}
		return $out;
	}

	/**
	 * Endpoint público de solo lectura: /wp-json/taller-agenda/v1/horarios?fecha=AAAA-MM-DD
	 * (no expone datos de clientes, solo si cada horario está libre).
	 */
	public static function rest() {
		register_rest_route(
			'taller-agenda/v1',
			'/horarios',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'fecha' => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v );
						},
					),
				),
				'callback'            => function ( WP_REST_Request $r ) {
					$res = rest_ensure_response( array( 'horarios' => self::availability( $r['fecha'] ) ) );
					$res->header( 'Cache-Control', 'no-store' );
					return $res;
				},
			)
		);
	}

	/**
	 * Crea una cita validando todo en el servidor.
	 *
	 * @param array $d Datos limpios.
	 * @return int|WP_Error
	 */
	public static function create( $d ) {
		$services = TTA_Settings::services();
		if ( '' === $d['nombre'] || strlen( $d['telefono'] ) < 8 ) {
			return new WP_Error( 'datos', __( 'Ingresa tu nombre y un teléfono válido.', 'taller-agenda' ) );
		}
		if ( ! isset( $services[ $d['servicio'] ] ) ) {
			return new WP_Error( 'servicio', __( 'Selecciona un servicio.', 'taller-agenda' ) );
		}
		if ( $d['email'] && ! is_email( $d['email'] ) ) {
			return new WP_Error( 'email', __( 'El correo no es válido.', 'taller-agenda' ) );
		}

		// Bloqueo simple para evitar dos reservas simultáneas del último cupo.
		$lock = 'tta_lock_' . md5( $d['fecha'] . $d['hora'] );
		if ( get_transient( $lock ) ) {
			usleep( 400000 );
		}
		set_transient( $lock, 1, 10 );

		$ok = false;
		foreach ( self::availability( $d['fecha'] ) as $slot ) {
			if ( $slot['hora'] === $d['hora'] && $slot['disponible'] ) {
				$ok = true;
			}
		}
		if ( ! $ok ) {
			delete_transient( $lock );
			return new WP_Error( 'horario', __( 'Ese horario ya no está disponible. Por favor elige otro.', 'taller-agenda' ) );
		}

		$service_name = $services[ $d['servicio'] ];
		$id           = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s — %s', $d['nombre'], $service_name ),
			),
			true
		);
		delete_transient( $lock );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$meta = array(
			'_tta_nombre'   => $d['nombre'],
			'_tta_telefono' => $d['telefono'],
			'_tta_email'    => $d['email'],
			'_tta_patente'  => $d['patente'],
			'_tta_vehiculo' => $d['vehiculo'],
			'_tta_servicio' => $service_name,
			'_tta_fecha'    => $d['fecha'],
			'_tta_hora'     => $d['hora'],
			'_tta_mensaje'  => $d['mensaje'],
			'_tta_estado'   => 'pendiente',
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}

		self::notify( $id );
		return $id;
	}

	/**
	 * Fecha legible en español.
	 *
	 * @param string $date AAAA-MM-DD.
	 */
	public static function human_date( $date ) {
		$ts = strtotime( $date . ' 12:00:00' );
		return $ts ? wp_date( 'l j \d\e F', $ts ) : $date;
	}

	/**
	 * Teléfono → enlace de WhatsApp.
	 *
	 * @param string $phone Teléfono.
	 */
	public static function wa_link( $phone ) {
		$n = preg_replace( '/\D/', '', (string) $phone );
		if ( 9 === strlen( $n ) && '9' === $n[0] ) {
			$n = '56' . $n;
		} elseif ( 8 === strlen( $n ) ) {
			$n = '569' . $n;
		}
		return 'https://wa.me/' . $n;
	}

	/**
	 * Correos al taller y al cliente.
	 *
	 * @param int $id Cita.
	 */
	public static function notify( $id ) {
		$m    = function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_tta_' . $k, true );
		};
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$when = self::human_date( $m( 'fecha' ) ) . ' a las ' . $m( 'hora' );

		$body  = "Nueva solicitud de hora:\n\n";
		$body .= 'Servicio: ' . $m( 'servicio' ) . "\n";
		$body .= 'Fecha: ' . $when . "\n";
		$body .= 'Nombre: ' . $m( 'nombre' ) . "\n";
		$body .= 'Teléfono: ' . $m( 'telefono' ) . ' (WhatsApp: ' . self::wa_link( $m( 'telefono' ) ) . ")\n";
		if ( $m( 'email' ) ) {
			$body .= 'Correo: ' . $m( 'email' ) . "\n";
		}
		if ( $m( 'patente' ) || $m( 'vehiculo' ) ) {
			$body .= 'Vehículo: ' . trim( $m( 'vehiculo' ) . ' ' . $m( 'patente' ) ) . "\n";
		}
		if ( $m( 'mensaje' ) ) {
			$body .= "\nComentario:\n" . $m( 'mensaje' ) . "\n";
		}
		$body .= "\nGestionar: " . admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n";

		$to = TTA_Settings::get( 'notify_email' );
		if ( $to ) {
			$headers = array();
			if ( $m( 'email' ) ) {
				$headers[] = 'Reply-To: ' . $m( 'nombre' ) . ' <' . $m( 'email' ) . '>';
			}
			wp_mail( $to, sprintf( '[%s] Nueva cita: %s — %s', $site, $m( 'servicio' ), $when ), $body, $headers );
		}

		if ( TTA_Settings::get( 'client_email' ) && $m( 'email' ) ) {
			$c  = 'Hola ' . $m( 'nombre' ) . ",\n\n";
			$c .= 'Recibimos tu solicitud de hora en ' . $site . ":\n\n";
			$c .= 'Servicio: ' . $m( 'servicio' ) . "\nFecha: " . $when . "\n\n";
			$c .= "Te contactaremos para confirmarla. Si necesitas cambiarla, responde este correo.\n\n" . $site . "\n" . home_url( '/' );
			wp_mail( $m( 'email' ), sprintf( 'Tu solicitud de hora en %s', $site ), $c );
		}
	}
}
