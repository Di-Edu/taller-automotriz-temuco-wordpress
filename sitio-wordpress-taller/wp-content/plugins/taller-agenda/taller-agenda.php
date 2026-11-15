<?php
/**
 * Plugin Name: Taller Agenda
 * Description: Agenda de citas simple para talleres: formulario [taller_agenda] con horarios disponibles en tiempo real, cupos por bloque, días bloqueados, aviso por correo y gestión de citas en el escritorio de WordPress (sin servicios externos ni pagos mensuales).
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: taller-agenda
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

define( 'TTA_VERSION', '1.0.0' );
define( 'TTA_FILE', __FILE__ );
define( 'TTA_DIR', plugin_dir_path( __FILE__ ) );

require TTA_DIR . 'includes/class-settings.php';
require TTA_DIR . 'includes/class-booking.php';
require TTA_DIR . 'includes/class-admin.php';
require TTA_DIR . 'includes/class-form.php';
require TTA_DIR . 'includes/class-stock.php';

add_action(
	'plugins_loaded',
	function () {
		TTA_Settings::init();
		TTA_Booking::init();
		TTA_Admin::init();
		TTA_Form::init();
		TTA_Stock::init();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		TTA_Booking::register_post_type();
		flush_rewrite_rules();
	}
);
