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
		add_action( 'admin_menu', array( __CLASS__, 'calendar_menu' ) );
	}

	/**
	 * Submenú "Calendario".
	 */
	public static function calendar_menu() {
		add_submenu_page( 'edit.php?post_type=' . TTA_Booking::CPT, __( 'Calendario de citas', 'taller-agenda' ), __( 'Calendario', 'taller-agenda' ), 'edit_posts', 'tta-calendar', array( __CLASS__, 'calendar_page' ), 1 );
	}

	/**
	 * Vista mensual: citas de cada día y horarios libres / ocupados.
	 */
	public static function calendar_page() {
		$tz    = wp_timezone();
		$month = isset( $_GET['mes'] ) && preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', sanitize_text_field( wp_unslash( $_GET['mes'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['mes'] ) ) : current_datetime()->format( 'Y-m' ); // phpcs:ignore WordPress.Security.NonceVerification
		$first = DateTimeImmutable::createFromFormat( '!Y-m-d', $month . '-01', $tz );

		$branches = TTA_Settings::branches();
		$branch   = '';
		if ( $branches ) {
			$req    = isset( $_GET['sucursal'] ) ? sanitize_text_field( wp_unslash( $_GET['sucursal'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$branch = isset( $branches[ $req ] ) ? $req : '';
		}

		// Citas del mes (incluye canceladas para que el taller vea el historial).
		$meta = array(
			array(
				'key'     => '_tta_fecha',
				'value'   => $month . '-',
				'compare' => 'LIKE',
			),
		);
		if ( '' !== $branch ) {
			$meta[] = array(
				'key'   => '_tta_sucursal',
				'value' => $branch,
			);
		}
		$q      = new WP_Query(
			array(
				'post_type'      => TTA_Booking::CPT,
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 2000,
				'no_found_rows'  => true,
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$by_day = array();
		foreach ( $q->posts as $p ) {
			$d              = get_post_meta( $p->ID, '_tta_fecha', true );
			$by_day[ $d ][] = array(
				'id'     => $p->ID,
				'hora'   => get_post_meta( $p->ID, '_tta_hora', true ),
				'nombre' => get_post_meta( $p->ID, '_tta_nombre', true ),
				'srv'    => get_post_meta( $p->ID, '_tta_servicio', true ),
				'suc'    => get_post_meta( $p->ID, '_tta_sucursal_nombre', true ),
				'estado' => get_post_meta( $p->ID, '_tta_estado', true ),
			);
		}
		foreach ( $by_day as &$list ) {
			usort(
				$list,
				function ( $a, $b ) {
					return strcmp( $a['hora'], $b['hora'] );
				}
			);
		}
		unset( $list );

		// Disponibilidad (por sucursal si hay varias y no se filtró, se muestra la de la primera).
		$avail_branch = '' !== $branch ? $branch : ( $branches ? (string) key( $branches ) : '' );
		$avail        = ( count( $branches ) > 1 && '' === $branch ) ? array() : TTA_Booking::month( $month, $avail_branch );

		$base   = admin_url( 'edit.php?post_type=' . TTA_Booking::CPT . '&page=tta-calendar' );
		$nav    = function ( $m ) use ( $base, $branch ) {
			return add_query_arg( array_filter( array( 'mes' => $m, 'sucursal' => $branch ) ), $base );
		};
		$colors = array(
			'pendiente'  => '#b54708',
			'confirmada' => '#027a48',
			'atendida'   => '#475467',
			'cancelada'  => '#b42318',
		);
		$labels = TTA_Booking::statuses();
		$today  = current_datetime()->format( 'Y-m-d' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Calendario de citas', 'taller-agenda' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . TTA_Booking::CPT ) ); ?>"><?php esc_html_e( 'Agregar cita', 'taller-agenda' ); ?></a>
			<hr class="wp-header-end">

			<div class="tta-admin-cal-bar">
				<a class="button" href="<?php echo esc_url( $nav( $first->modify( '-1 month' )->format( 'Y-m' ) ) ); ?>">‹ <?php esc_html_e( 'Anterior', 'taller-agenda' ); ?></a>
				<strong class="tta-admin-cal-title"><?php echo esc_html( ucfirst( wp_date( 'F Y', $first->getTimestamp() ) ) ); ?></strong>
				<a class="button" href="<?php echo esc_url( $nav( $first->modify( '+1 month' )->format( 'Y-m' ) ) ); ?>"><?php esc_html_e( 'Siguiente', 'taller-agenda' ); ?> ›</a>
				<a class="button-link" href="<?php echo esc_url( $nav( current_datetime()->format( 'Y-m' ) ) ); ?>"><?php esc_html_e( 'Hoy', 'taller-agenda' ); ?></a>
				<?php if ( count( $branches ) > 1 ) : ?>
					<form method="get" style="margin-left:auto">
						<input type="hidden" name="post_type" value="<?php echo esc_attr( TTA_Booking::CPT ); ?>">
						<input type="hidden" name="page" value="tta-calendar">
						<input type="hidden" name="mes" value="<?php echo esc_attr( $month ); ?>">
						<select name="sucursal" onchange="this.form.submit()">
							<option value=""><?php esc_html_e( 'Todas las sucursales', 'taller-agenda' ); ?></option>
							<?php foreach ( $branches as $id => $name ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $branch, (string) $id ); ?>><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				<?php endif; ?>
			</div>
			<?php if ( count( $branches ) > 1 && '' === $branch ) : ?>
				<p class="description"><?php esc_html_e( 'Elige una sucursal para ver también sus horarios libres de cada día.', 'taller-agenda' ); ?></p>
			<?php endif; ?>

			<div class="tta-admin-cal">
				<?php foreach ( array( 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo' ) as $dname ) : ?>
					<div class="tta-admin-dow"><?php echo esc_html( $dname ); ?></div>
				<?php endforeach; ?>
				<?php
				$offset = ( (int) $first->format( 'N' ) ) - 1;
				for ( $i = 0; $i < $offset; $i++ ) {
					echo '<div class="tta-admin-day is-empty"></div>';
				}
				$days = (int) $first->format( 't' );
				for ( $d = 1; $d <= $days; $d++ ) :
					$date = $month . '-' . str_pad( (string) $d, 2, '0', STR_PAD_LEFT );
					$info = isset( $avail[ $date ] ) ? $avail[ $date ] : null;
					$cls  = 'tta-admin-day' . ( $date === $today ? ' is-today' : '' ) . ( $date < $today ? ' is-past' : '' );
					?>
					<div class="<?php echo esc_attr( $cls ); ?>">
						<div class="tta-admin-day-head">
							<span class="tta-admin-num"><?php echo (int) $d; ?></span>
							<?php if ( $info ) : ?>
								<span class="tta-admin-badge <?php echo $info['libres'] ? ( $info['libres'] / $info['total'] <= 0.34 ? 'is-few' : 'is-free' ) : 'is-full'; ?>">
									<?php
									echo $info['libres']
										/* translators: 1: libres, 2: total */
										? esc_html( sprintf( __( '%1$d/%2$d libres', 'taller-agenda' ), $info['libres'], $info['total'] ) )
										: esc_html__( 'Completo', 'taller-agenda' );
									?>
								</span>
							<?php endif; ?>
						</div>
						<?php if ( ! empty( $by_day[ $date ] ) ) : ?>
							<ul>
								<?php
								foreach ( $by_day[ $date ] as $c ) :
									$st = $c['estado'] ? $c['estado'] : 'pendiente';
									?>
									<li class="tta-admin-ev is-<?php echo esc_attr( $st ); ?>" style="border-left-color:<?php echo esc_attr( $colors[ $st ] ?? '#999' ); ?>">
										<a href="<?php echo esc_url( get_edit_post_link( $c['id'] ) ); ?>" title="<?php echo esc_attr( ( $labels[ $st ] ?? $st ) . ( $c['suc'] ? ' · ' . $c['suc'] : '' ) ); ?>">
											<strong><?php echo esc_html( $c['hora'] ); ?></strong> <?php echo esc_html( $c['nombre'] ); ?>
											<span><?php echo esc_html( $c['srv'] . ( $c['suc'] && '' === $branch ? ' · ' . $c['suc'] : '' ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endfor; ?>
			</div>

			<p class="tta-admin-legend">
				<?php foreach ( $labels as $k => $label ) : ?>
					<span><i style="background:<?php echo esc_attr( $colors[ $k ] ); ?>"></i><?php echo esc_html( $label ); ?></span>
				<?php endforeach; ?>
			</p>
		</div>
		<style>
			.tta-admin-cal-bar{display:flex;align-items:center;gap:8px;margin:16px 0;flex-wrap:wrap}
			.tta-admin-cal-title{font-size:1.3em;min-width:180px;text-align:center}
			.tta-admin-cal{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px}
			.tta-admin-dow{font-weight:600;text-align:center;padding:6px 0;color:#50575e}
			.tta-admin-day{background:#fff;border:1px solid #dcdcde;border-radius:6px;min-height:110px;padding:6px;overflow:hidden}
			.tta-admin-day.is-empty{background:transparent;border:0}
			.tta-admin-day.is-past{background:#f6f7f7}
			.tta-admin-day.is-today{border:2px solid #2271b1}
			.tta-admin-day-head{display:flex;justify-content:space-between;align-items:center;gap:4px;margin-bottom:4px}
			.tta-admin-num{font-weight:700}
			.tta-admin-badge{font-size:11px;padding:1px 6px;border-radius:10px;white-space:nowrap}
			.tta-admin-badge.is-free{background:#ecfdf3;color:#027a48}.tta-admin-badge.is-few{background:#fffbeb;color:#b54708}.tta-admin-badge.is-full{background:#fef3f2;color:#b42318}
			.tta-admin-day ul{margin:0}
			.tta-admin-ev{margin:0 0 3px;border-left:3px solid;padding:2px 4px;background:#f6f7f7;border-radius:3px;font-size:12px;line-height:1.3}
			.tta-admin-ev a{text-decoration:none;color:#1d2327;display:block}
			.tta-admin-ev span{display:block;color:#646970;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
			.tta-admin-ev.is-cancelada a{text-decoration:line-through;opacity:.6}
			.tta-admin-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px}
			.tta-admin-legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:5px}
			@media (max-width:782px){.tta-admin-cal{grid-template-columns:1fr}.tta-admin-dow,.tta-admin-day.is-empty{display:none}.tta-admin-day{min-height:0}}
		</style>
		<?php
	}

	/**
	 * Columnas del listado.
	 *
	 * @param array $c Columnas.
	 */
	public static function columns( $c ) {
		$cols = array(
			'cb'           => $c['cb'],
			'title'        => __( 'Cliente — servicio', 'taller-agenda' ),
			'tta_cuando'   => __( 'Fecha y hora', 'taller-agenda' ),
		);
		if ( count( TTA_Settings::branches() ) > 1 ) {
			$cols['tta_sucursal'] = __( 'Sucursal', 'taller-agenda' );
		}
		return $cols + array(
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
			case 'tta_sucursal':
				echo esc_html( $m( 'sucursal_nombre' ) );
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
		$branches = TTA_Settings::branches();
		if ( count( $branches ) > 1 ) {
			$sb = (string) get_post_meta( $post->ID, '_tta_sucursal', true );
			echo '<p><label for="tta_suc"><strong>' . esc_html__( 'Sucursal', 'taller-agenda' ) . '</strong></label><br><select id="tta_suc" name="tta[sucursal]" style="width:100%">';
			foreach ( $branches as $id => $name ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $id ), selected( $sb, (string) $id, false ), esc_html( $name ) );
			}
			echo '</select></p><hr>';
		}
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
		} elseif ( ! get_post_meta( $post_id, '_tta_estado', true ) ) {
			update_post_meta( $post_id, '_tta_estado', 'pendiente' );
		}
		$branches = TTA_Settings::branches();
		$branch   = TTA_Settings::branch_id( isset( $in['sucursal'] ) ? $in['sucursal'] : '' );
		if ( '' !== $branch && 'invalid' !== $branch ) {
			update_post_meta( $post_id, '_tta_sucursal', $branch );
			update_post_meta( $post_id, '_tta_sucursal_nombre', $branches[ $branch ] );
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
