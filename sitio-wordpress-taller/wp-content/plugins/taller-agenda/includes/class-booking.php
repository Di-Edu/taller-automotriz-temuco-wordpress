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
	 * Citas activas (no canceladas) agrupadas por fecha y hora.
	 *
	 * @param string $date_like Fecha exacta (AAAA-MM-DD) o mes (AAAA-MM) para todo el mes.
	 * @param string $branch    Sucursal ('' = todas).
	 * @return array fecha => [hora => cantidad]
	 */
	public static function taken( $date_like, $branch = '' ) {
		$is_month = 7 === strlen( $date_like );
		$meta     = array(
			array(
				'key'     => '_tta_fecha',
				'value'   => $is_month ? $date_like . '-' : $date_like,
				'compare' => $is_month ? 'LIKE' : '=',
			),
			array(
				'key'     => '_tta_estado',
				'value'   => 'cancelada',
				'compare' => '!=',
			),
		);
		if ( '' !== $branch ) {
			$meta[] = array(
				'key'   => '_tta_sucursal',
				'value' => $branch,
			);
		}
		$q     = new WP_Query(
			array(
				'post_type'              => self::CPT,
				'post_status'            => array( 'publish', 'private' ),
				'posts_per_page'         => 2000,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$count = array();
		foreach ( $q->posts as $id ) {
			$d = get_post_meta( $id, '_tta_fecha', true );
			$h = get_post_meta( $id, '_tta_hora', true );
			if ( ! isset( $count[ $d ][ $h ] ) ) {
				$count[ $d ][ $h ] = 0;
			}
			++$count[ $d ][ $h ];
		}
		return $count;
	}

	/**
	 * Horarios de un día con disponibilidad.
	 *
	 * @param string $date   Fecha.
	 * @param string $branch Sucursal.
	 * @param array  $taken  Conteo ya calculado (opcional, para no repetir consultas).
	 * @return array[] [ ['hora'=>'09:00','disponible'=>true,'libres'=>2], ... ]
	 */
	public static function availability( $date, $branch = '', $taken = null ) {
		$cap   = (int) TTA_Settings::get( 'capacity' );
		$taken = null === $taken ? self::taken( $date, $branch ) : $taken;
		$day   = isset( $taken[ $date ] ) ? $taken[ $date ] : array();
		$out   = array();
		foreach ( self::slots_for( $date ) as $h ) {
			$used  = isset( $day[ $h ] ) ? $day[ $h ] : 0;
			$out[] = array(
				'hora'       => $h,
				'disponible' => $used < $cap,
				'libres'     => max( 0, $cap - $used ),
			);
		}
		return $out;
	}

	/**
	 * Resumen de un mes para el calendario: por cada día con atención, horarios totales y libres.
	 * Los días que no aparecen están cerrados, bloqueados o fuera del rango de reserva.
	 *
	 * @param string $month  AAAA-MM.
	 * @param string $branch Sucursal.
	 * @return array fecha => ['total'=>n,'libres'=>n]
	 */
	public static function month( $month, $branch = '' ) {
		$first = DateTimeImmutable::createFromFormat( '!Y-m-d', $month . '-01', wp_timezone() );
		if ( ! $first ) {
			return array();
		}
		$taken = self::taken( $month, $branch );
		$days  = (int) $first->format( 't' );
		$out   = array();
		for ( $i = 0; $i < $days; $i++ ) {
			$date  = $first->modify( "+{$i} days" )->format( 'Y-m-d' );
			$slots = self::availability( $date, $branch, $taken );
			if ( ! $slots ) {
				continue;
			}
			$out[ $date ] = array(
				'total'  => count( $slots ),
				'libres' => count(
					array_filter(
						$slots,
						function ( $s ) {
							return $s['disponible'];
						}
					)
				),
			);
		}
		return $out;
	}

	/**
	 * Endpoints públicos de solo lectura (no exponen datos de clientes, solo libre/ocupado):
	 *  /wp-json/taller-agenda/v1/horarios?fecha=AAAA-MM-DD[&sucursal=ID]
	 *  /wp-json/taller-agenda/v1/mes?mes=AAAA-MM[&sucursal=ID]
	 */
	public static function rest() {
		$branch_arg = array(
			'required'          => false,
			'sanitize_callback' => 'absint',
		);
		$no_store   = function ( $data ) {
			$res = rest_ensure_response( $data );
			$res->header( 'Cache-Control', 'no-store' );
			return $res;
		};
		$branch_of  = function ( WP_REST_Request $r ) {
			$b = TTA_Settings::branch_id( $r['sucursal'] );
			return 'invalid' === $b ? null : $b;
		};

		register_rest_route(
			'taller-agenda/v1',
			'/horarios',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'fecha'    => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v );
						},
					),
					'sucursal' => $branch_arg,
				),
				'callback'            => function ( WP_REST_Request $r ) use ( $no_store, $branch_of ) {
					$b = $branch_of( $r );
					return $no_store( array( 'horarios' => null === $b ? array() : self::availability( $r['fecha'], $b ) ) );
				},
			)
		);

		register_rest_route(
			'taller-agenda/v1',
			'/mes',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'mes'      => array(
						'required'          => true,
						'validate_callback' => function ( $v ) {
							return (bool) preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $v );
						},
					),
					'sucursal' => $branch_arg,
				),
				'callback'            => function ( WP_REST_Request $r ) use ( $no_store, $branch_of ) {
					$b = $branch_of( $r );
					return $no_store(
						array(
							'mes'  => $r['mes'],
							'dias' => null === $b ? (object) array() : (object) self::month( $r['mes'], $b ),
						)
					);
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
		$branch = TTA_Settings::branch_id( isset( $d['sucursal'] ) ? $d['sucursal'] : '' );
		if ( 'invalid' === $branch ) {
			return new WP_Error( 'sucursal', __( 'Selecciona una sucursal.', 'taller-agenda' ) );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['fecha'] ) || '' === $d['hora'] ) {
			return new WP_Error( 'fecha', __( 'Elige un día en el calendario y un horario.', 'taller-agenda' ) );
		}

		// Bloqueo simple para evitar dos reservas simultáneas del último cupo.
		$lock = 'tta_lock_' . md5( $branch . $d['fecha'] . $d['hora'] );
		if ( get_transient( $lock ) ) {
			usleep( 400000 );
		}
		set_transient( $lock, 1, 10 );

		$ok = false;
		foreach ( self::availability( $d['fecha'], $branch ) as $slot ) {
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
		if ( '' !== $branch ) {
			$branches                     = TTA_Settings::branches();
			$meta['_tta_sucursal']        = $branch;
			$meta['_tta_sucursal_nombre'] = $branches[ $branch ];
		}
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
		if ( $m( 'sucursal_nombre' ) ) {
			$body .= 'Sucursal: ' . $m( 'sucursal_nombre' ) . "\n";
		}
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
			$c .= 'Servicio: ' . $m( 'servicio' ) . "\n";
			$c .= $m( 'sucursal_nombre' ) ? 'Sucursal: ' . $m( 'sucursal_nombre' ) . "\n" : '';
			$c .= 'Fecha: ' . $when . "\n\n";
			$c .= "Te contactaremos para confirmarla. Si necesitas cambiarla, responde este correo.\n\n" . $site . "\n" . home_url( '/' );
			wp_mail( $m( 'email' ), sprintf( 'Tu solicitud de hora en %s', $site ), $c );
		}
	}
}
