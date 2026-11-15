<?php
/**
 * Integración con WooCommerce (gratuito). Solo se activa si el plugin WooCommerce está instalado.
 * - Tienda con el diseño del tema, ícono de carrito con contador en el encabezado.
 * - Las sucursales del tema se publican como puntos de "Retiro en sucursal" del checkout.
 * - Botón "Consultar por WhatsApp" en cada producto.
 * - Tras comprar un pack de servicio, invita a agendar la hora.
 * - Los archivos de WooCommerce solo se cargan en páginas de tienda (velocidad).
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * ¿Está activo WooCommerce?
 */
function tt_has_woo() {
	return class_exists( 'WooCommerce' );
}

/**
 * Soportes del tema para WooCommerce.
 */
function tt_woo_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 480,
			'single_image_width'    => 800,
			'product_grid'          => array(
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'tt_woo_setup' );

// Los plugins se cargan antes que el tema: si WooCommerce no está, no se registra nada más.
if ( tt_has_woo() ) {
	tt_woo_hooks();
}

/**
 * Registra los ganchos de WooCommerce.
 */
function tt_woo_hooks() {
	// El título lo pone woocommerce.php en la cabecera oscura.
	add_filter( 'woocommerce_show_page_title', '__return_false' );

	// 4 productos por fila y 12 por página.
	add_filter(
		'loop_shop_columns',
		function () {
			return 4;
		}
	);
	add_filter(
		'loop_shop_per_page',
		function () {
			return 12;
		}
	);

	add_filter( 'woocommerce_add_to_cart_fragments', 'tt_cart_fragment' );
	add_action( 'woocommerce_single_product_summary', 'tt_product_whatsapp', 35 );
	add_action( 'woocommerce_thankyou', 'tt_thankyou_booking', 5 );
	add_action( 'wp_enqueue_scripts', 'tt_woo_trim_assets', 99 );
	add_action( 'save_post_tt_sucursal', 'tt_sync_pickup_locations', 20 );
	add_action( 'admin_init', 'tt_woo_first_run' );
}

/* ---------- Carrito en el encabezado ---------- */

/**
 * HTML del enlace al carrito.
 */
function tt_cart_link() {
	if ( ! tt_has_woo() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$count = WC()->cart->get_cart_contents_count();
	return '<a class="header-cart" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr( sprintf( /* translators: %d: productos */ __( 'Carrito: %d productos', 'taller-temuco' ), $count ) ) . '">'
		. '<svg class="icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7.2 14h9.9a2 2 0 0 0 1.8-1.1L22 6H5.2l-.9-2H1v2h2l3.6 7.6L5.2 16A2 2 0 0 0 7 19h12v-2H7l1.1-2z"/></svg>'
		. '<span class="cart-count' . ( $count ? '' : ' is-empty' ) . '">' . (int) $count . '</span></a>';
}

/**
 * Actualiza el contador al agregar productos sin recargar.
 *
 * @param array $fragments Fragmentos.
 */
function tt_cart_fragment( $fragments ) {
	$fragments['a.header-cart'] = tt_cart_link();
	return $fragments;
}

/* ---------- Producto ---------- */

/**
 * Botón "Consultar por WhatsApp" con el nombre del producto.
 */
function tt_product_whatsapp() {
	global $product;
	if ( ! $product ) {
		return;
	}
	$msg = sprintf( 'Hola, quiero consultar por el producto "%s" (%s).', $product->get_name(), get_permalink( $product->get_id() ) );
	echo '<p class="product-wa"><a class="btn btn-wa" href="' . esc_url( tt_whatsapp_url( $msg ) ) . '" target="_blank" rel="noopener" data-track="whatsapp_product">'
		. tt_icon( 'whatsapp' ) . ' ' . esc_html__( '¿Dudas? Consultar por WhatsApp', 'taller-temuco' ) . '</a></p>'; // phpcs:ignore
}

/**
 * Si el pedido incluye un producto de la categoría "Packs de servicio", invita a agendar.
 *
 * @param int $order_id Pedido.
 */
function tt_thankyou_booking( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	foreach ( $order->get_items() as $item ) {
		if ( has_term( 'packs-de-servicio', 'product_cat', $item->get_product_id() ) ) {
			echo '<div class="tt-thankyou-booking"><p><strong>' . esc_html__( '¡Compraste un pack de servicio!', 'taller-temuco' ) . '</strong> '
				. esc_html__( 'Elige ahora el día y la hora para atender tu vehículo.', 'taller-temuco' ) . '</p>'
				. '<a class="btn btn-primary" href="' . esc_url( tt_booking_url() ) . '">' . tt_icon( 'calendar' ) . ' ' . esc_html__( 'Agendar mi hora', 'taller-temuco' ) . '</a></div>'; // phpcs:ignore
			return;
		}
	}
}

/* ---------- Velocidad ---------- */

/**
 * Los estilos y scripts de WooCommerce solo se cargan en páginas de tienda.
 * En el resto del sitio (Home, servicios, agenda) no suman peso.
 */
function tt_woo_trim_assets() {
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return;
	}
	foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'wc-blocks-style', 'wc-blocks-vendors-style' ) as $style ) {
		wp_dequeue_style( $style );
	}
	foreach ( array( 'wc-cart-fragments', 'woocommerce', 'wc-add-to-cart', 'sourcebuster-js', 'wc-order-attribution' ) as $script ) {
		wp_dequeue_script( $script );
	}
}

/* ---------- Retiro en sucursal ---------- */

/**
 * Publica las sucursales como puntos de retiro del checkout de WooCommerce ("Retiro en sucursal").
 * Se ejecuta al guardar una sucursal y la primera vez que se activa WooCommerce.
 */
function tt_sync_pickup_locations() {
	if ( ! tt_has_woo() || ! function_exists( 'tt_branches' ) ) {
		return;
	}
	$locations = array();
	foreach ( tt_branches( false ) as $b ) {
		$locations[] = array(
			'name'    => $b['name'],
			'address' => array(
				'address_1' => $b['address'],
				'city'      => $b['city'] ? $b['city'] : 'Temuco',
				'state'     => 'CL-AR',
				'postcode'  => '',
				'country'   => 'CL',
			),
			'details' => trim( $b['hours'] . ( $b['phone'] ? ' · ' . $b['phone'] : '' ) ),
			'enabled' => true,
		);
	}
	if ( ! $locations ) {
		return;
	}
	update_option( 'pickup_location_pickup_locations', $locations );

	$settings = get_option( 'woocommerce_pickup_location_settings', array() );
	if ( empty( $settings ) ) {
		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled'    => 'yes',
				'title'      => __( 'Retiro en sucursal', 'taller-temuco' ),
				'tax_status' => 'taxable',
				'cost'       => '',
			)
		);
	}
}

/**
 * Primera vez con WooCommerce: sincroniza sucursales y agrega "Tienda" al menú principal.
 */
function tt_woo_first_run() {
	if ( get_option( 'tt_woo_ready' ) || ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}
	$shop = wc_get_page_id( 'shop' );
	if ( $shop <= 0 ) {
		return; // WooCommerce aún no crea sus páginas.
	}
	update_option( 'tt_woo_ready', 1 );
	tt_sync_pickup_locations();

	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		$items  = wp_get_nav_menu_items( $locations['primary'] );
		$exists = false;
		foreach ( (array) $items as $it ) {
			if ( (int) $it->object_id === $shop ) {
				$exists = true;
			}
		}
		if ( ! $exists ) {
			wp_update_nav_menu_item(
				$locations['primary'],
				0,
				array(
					'menu-item-object-id' => $shop,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => 3,
				)
			);
		}
	}
}
