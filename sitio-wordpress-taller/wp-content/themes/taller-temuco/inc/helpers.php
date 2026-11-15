<?php
/**
 * Funciones auxiliares.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valores por defecto de todas las opciones del Personalizador.
 * El cliente los cambia en Apariencia → Personalizar, sin tocar código.
 */
function tt_defaults() {
	return array(
		'business_name'   => 'Taller Automotriz Temuco',
		'brand_color'     => '#e8590c',
		'phone'           => '+56 9 1234 5678',
		'whatsapp'        => '56912345678',
		'whatsapp_msg'    => 'Hola, quiero cotizar un servicio para mi vehículo.',
		'whatsapp_show'   => true,
		'email'           => 'contacto@tutaller.cl',
		'address'         => 'Av. Alemania 0000, Temuco, Araucanía',
		'hours'           => 'Lunes a viernes 9:00–18:30 · Sábado 9:00–13:30',
		'map_query'       => 'Temuco, Araucanía, Chile',
		'lat'             => '-38.7359',
		'lng'             => '-72.5904',
		'hero_kicker'     => 'Servicio automotriz en Temuco',
		'hero_title'      => 'Tu vehículo en manos expertas, con diagnóstico claro y precio justo',
		'hero_text'       => 'Mantención, frenos, scanner, suspensión y más. Cotiza por WhatsApp en minutos o agenda tu hora online. Atendemos Temuco, Padre Las Casas, Labranza y alrededores.',
		'hero_cta'        => 'Agendar hora',
		'hero_image'      => '',
		'stat_1'          => '+15|años de experiencia',
		'stat_2'          => '+5.000|vehículos atendidos',
		'stat_3'          => '4,9 ★|valoración de clientes',
		'stat_4'          => '6 meses|garantía en trabajos',
		'why_1'           => 'Diagnóstico transparente|Te mostramos el problema con fotos y scanner antes de reparar. Sin sorpresas en la cuenta.',
		'why_2'           => 'Presupuesto por escrito|Cotización detallada de repuestos y mano de obra antes de comenzar cualquier trabajo.',
		'why_3'           => 'Repuestos de calidad|Trabajamos con repuestos originales o alternativos certificados, a tu elección.',
		'why_4'           => 'Entrega en el día|La mayoría de las mantenciones y reparaciones de frenos se entregan el mismo día.',
		'testi_1'         => 'Muy buena atención, me explicaron todo lo que tenía el auto y quedó impecable. 100% recomendado.|Carolina M.|Temuco',
		'testi_2'         => 'Agendé por la web y me atendieron a la hora. Precio justo y entrega el mismo día.|Rodrigo S.|Padre Las Casas',
		'testi_3'         => 'Llevé la camioneta por ruido en la suspensión, lo detectaron rápido y quedó perfecta.|Felipe A.|Labranza',
		'areas'           => 'Temuco, Padre Las Casas, Labranza, Lautaro, Vilcún, Freire, Cajón, Nueva Imperial',
		'final_title'     => '¿Tu auto necesita atención? Agenda hoy y evita una falla mayor',
		'final_text'      => 'Respondemos por WhatsApp en horario de atención. Cupos limitados por día.',
		'instagram'       => '',
		'facebook'        => '',
		'google_reviews'  => '',
		'footer_text'     => 'Servicio automotriz integral en Temuco. Mantención, reparación y diagnóstico con garantía.',
	);
}

/**
 * Lee una opción del tema con su valor por defecto.
 *
 * @param string $key Clave.
 * @return mixed
 */
function tt_opt( $key ) {
	$defaults = tt_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'tt_' . $key, $default );
}

/**
 * Separa un campo "a|b|c" en partes.
 *
 * @param string $key Clave.
 * @return string[]
 */
function tt_parts( $key ) {
	return array_map( 'trim', explode( '|', (string) tt_opt( $key ) ) );
}

/**
 * Solo dígitos del número de WhatsApp (formato internacional sin +).
 */
function tt_whatsapp_number() {
	$n = preg_replace( '/\D/', '', (string) tt_opt( 'whatsapp' ) );
	if ( 9 === strlen( $n ) && '9' === $n[0] ) {
		$n = '56' . $n; // Celular chileno sin código de país.
	}
	return $n;
}

/**
 * URL de WhatsApp con mensaje prellenado.
 *
 * @param string $msg Mensaje (opcional).
 */
function tt_whatsapp_url( $msg = '' ) {
	$msg = $msg ? $msg : tt_opt( 'whatsapp_msg' );
	return 'https://wa.me/' . tt_whatsapp_number() . '?text=' . rawurlencode( $msg );
}

