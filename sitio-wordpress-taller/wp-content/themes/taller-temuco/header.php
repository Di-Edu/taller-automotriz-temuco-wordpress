<?php
/**
 * Encabezado.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#111827">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#contenido"><?php esc_html_e( 'Saltar al contenido', 'taller-temuco' ); ?></a>

<div class="topbar">
	<div class="container topbar-inner">
		<?php $tt_nb = count( tt_branches() ); ?>
		<a class="topbar-location" href="<?php echo esc_url( tt_location_url() ); ?>" data-track="location_topbar">
			<?php echo tt_icon( 'pin' ); // phpcs:ignore ?>
			<?php
			if ( $tt_nb > 1 ) {
				/* translators: %d: número de sucursales */
				echo esc_html( sprintf( __( '%d sucursales · Ver mapa', 'taller-temuco' ), $tt_nb ) );
			} else {
				echo esc_html( tt_branches()[0]['full_address'] ) . ' · <u>' . esc_html__( 'Cómo llegar', 'taller-temuco' ) . '</u>';
			}
			?>
		</a>
		<span class="hide-sm"><?php echo tt_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'hours' ) ); ?></span>
	</div>
</div>

<header class="site-header" id="top">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( has_custom_logo() ) {
				echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'brand-logo', 'alt' => tt_opt( 'business_name' ), 'loading' => 'eager' ) );
			} else {
				echo '<span class="brand-mark">' . tt_icon( 'wrench' ) . '</span><span class="brand-name">' . esc_html( tt_opt( 'business_name' ) ) . '</span>'; // phpcs:ignore
			}
			?>
		</a>

		<div class="header-tools">
		<?php echo tt_cart_link(); // phpcs:ignore -- HTML generado y escapado en tt_cart_link() ?>
		<button class="nav-toggle" aria-expanded="false" aria-controls="menu-principal">
			<?php echo tt_icon( 'menu' ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Menú', 'taller-temuco' ); ?></span>
		</button>
		</div>

		<nav class="main-nav" id="menu-principal" aria-label="<?php esc_attr_e( 'Principal', 'taller-temuco' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'fallback_cb'    => 'tt_fallback_menu',
					'depth'          => 2,
				)
			);
			?>
			<div class="nav-ctas">
				<a class="btn btn-ghost" href="<?php echo esc_url( tt_tel_href() ); ?>"><?php echo tt_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( tt_opt( 'phone' ) ); ?></a>
				<a class="btn btn-primary" href="<?php echo esc_url( tt_booking_url() ); ?>"><?php echo tt_icon( 'calendar' ); // phpcs:ignore ?> <?php esc_html_e( 'Agendar hora', 'taller-temuco' ); ?></a>
			</div>
		</nav>
	</div>
</header>

<main id="contenido" class="site-main">
