<?php
/**
 * Taller Temuco — funciones del tema.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

define( 'TT_VERSION', '1.0.0' );
define( 'TT_DIR', get_template_directory() );
define( 'TT_URI', get_template_directory_uri() );

require TT_DIR . '/inc/helpers.php';
require TT_DIR . '/inc/customizer.php';
require TT_DIR . '/inc/branches.php';
require TT_DIR . '/inc/seo.php';
require TT_DIR . '/inc/performance.php';
require TT_DIR . '/inc/setup-content.php';

/**
 * Soportes del tema.
 */
function tt_setup() {
	load_theme_textdomain( 'taller-temuco', TT_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Menú principal', 'taller-temuco' ),
			'footer'  => __( 'Menú del pie', 'taller-temuco' ),
		)
	);

	add_image_size( 'tt-card', 640, 420, true );
	add_image_size( 'tt-hero', 1600, 900, true );
}
add_action( 'after_setup_theme', 'tt_setup' );

/**
 * CSS y JS (ligeros, sin jQuery ni librerías externas).
 */
function tt_assets() {
	wp_enqueue_style( 'tt-main', TT_URI . '/assets/css/main.css', array(), TT_VERSION );
	wp_add_inline_style( 'tt-main', tt_inline_brand_css() );

	wp_enqueue_script( 'tt-main', TT_URI . '/assets/js/main.js', array(), TT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'tt_assets' );

/**
 * Color de marca elegido en el Personalizador.
 */
function tt_inline_brand_css() {
	$brand = sanitize_hex_color( tt_opt( 'brand_color' ) );
	if ( ! $brand ) {
		return '';
	}
	return ':root{--brand:' . $brand . ';}';
}

/**
 * Zona de widgets en el pie (opcional, autoadministrable).
 */
function tt_widgets() {
	register_sidebar(
		array(
			'name'          => __( 'Pie de página', 'taller-temuco' ),
			'id'            => 'footer-1',
			'before_widget' => '<div class="footer-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="footer-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'tt_widgets' );

/**
 * Al activar el tema, WordPress mueve sus widgets por defecto (Archivos, Categorías…) al pie.
 * Se desactivan una sola vez para que el pie se vea limpio; el cliente puede agregar los suyos.
 */
function tt_clear_default_widgets() {
	if ( get_option( 'tt_widgets_cleared' ) ) {
		return;
	}
	$sidebars = get_option( 'sidebars_widgets', array() );
	if ( ! empty( $sidebars['footer-1'] ) ) {
		$inactive                        = isset( $sidebars['wp_inactive_widgets'] ) ? (array) $sidebars['wp_inactive_widgets'] : array();
		$sidebars['wp_inactive_widgets'] = array_merge( $inactive, (array) $sidebars['footer-1'] );
		$sidebars['footer-1']            = array();
		update_option( 'sidebars_widgets', $sidebars );
	}
	update_option( 'tt_widgets_cleared', 1 );
}
add_action( 'after_switch_theme', 'tt_clear_default_widgets', 30 );

/**
 * Menú de respaldo si el cliente aún no crea uno.
 */
function tt_fallback_menu() {
	echo '<ul class="menu">';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
		)
	);
	echo '</ul>';
}
