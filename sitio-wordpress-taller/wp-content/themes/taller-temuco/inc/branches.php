<?php
/**
 * Sucursales: el cliente las agrega en el escritorio (menú "Sucursales") y aparecen solas
 * en el mapa interactivo, en el pie de página, en la agenda y en los datos estructurados de Google.
 * Para ubicar cada sucursal basta con hacer clic en el mapa o buscar la dirección.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

define( 'TT_LEAFLET_VER', '1.9.4' );
define( 'TT_LEAFLET_CSS', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' );
define( 'TT_LEAFLET_JS', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' );
define( 'TT_LEAFLET_CSS_SRI', 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=' );
define( 'TT_LEAFLET_JS_SRI', 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=' );

/**
 * Campos de cada sucursal.
 */
function tt_branch_fields() {
	return array(
		'address'  => __( 'Dirección', 'taller-temuco' ),
		'city'     => __( 'Comuna', 'taller-temuco' ),
		'phone'    => __( 'Teléfono', 'taller-temuco' ),
		'whatsapp' => __( 'WhatsApp (ej: 56912345678; vacío = el general)', 'taller-temuco' ),
		'hours'    => __( 'Horario', 'taller-temuco' ),
		'lat'      => __( 'Latitud', 'taller-temuco' ),
		'lng'      => __( 'Longitud', 'taller-temuco' ),
	);
}

/**
 * Tipo de contenido "Sucursales".
 */
