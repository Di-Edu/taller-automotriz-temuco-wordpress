<?php
/**
 * SEO básico integrado: título SEO y meta descripción por página (caja "SEO" en el editor),
 * Open Graph y datos estructurados (AutoRepair / Service / BreadcrumbList).
 * Si el cliente instala Yoast SEO o Rank Math, este módulo se desactiva solo para no duplicar etiquetas.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * ¿Hay un plugin SEO dedicado activo?
 */
function tt_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/* ---------- Caja "SEO" en el editor ---------- */

/**
 * Registra los metadatos para que también funcionen en el editor de bloques / REST.
 */
function tt_register_seo_meta() {
	foreach ( array( 'page', 'post' ) as $type ) {
		foreach ( array( '_tt_seo_title', '_tt_seo_desc' ) as $key ) {
			register_post_meta(
				$type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'tt_register_seo_meta' );

/**
 * Agrega la caja SEO.
 */
function tt_seo_add_box() {
	if ( tt_seo_plugin_active() ) {
		return;
	}
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'tt_seo', __( 'SEO (Google)', 'taller-temuco' ), 'tt_seo_box_html', $type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'tt_seo_add_box' );

/**
 * HTML de la caja SEO, con contador de caracteres y vista previa estilo Google.
 *
 * @param WP_Post $post Entrada.
 */
function tt_seo_box_html( $post ) {
	wp_nonce_field( 'tt_seo_save', 'tt_seo_nonce' );
	$title = get_post_meta( $post->ID, '_tt_seo_title', true );
	$desc  = get_post_meta( $post->ID, '_tt_seo_desc', true );
	?>
	<p>
		<label for="tt_seo_title"><strong><?php esc_html_e( 'Título SEO', 'taller-temuco' ); ?></strong> <span class="description">(<?php esc_html_e( 'ideal 50–60 caracteres; vacío = título de la página', 'taller-temuco' ); ?>)</span></label>
		<input type="text" id="tt_seo_title" name="tt_seo_title" value="<?php echo esc_attr( $title ); ?>" class="widefat" maxlength="90" data-tt-count="60">
	</p>
	<p>
		<label for="tt_seo_desc"><strong><?php esc_html_e( 'Meta descripción', 'taller-temuco' ); ?></strong> <span class="description">(<?php esc_html_e( 'ideal 140–160 caracteres; incluye servicio + Temuco + llamado a la acción', 'taller-temuco' ); ?>)</span></label>
		<textarea id="tt_seo_desc" name="tt_seo_desc" rows="3" class="widefat" maxlength="200" data-tt-count="160"><?php echo esc_textarea( $desc ); ?></textarea>
	</p>
	<script>
	document.querySelectorAll('[data-tt-count]').forEach(function (el) {
		var max = +el.dataset.ttCount, out = document.createElement('small');
		el.after(out);
		function upd() { out.textContent = el.value.length + ' / ' + max; out.style.color = el.value.length > max ? '#b32d2e' : '#50575e'; }
		el.addEventListener('input', upd); upd();
	});
	</script>
	<?php
}

/**
 * Guarda la caja SEO.
 *
 * @param int $post_id ID.
 */
function tt_seo_save( $post_id ) {
	if ( ! isset( $_POST['tt_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tt_seo_nonce'] ) ), 'tt_seo_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'tt_seo_title' => '_tt_seo_title', 'tt_seo_desc' => '_tt_seo_desc' ) as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
}
add_action( 'save_post', 'tt_seo_save' );

/* ---------- Salida en <head> ---------- */

/**
 * Título del documento.
 *
 * @param string $title Título.
 */
function tt_seo_document_title( $title ) {
	if ( tt_seo_plugin_active() ) {
		return $title;
	}
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_tt_seo_title', true );
		if ( $custom ) {
			return $custom;
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'tt_seo_document_title', 20 );

/**
 * Separador del título.
 */
add_filter(
	'document_title_separator',
	function () {
		return '|';
	}
);

/**
 * Meta descripción efectiva de la página actual.
 */
function tt_seo_current_description() {
	$desc = '';
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = get_post_meta( $id, '_tt_seo_desc', true );
		if ( ! $desc ) {
			$post = get_post( $id );
			$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28, '…' );
		}
	}
	if ( ! $desc && ( is_front_page() || is_home() ) ) {
		$desc = tt_opt( 'hero_text' );
	}
	if ( ! $desc ) {
		$desc = get_bloginfo( 'description' );
	}
	return trim( preg_replace( '/\s+/', ' ', $desc ) );
}

/**
 * Imprime meta descripción, Open Graph y JSON-LD.
 */
function tt_seo_head() {
	if ( tt_seo_plugin_active() ) {
		tt_schema_output();
		return;
	}

	$desc  = tt_seo_current_description();
	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink() : home_url( '/' );
	$image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'tt-hero' );
	} elseif ( tt_opt( 'hero_image' ) ) {
		$image = tt_opt( 'hero_image' );
	}

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:locale" content="es_CL">' . "\n";
	echo '<meta property="og:type" content="' . ( is_front_page() ? 'website' : 'article' ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( tt_opt( 'business_name' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="geo.region" content="CL-AR">' . "\n";
	echo '<meta name="geo.placename" content="Temuco">' . "\n";
	if ( tt_opt( 'lat' ) && tt_opt( 'lng' ) ) {
		echo '<meta name="geo.position" content="' . esc_attr( tt_opt( 'lat' ) . ';' . tt_opt( 'lng' ) ) . '">' . "\n";
	}

	tt_schema_output();
}
add_action( 'wp_head', 'tt_seo_head', 2 );

/**
 * Datos estructurados (schema.org) para SEO local.
 */
function tt_schema_output() {
	$graph    = array();
	$business = array(
		'@type'       => 'AutoRepair',
		'@id'         => home_url( '/#negocio' ),
		'name'        => tt_opt( 'business_name' ),
		'url'         => home_url( '/' ),
		'telephone'   => tt_opt( 'phone' ),
		'email'       => tt_opt( 'email' ),
		'priceRange'  => '$$',
		'description' => tt_opt( 'footer_text' ),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => tt_opt( 'address' ),
			'addressLocality' => 'Temuco',
			'addressRegion'   => 'Araucanía',
			'addressCountry'  => 'CL',
		),
		'areaServed'  => array_map(
			function ( $c ) {
				return array(
					'@type' => 'City',
					'name'  => trim( $c ),
				);
			},
			array_filter( explode( ',', (string) tt_opt( 'areas' ) ) )
		),
	);
	if ( tt_opt( 'lat' ) && tt_opt( 'lng' ) ) {
		$business['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) tt_opt( 'lat' ),
			'longitude' => (float) tt_opt( 'lng' ),
		);
	}
	if ( has_custom_logo() ) {
		$business['image'] = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
	} elseif ( tt_opt( 'hero_image' ) ) {
		$business['image'] = tt_opt( 'hero_image' );
	}
	$same = array_values( array_filter( array( tt_opt( 'instagram' ), tt_opt( 'facebook' ), tt_opt( 'google_reviews' ) ) ) );
	if ( $same ) {
		$business['sameAs'] = $same;
	}
	$graph[] = $business;

	if ( is_page() && is_page_template( 'page-servicio.php' ) ) {
		$id      = get_queried_object_id();
		$graph[] = array(
			'@type'       => 'Service',
			'name'        => get_the_title( $id ),
			'serviceType' => get_the_title( $id ),
			'description' => tt_seo_current_description(),
			'provider'    => array( '@id' => home_url( '/#negocio' ) ),
			'areaServed'  => $business['areaServed'],
			'url'         => get_permalink( $id ),
		);

		$crumbs   = array();
		$crumbs[] = array( 'Inicio', home_url( '/' ) );
		$parent   = wp_get_post_parent_id( $id );
		if ( $parent ) {
			$crumbs[] = array( get_the_title( $parent ), get_permalink( $parent ) );
		}
		$crumbs[] = array( get_the_title( $id ), get_permalink( $id ) );
		$items    = array();
		foreach ( $crumbs as $i => $c ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $c[0],
				'item'     => $c[1],
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . '</script>' . "\n";
}
