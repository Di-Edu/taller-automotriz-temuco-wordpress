<?php
/**
 * Gestión de citas en el escritorio: columnas, filtros, ficha editable y widget "Citas de hoy".
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin.
 */
class TTA_Admin {

	/**
	 * Campos editables de la ficha.
	 */
	private static function fields() {
		return array(
			'nombre'   => __( 'Nombre', 'taller-agenda' ),
			'telefono' => __( 'Teléfono', 'taller-agenda' ),
			'email'    => __( 'Correo', 'taller-agenda' ),
			'patente'  => __( 'Patente', 'taller-agenda' ),
			'vehiculo' => __( 'Vehículo', 'taller-agenda' ),
			'servicio' => __( 'Servicio', 'taller-agenda' ),
			'fecha'    => __( 'Fecha (AAAA-MM-DD)', 'taller-agenda' ),
			'hora'     => __( 'Hora (HH:MM)', 'taller-agenda' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		$cpt = TTA_Booking::CPT;
		add_filter( "manage_{$cpt}_posts_columns", array( __CLASS__, 'columns' ) );
		add_action( "manage_{$cpt}_posts_custom_column", array( __CLASS__, 'column' ), 10, 2 );
		add_filter( "manage_edit-{$cpt}_sortable_columns", array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'query' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_ui' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( "save_post_{$cpt}", array( __CLASS__, 'save' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	/**
	 * Columnas del listado.
	 *
	 * @param array $c Columnas.
	 */
	public static function columns( $c ) {
		return array(
			'cb'           => $c['cb'],
			'title'        => __( 'Cliente — servicio', 'taller-agenda' ),
			'tta_cuando'   => __( 'Fecha y hora', 'taller-agenda' ),
			'tta_contacto' => __( 'Contacto', 'taller-agenda' ),
			'tta_vehiculo' => __( 'Vehículo', 'taller-agenda' ),
			'tta_estado'   => __( 'Estado', 'taller-agenda' ),
			'date'         => __( 'Recibida', 'taller-agenda' ),
		);
	}

	/**
	 * Celdas.
	 *
	 * @param string $col Columna.
	 * @param int    $id  Cita.
	 */
	public static function column( $col, $id ) {
		$m = function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_tta_' . $k, true );
		};
		switch ( $col ) {
			case 'tta_cuando':
				echo '<strong>' . esc_html( TTA_Booking::human_date( $m( 'fecha' ) ) ) . '</strong><br>' . esc_html( $m( 'hora' ) );
				break;
			case 'tta_contacto':
				echo esc_html( $m( 'telefono' ) ) . '<br><a href="' . esc_url( TTA_Booking::wa_link( $m( 'telefono' ) ) ) . '" target="_blank" rel="noopener">WhatsApp</a>';
				if ( $m( 'email' ) ) {
					echo ' · <a href="mailto:' . esc_attr( $m( 'email' ) ) . '">' . esc_html__( 'Correo', 'taller-agenda' ) . '</a>';
				}
				break;
			case 'tta_vehiculo':
				echo esc_html( trim( $m( 'vehiculo' ) . ' ' . $m( 'patente' ) ) );
				break;
			case 'tta_estado':
				$s      = $m( 'estado' ) ? $m( 'estado' ) : 'pendiente';
				$labels = TTA_Booking::statuses();
				$colors = array(
					'pendiente'  => '#b54708',
					'confirmada' => '#027a48',
					'atendida'   => '#475467',
					'cancelada'  => '#b42318',
				);
				printf( '<span style="font-weight:600;color:%s">%s</span>', esc_attr( $colors[ $s ] ?? '#000' ), esc_html( $labels[ $s ] ?? $s ) );
				break;
		}
	}

	/**
	 * Columnas ordenables.
	 *
	 * @param array $c Columnas.
	 */
	public static function sortable( $c ) {
		$c['tta_cuando'] = 'tta_cuando';
		return $c;
	}

	/**
	 * Orden por fecha de cita y filtro por estado.
	 *
	 * @param WP_Query $q Consulta.
	 */
	public static function query( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || TTA_Booking::CPT !== $q->get( 'post_type' ) ) {
			return;
		}
		$orderby = $q->get( 'orderby' );
		if ( ! $orderby || 'tta_cuando' === $orderby ) {
			$q->set( 'meta_key', '_tta_fecha' );
			$q->set( 'orderby', array( 'meta_value' => $q->get( 'order' ) ? $q->get( 'order' ) : 'DESC' ) );
		}
		if ( ! empty( $_GET['tta_estado'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set(
				'meta_query',
				array(
					array(
						'key'   => '_tta_estado',
						'value' => sanitize_key( $_GET['tta_estado'] ), // phpcs:ignore
					),
				)
			);
		}
	}

	/**
	 * Selector de estado sobre el listado.
	 *
	 * @param string $post_type Tipo.
	 */
	public static function filter_ui( $post_type ) {
		if ( TTA_Booking::CPT !== $post_type ) {
			return;
		}
		$cur = isset( $_GET['tta_estado'] ) ? sanitize_key( $_GET['tta_estado'] ) : ''; // phpcs:ignore
		echo '<select name="tta_estado"><option value="">' . esc_html__( 'Todos los estados', 'taller-agenda' ) . '</option>';
		foreach ( TTA_Booking::statuses() as $k => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $cur, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Acción rápida "WhatsApp" en cada fila.
	 *
	 * @param array   $actions Acciones.
	 * @param WP_Post $post    Cita.
	 */
	public static function row_actions( $actions, $post ) {
		if ( TTA_Booking::CPT === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
			$tel = get_post_meta( $post->ID, '_tta_telefono', true );
			if ( $tel ) {
				$actions['tta_wa'] = '<a href="' . esc_url( TTA_Booking::wa_link( $tel ) ) . '" target="_blank" rel="noopener">WhatsApp</a>';
			}
		}
		return $actions;
	}

	/**
	 * Cajas de la ficha.
	 */
	public static function boxes() {
		add_meta_box( 'tta_details', __( 'Datos de la cita', 'taller-agenda' ), array( __CLASS__, 'box_details' ), TTA_Booking::CPT, 'normal', 'high' );
		add_meta_box( 'tta_status', __( 'Estado', 'taller-agenda' ), array( __CLASS__, 'box_status' ), TTA_Booking::CPT, 'side', 'high' );
	}

	/**
	 * Ficha.
	 *
	 * @param WP_Post $post Cita.
	 */
	public static function box_details( $post ) {
		wp_nonce_field( 'tta_save', 'tta_admin_nonce' );
		echo '<table class="form-table" role="presentation">';
		foreach ( self::fields() as $k => $label ) {
			$val = get_post_meta( $post->ID, '_tta_' . $k, true );
			printf(
				'<tr><th><label for="tta_%1$s">%2$s</label></th><td><input type="text" class="regular-text" id="tta_%1$s" name="tta[%1$s]" value="%3$s"></td></tr>',
				esc_attr( $k ),
				esc_html( $label ),
				esc_attr( $val )
			);
		}
		printf(
			'<tr><th><label for="tta_mensaje">%s</label></th><td><textarea class="large-text" rows="4" id="tta_mensaje" name="tta[mensaje]">%s</textarea></td></tr>',
			esc_html__( 'Comentario del cliente', 'taller-agenda' ),
			esc_textarea( get_post_meta( $post->ID, '_tta_mensaje', true ) )
		);
		echo '</table>';
	}

	/**
	 * Caja de estado.
	 *
	 * @param WP_Post $post Cita.
	 */
	public static function box_status( $post ) {
		$cur = get_post_meta( $post->ID, '_tta_estado', true );
		$cur = $cur ? $cur : 'pendiente';
		foreach ( TTA_Booking::statuses() as $k => $label ) {
			printf( '<p><label><input type="radio" name="tta[estado]" value="%s" %s> %s</label></p>', esc_attr( $k ), checked( $cur, $k, false ), esc_html( $label ) );
		}
		$tel = get_post_meta( $post->ID, '_tta_telefono', true );
		if ( $tel ) {
			$msg = sprintf(
				'Hola %s, te confirmamos tu hora para %s el %s a las %s. ¡Te esperamos!',
				get_post_meta( $post->ID, '_tta_nombre', true ),
				get_post_meta( $post->ID, '_tta_servicio', true ),
				TTA_Booking::human_date( get_post_meta( $post->ID, '_tta_fecha', true ) ),
				get_post_meta( $post->ID, '_tta_hora', true )
			);
			printf( '<p><a class="button button-primary" href="%s" target="_blank" rel="noopener">%s</a></p>', esc_url( TTA_Booking::wa_link( $tel ) . '?text=' . rawurlencode( $msg ) ), esc_html__( 'Confirmar por WhatsApp', 'taller-agenda' ) );
		}
	}

	/**
	 * Guarda la ficha.
	 *
	 * @param int $post_id Cita.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST['tta_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_admin_nonce'] ) ), 'tta_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['tta'] ) ? (array) wp_unslash( $_POST['tta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( array_keys( self::fields() ) as $k ) {
			if ( isset( $in[ $k ] ) ) {
				update_post_meta( $post_id, '_tta_' . $k, sanitize_text_field( $in[ $k ] ) );
			}
		}
		if ( isset( $in['mensaje'] ) ) {
			update_post_meta( $post_id, '_tta_mensaje', sanitize_textarea_field( $in['mensaje'] ) );
		}
		if ( isset( $in['estado'] ) && array_key_exists( $in['estado'], TTA_Booking::statuses() ) ) {
			update_post_meta( $post_id, '_tta_estado', $in['estado'] );
		}
	}

	/**
	 * Widget del escritorio: próximas citas.
	 */
	public static function dashboard() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'tta_today',
			__( 'Próximas citas del taller', 'taller-agenda' ),
			function () {
				$q = new WP_Query(
					array(
						'post_type'      => TTA_Booking::CPT,
						'posts_per_page' => 10,
						'no_found_rows'  => true,
						'meta_key'       => '_tta_fecha', // phpcs:ignore WordPress.DB.SlowDBQuery
						'orderby'        => 'meta_value',
						'order'          => 'ASC',
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'key'     => '_tta_fecha',
								'value'   => current_datetime()->format( 'Y-m-d' ),
								'compare' => '>=',
							),
							array(
								'key'     => '_tta_estado',
								'value'   => array( 'cancelada', 'atendida' ),
								'compare' => 'NOT IN',
							),
						),
					)
				);
				if ( ! $q->have_posts() ) {
					echo '<p>' . esc_html__( 'No hay citas próximas.', 'taller-agenda' ) . '</p>';
					return;
				}
				echo '<ul>';
				foreach ( $q->posts as $p ) {
					printf(
						'<li><strong>%s %s</strong> — <a href="%s">%s</a> (%s)</li>',
						esc_html( TTA_Booking::human_date( get_post_meta( $p->ID, '_tta_fecha', true ) ) ),
						esc_html( get_post_meta( $p->ID, '_tta_hora', true ) ),
						esc_url( get_edit_post_link( $p->ID ) ),
						esc_html( get_the_title( $p ) ),
						esc_html( TTA_Booking::statuses()[ get_post_meta( $p->ID, '_tta_estado', true ) ] ?? '' )
					);
				}
				echo '</ul><p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=' . TTA_Booking::CPT ) ) . '">' . esc_html__( 'Ver todas', 'taller-agenda' ) . '</a></p>';
			}
		);
	}
}
