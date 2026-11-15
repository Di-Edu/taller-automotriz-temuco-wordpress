<?php
/**
 * Optimizaciones de velocidad sin plugins: se quita lo que WordPress carga por defecto
 * y el sitio no usa, y se agregan pistas de carga para el navegador.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Limpieza del <head>.
 */
function tt_cleanup_head() {
	// Emojis (≈15 KB de JS + CSS innecesarios).
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );

	// Etiquetas que no aportan.
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
add_action( 'init', 'tt_cleanup_head' );

/**
 * No cargar CSS de bloques que no se usan en el front (WP 5.8+ los carga por bloque).
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Quita CSS de estilos globales "classic-theme" que no se usa y el script de embeds.
 */
function tt_dequeue_unused() {
	if ( is_admin() ) {
		return;
	}
	wp_dequeue_style( 'classic-theme-styles' );
	wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_enqueue_scripts', 'tt_dequeue_unused', 100 );

/**
 * Precarga la imagen de portada del Home (mejora LCP).
 */
function tt_preload_hero() {
	if ( is_front_page() && tt_opt( 'hero_image' ) ) {
		echo '<link rel="preload" as="image" href="' . esc_url( tt_opt( 'hero_image' ) ) . '" fetchpriority="high">' . "\n";
	}
}
add_action( 'wp_head', 'tt_preload_hero', 1 );

/**
 * Las imágenes del contenido se cargan diferidas (WP ya lo hace) y con decoding async.
 *
 * @param array $attr Atributos.
 */
function tt_img_attrs( $attr ) {
	$attr['decoding'] = 'async';
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'tt_img_attrs' );

/**
 * Limita revisiones guardadas para mantener liviana la base de datos.
 */
add_filter(
	'wp_revisions_to_keep',
	function () {
		return 10;
	}
);