/**
 * Enlace tel: del teléfono.
 */
function tt_tel_href() {
	return 'tel:+' . ltrim( preg_replace( '/\D/', '', (string) tt_opt( 'phone' ) ), '+' );
}

/**
 * Página de agenda (la que contiene el shortcode [taller_agenda]).
 */
function tt_booking_url() {
	$id = (int) get_option( 'tt_booking_page_id' );
	if ( $id && get_post_status( $id ) === 'publish' ) {
		return get_permalink( $id );
	}
	$page = get_page_by_path( 'agendar-hora' );
	return $page ? get_permalink( $page ) : home_url( '/agendar-hora/' );
}

/**
 * Páginas de servicio = páginas hijas de "Servicios", ordenadas por orden de menú.
 *
 * @return WP_Post[]
 */
function tt_services() {
	$parent = (int) get_option( 'tt_services_page_id' );
	if ( ! $parent ) {
		$p      = get_page_by_path( 'servicios' );
		$parent = $p ? $p->ID : 0;
	}
	if ( ! $parent ) {
		return array();
	}
	return get_pages(
		array(
			'parent'      => $parent,
			'sort_column' => 'menu_order,post_title',
			'post_status' => 'publish',
		)
	);
}

/**
 * Resumen corto de un servicio (extracto o meta descripción).
 *
 * @param WP_Post $post Página.
 */
function tt_service_summary( $post ) {
	if ( has_excerpt( $post ) ) {
		return get_the_excerpt( $post );
	}
	$desc = get_post_meta( $post->ID, '_tt_seo_desc', true );
	return $desc ? $desc : wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 );
}

/**
 * Íconos SVG en línea (sin fuentes de íconos externas = carga más rápida).
 *
 * @param string $name Nombre.
 */
function tt_icon( $name ) {
	$p = array(
		'whatsapp' => '<path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-1 1.2-.4.2-.7.1a8 8 0 0 1-4-3.5c-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8.9 3.8.8a3.2 3.2 0 0 0 2.1-1.5 2.6 2.6 0 0 0 .2-1.5c-.1-.1-.3-.2-.6-.3zM12 21.8a9.9 9.9 0 0 1-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8zm8.4-18.2A11.8 11.8 0 0 0 1.7 17.8L0 24l6.3-1.7a11.8 11.8 0 0 0 5.7 1.5 11.8 11.8 0 0 0 8.4-20.2z" fill="currentColor"/>',
		'phone'    => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.3 1z" fill="currentColor"/>',
		'calendar' => '<path d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm-2 8h14v10H5zm2 2v2h2v-2zm4 0v2h2v-2zm4 0v2h2v-2z" fill="currentColor"/>',
		'pin'      => '<path d="M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 14.5 9 2.5 2.5 0 0 1 12 11.5z" fill="currentColor"/>',
		'clock'    => '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 10.6 3.5 2.1-.8 1.3L11 13V7h2z" fill="currentColor"/>',
		'check'    => '<path d="m9 16.2-4.2-4.2-1.4 1.4L9 19 21 7l-1.4-1.4z" fill="currentColor"/>',
		'wrench'   => '<path d="M22.7 19 13.6 9.9a6.5 6.5 0 0 0-8.4-8.3l4.4 4.3-3.1 3.1L2.1 4.6A6.5 6.5 0 0 0 10.4 13l9.1 9.1a1 1 0 0 0 1.4 0l1.8-1.8a1 1 0 0 0 0-1.3z" fill="currentColor"/>',
		'arrow'    => '<path d="M5 11h11.2l-4.9-4.9L12.7 4.7 20 12l-7.3 7.3-1.4-1.4 4.9-4.9H5z" fill="currentColor"/>',
		'star'     => '<path d="m12 17.3 6.2 3.7-1.6-7 5.4-4.7-7.2-.6L12 2 9.2 8.7 2 9.3l5.4 4.7-1.6 7z" fill="currentColor"/>',
		'shield'   => '<path d="M12 1 3 5v6c0 5.6 3.8 10.7 9 12 5.2-1.3 9-6.4 9-12V5zm-2 16-4-4 1.4-1.4 2.6 2.6 6.6-6.6L18 9z" fill="currentColor"/>',
		'menu'     => '<path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z" fill="currentColor"/>',
	);
	$path = isset( $p[ $name ] ) ? $p[ $name ] : $p['check'];
	return '<svg class="icon icon-' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">' . $path . '</svg>';
}