function tt_register_branches() {
	register_post_type(
		'tt_sucursal',
		array(
			'labels'        => array(
				'name'          => __( 'Sucursales', 'taller-temuco' ),
				'singular_name' => __( 'Sucursal', 'taller-temuco' ),
				'add_new'       => __( 'Agregar sucursal', 'taller-temuco' ),
				'add_new_item'  => __( 'Agregar sucursal', 'taller-temuco' ),
				'edit_item'     => __( 'Editar sucursal', 'taller-temuco' ),
				'all_items'     => __( 'Todas las sucursales', 'taller-temuco' ),
				'not_found'     => __( 'No hay sucursales', 'taller-temuco' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'menu_position' => 26,
			'menu_icon'     => 'dashicons-location-alt',
			'supports'      => array( 'title', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'tt_register_branches' );

/**
 * Todas las sucursales publicadas, en orden. Si aún no hay ninguna, devuelve la ubicación
 * principal definida en el Personalizador (id = 0) para que el mapa nunca quede vacío.
 *
 * @param bool $with_fallback Incluir la ubicación del Personalizador si no hay sucursales.
 * @return array[]
 */
function tt_branches( $with_fallback = true ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		$posts = get_posts(
			array(
				'post_type'      => 'tt_sucursal',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);
		foreach ( $posts as $p ) {
			$b = array(
				'id'   => $p->ID,
				'name' => get_the_title( $p ),
			);
			foreach ( array_keys( tt_branch_fields() ) as $k ) {
				$b[ $k ] = (string) get_post_meta( $p->ID, '_tt_b_' . $k, true );
			}
			$cache[] = tt_branch_complete( $b );
		}
	}
	if ( $cache || ! $with_fallback ) {
		return $cache;
	}
	return array(
		tt_branch_complete(
			array(
				'id'       => 0,
				'name'     => tt_opt( 'business_name' ),
				'address'  => tt_opt( 'address' ),
				'city'     => 'Temuco',
				'phone'    => tt_opt( 'phone' ),
				'whatsapp' => '',
				'hours'    => tt_opt( 'hours' ),
				'lat'      => tt_opt( 'lat' ),
				'lng'      => tt_opt( 'lng' ),
			)
		),
	);
}

/**
 * Completa valores por defecto y enlaces (cómo llegar, Waze, WhatsApp, teléfono).
 *
 * @param array $b Sucursal.
 */
function tt_branch_complete( $b ) {
	$b['phone'] = $b['phone'] ? $b['phone'] : tt_opt( 'phone' );
	$b['hours'] = $b['hours'] ? $b['hours'] : tt_opt( 'hours' );
	$has_geo    = is_numeric( $b['lat'] ) && is_numeric( $b['lng'] );
	$full       = trim( $b['address'] . ( $b['city'] && false === stripos( $b['address'], $b['city'] ) ? ', ' . $b['city'] : '' ) );

	$b['full_address'] = $full;
	$b['lat']          = $has_geo ? (float) $b['lat'] : null;
	$b['lng']          = $has_geo ? (float) $b['lng'] : null;
	$b['directions']   = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $has_geo ? $b['lat'] . ',' . $b['lng'] : $full . ', Chile' );
	$b['waze']         = $has_geo ? 'https://waze.com/ul?ll=' . $b['lat'] . ',' . $b['lng'] . '&navigate=yes' : 'https://waze.com/ul?q=' . rawurlencode( $full ) . '&navigate=yes';
	$b['tel']          = 'tel:+' . ltrim( preg_replace( '/\D/', '', $b['phone'] ), '+' );

	$wa = preg_replace( '/\D/', '', (string) $b['whatsapp'] );
	if ( 9 === strlen( $wa ) && '9' === $wa[0] ) {
		$wa = '56' . $wa;
	}
	$msg     = sprintf( 'Hola, quiero cotizar un servicio en la sucursal %s.', $b['name'] );
	$b['wa'] = $wa ? 'https://wa.me/' . $wa . '?text=' . rawurlencode( $msg ) : tt_whatsapp_url( $msg );
	return $b;
}

/* ---------- Ficha de sucursal en el escritorio ---------- */

/**
 * Caja con los datos y un mapa para marcar la ubicación.
 */
function tt_branch_boxes() {
	add_meta_box( 'tt_branch', __( 'Datos y ubicación de la sucursal', 'taller-temuco' ), 'tt_branch_box_html', 'tt_sucursal', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'tt_branch_boxes' );

/**
 * HTML de la ficha.
 *
 * @param WP_Post $post Sucursal.
 */
function tt_branch_box_html( $post ) {
	wp_nonce_field( 'tt_branch_save', 'tt_branch_nonce' );
	$v = function ( $k ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_tt_b_' . $k, true );
	};
	echo '<table class="form-table" role="presentation">';
	foreach ( tt_branch_fields() as $k => $label ) {
		if ( in_array( $k, array( 'lat', 'lng' ), true ) ) {
			continue;
		}
		printf(
			'<tr><th><label for="tt_b_%1$s">%2$s</label></th><td><input type="text" class="regular-text" id="tt_b_%1$s" name="tt_b[%1$s]" value="%3$s"></td></tr>',
			esc_attr( $k ),
			esc_html( $label ),
			esc_attr( $v( $k ) )
		);
	}
	echo '</table>';
	?>
	<h3><?php esc_html_e( 'Ubicación en el mapa', 'taller-temuco' ); ?></h3>
	<p class="description"><?php esc_html_e( 'Haz clic en el mapa donde está la sucursal (puedes arrastrar el marcador), o usa "Buscar dirección".', 'taller-temuco' ); ?></p>
	<p>
		<button type="button" class="button" id="tt_b_geocode"><?php esc_html_e( 'Buscar dirección en el mapa', 'taller-temuco' ); ?></button>
		<span id="tt_b_geostatus" class="description"></span>
	</p>
	<div id="tt_b_map" style="height:340px;border:1px solid #c3c4c7;border-radius:4px"></div>
	<p>
		<label><?php esc_html_e( 'Latitud', 'taller-temuco' ); ?> <input type="text" id="tt_b_lat" name="tt_b[lat]" value="<?php echo esc_attr( $v( 'lat' ) ); ?>" size="12"></label>
		<label><?php esc_html_e( 'Longitud', 'taller-temuco' ); ?> <input type="text" id="tt_b_lng" name="tt_b[lng]" value="<?php echo esc_attr( $v( 'lng' ) ); ?>" size="12"></label>
	</p>
	<p class="description"><?php esc_html_e( 'El orden de las sucursales se cambia en "Atributos de página → Orden" (la primera es la casa matriz).', 'taller-temuco' ); ?></p>
	<script>
	window.addEventListener('load', function () {
		if (!window.L) { return; }
		var lat = document.getElementById('tt_b_lat'), lng = document.getElementById('tt_b_lng');
		var start = (lat.value && lng.value) ? [ +lat.value, +lng.value ] : [ <?php echo (float) tt_opt( 'lat' ); ?>, <?php echo (float) tt_opt( 'lng' ); ?> ];
		var map = L.map('tt_b_map').setView(start, lat.value ? 16 : 13);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
		var marker = null;
		function place(ll, zoom) {
			if (!marker) {
				marker = L.marker(ll, { draggable: true }).addTo(map);
				marker.on('dragend', function () { set(marker.getLatLng()); });
			}
			marker.setLatLng(ll); set(L.latLng(ll));
			if (zoom) { map.setView(ll, zoom); }
		}
		function set(ll) { lat.value = ll.lat.toFixed(6); lng.value = ll.lng.toFixed(6); }
		if (lat.value && lng.value) { place(start); }
		map.on('click', function (e) { place(e.latlng); });
		document.getElementById('tt_b_geocode').addEventListener('click', function () {
			var st = document.getElementById('tt_b_geostatus');
			var q = [ document.getElementById('tt_b_address').value, document.getElementById('tt_b_city').value, 'Chile' ].filter(Boolean).join(', ');
			st.textContent = 'Buscando…';
			fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=cl&q=' + encodeURIComponent(q))
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (!d.length) { st.textContent = 'No se encontró; marca el punto haciendo clic en el mapa.'; return; }
					place([ +d[0].lat, +d[0].lon ], 17); st.textContent = 'Encontrado. Ajusta el marcador si es necesario.';
				})
				.catch(function () { st.textContent = 'No se pudo buscar; marca el punto haciendo clic en el mapa.'; });
		});
	});
	</script>
	<?php
}

/**
 * Carga Leaflet en la ficha de sucursal.
 *
 * @param string $hook Pantalla.
 */
function tt_branch_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( $screen && 'tt_sucursal' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_style( 'leaflet', TT_LEAFLET_CSS, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
		wp_enqueue_script( 'leaflet', TT_LEAFLET_JS, array(), null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
	}
}
add_action( 'admin_enqueue_scripts', 'tt_branch_admin_assets' );

/**
 * Integridad (SRI) para Leaflet: el navegador rechaza el archivo si alguien lo altera.
 *
 * @param string $tag    Etiqueta.
 * @param string $handle Identificador.
 */
function tt_leaflet_sri( $tag, $handle ) {
	if ( 'leaflet' !== $handle ) {
		return $tag;
	}
	if ( false !== strpos( $tag, '<script' ) ) {
		return str_replace( '<script ', '<script integrity="' . TT_LEAFLET_JS_SRI . '" crossorigin="" ', $tag );
	}
	return str_replace( '<link ', '<link integrity="' . TT_LEAFLET_CSS_SRI . '" crossorigin="" ', $tag );
}
add_filter( 'script_loader_tag', 'tt_leaflet_sri', 10, 2 );
add_filter( 'style_loader_tag', 'tt_leaflet_sri', 10, 2 );

/**
 * Guarda la ficha.
 *
 * @param int $post_id Sucursal.
 */
function tt_branch_save( $post_id ) {
	if ( ! isset( $_POST['tt_branch_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tt_branch_nonce'] ) ), 'tt_branch_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['tt_b'] ) ? (array) wp_unslash( $_POST['tt_b'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( array_keys( tt_branch_fields() ) as $k ) {
		if ( ! isset( $in[ $k ] ) ) {
			continue;
		}
		$val = sanitize_text_field( $in[ $k ] );
		if ( 'lat' === $k || 'lng' === $k ) {
			$val   = str_replace( ',', '.', $val );
			$limit = 'lat' === $k ? 90 : 180;
			$val   = ( is_numeric( $val ) && abs( (float) $val ) <= $limit ) ? (string) round( (float) $val, 6 ) : '';
		}
		update_post_meta( $post_id, '_tt_b_' . $k, $val );
	}
}
add_action( 'save_post_tt_sucursal', 'tt_branch_save' );

/**
 * Columnas del listado de sucursales.
 *
 * @param array $c Columnas.
 */
function tt_branch_columns( $c ) {
	return array(
		'cb'        => $c['cb'],
		'title'     => __( 'Sucursal', 'taller-temuco' ),
		'tt_addr'   => __( 'Dirección', 'taller-temuco' ),
		'tt_phone'  => __( 'Teléfono', 'taller-temuco' ),
		'tt_geo'    => __( 'En el mapa', 'taller-temuco' ),
	);
}
add_filter( 'manage_tt_sucursal_posts_columns', 'tt_branch_columns' );

/**
 * Celdas.
 *
 * @param string $col Columna.
 * @param int    $id  Sucursal.
 */
function tt_branch_column( $col, $id ) {
	if ( 'tt_addr' === $col ) {
		echo esc_html( trim( get_post_meta( $id, '_tt_b_address', true ) . ', ' . get_post_meta( $id, '_tt_b_city', true ), ', ' ) );
	} elseif ( 'tt_phone' === $col ) {
		echo esc_html( get_post_meta( $id, '_tt_b_phone', true ) );
	} elseif ( 'tt_geo' === $col ) {
		echo get_post_meta( $id, '_tt_b_lat', true ) ? '✅' : '<span style="color:#b32d2e">' . esc_html__( 'Falta marcar', 'taller-temuco' ) . '</span>';
	}
}
add_action( 'manage_tt_sucursal_posts_custom_column', 'tt_branch_column', 10, 2 );

/* ---------- Carga inicial y shortcode ---------- */

/**
 * Crea la "Casa matriz" a partir de los datos del Personalizador (una sola vez).
 */
function tt_seed_branches() {
	if ( get_option( 'tt_seeded_branches' ) || ! post_type_exists( 'tt_sucursal' ) ) {
		return;
	}
	update_option( 'tt_seeded_branches', 1 );
	if ( tt_branches( false ) ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'tt_sucursal',
			'post_status' => 'publish',
			'post_title'  => __( 'Casa matriz Temuco', 'taller-temuco' ),
			'menu_order'  => 0,
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		$meta = array(
			'address' => tt_opt( 'address' ),
			'city'    => 'Temuco',
			'phone'   => tt_opt( 'phone' ),
			'hours'   => tt_opt( 'hours' ),
			'lat'     => tt_opt( 'lat' ),
			'lng'     => tt_opt( 'lng' ),
		);
		foreach ( $meta as $k => $val ) {
			update_post_meta( $id, '_tt_b_' . $k, $val );
		}
	}
}
add_action( 'after_switch_theme', 'tt_seed_branches', 20 );
add_action( 'admin_init', 'tt_seed_branches' );

/**
 * Shortcode [taller_mapa] para insertar el mapa de sucursales en cualquier página.
 */
function tt_map_shortcode() {
	ob_start();
	get_template_part( 'template-parts/map' );
	return ob_get_clean();
}
add_shortcode( 'taller_mapa', 'tt_map_shortcode' );

/**
 * URL de la sección de ubicación (página Contacto o, si no existe, el Home).
 */
function tt_location_url() {
	$page = get_page_by_path( 'contacto' );
	return ( $page ? get_permalink( $page ) : home_url( '/' ) ) . '#ubicacion';
}
