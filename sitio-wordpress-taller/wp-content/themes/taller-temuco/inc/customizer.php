<?php
/**
 * Personalizador: todo el contenido del Home, datos de contacto y WhatsApp
 * se editan en Apariencia → Personalizar → "Taller: …", con vista previa en vivo.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra paneles, secciones y campos.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function tt_customize_register( $wp_customize ) {
	$d = tt_defaults();

	$sections = array(
		'tt_business' => array(
			'title'  => __( 'Taller: datos del negocio', 'taller-temuco' ),
			'fields' => array(
				'business_name' => array( 'Nombre del negocio', 'text' ),
				'brand_color'   => array( 'Color de marca', 'color' ),
				'phone'         => array( 'Teléfono visible', 'text' ),
				'email'         => array( 'Correo de contacto', 'email' ),
				'address'       => array( 'Dirección', 'text' ),
				'hours'         => array( 'Horario de atención', 'text' ),
				'map_query'     => array( 'Búsqueda para el mapa (Google Maps)', 'text' ),
				'lat'           => array( 'Latitud (para Google / datos estructurados)', 'text' ),
				'lng'           => array( 'Longitud', 'text' ),
				'areas'         => array( 'Comunas que atiende (separadas por coma)', 'textarea' ),
				'instagram'     => array( 'URL Instagram', 'url' ),
				'facebook'      => array( 'URL Facebook', 'url' ),
				'google_reviews'=> array( 'URL reseñas de Google', 'url' ),
				'footer_text'   => array( 'Texto del pie de página', 'textarea' ),
			),
		),
		'tt_whatsapp' => array(
			'title'  => __( 'Taller: WhatsApp', 'taller-temuco' ),
			'fields' => array(
				'whatsapp_show' => array( 'Mostrar botón flotante de WhatsApp', 'checkbox' ),
				'whatsapp'      => array( 'Número WhatsApp (ej: 56912345678)', 'text' ),
				'whatsapp_msg'  => array( 'Mensaje prellenado', 'textarea' ),
			),
		),
		'tt_home'     => array(
			'title'       => __( 'Taller: página de inicio', 'taller-temuco' ),
			'description' => __( 'Los campos con "|" se separan así: Título|Texto. Deja un campo vacío para ocultarlo.', 'taller-temuco' ),
			'fields'      => array(
				'hero_kicker' => array( 'Portada: antetítulo', 'text' ),
				'hero_title'  => array( 'Portada: título principal (H1)', 'textarea' ),
				'hero_text'   => array( 'Portada: texto', 'textarea' ),
				'hero_cta'    => array( 'Portada: texto del botón principal', 'text' ),
				'hero_image'  => array( 'Portada: imagen de fondo', 'image' ),
				'stat_1'      => array( 'Cifra 1 (valor|texto)', 'text' ),
				'stat_2'      => array( 'Cifra 2', 'text' ),
				'stat_3'      => array( 'Cifra 3', 'text' ),
				'stat_4'      => array( 'Cifra 4', 'text' ),
				'why_1'       => array( 'Por qué elegirnos 1 (título|texto)', 'textarea' ),
				'why_2'       => array( 'Por qué elegirnos 2', 'textarea' ),
				'why_3'       => array( 'Por qué elegirnos 3', 'textarea' ),
				'why_4'       => array( 'Por qué elegirnos 4', 'textarea' ),
				'testi_1'     => array( 'Testimonio 1 (texto|nombre|comuna)', 'textarea' ),
				'testi_2'     => array( 'Testimonio 2', 'textarea' ),
				'testi_3'     => array( 'Testimonio 3', 'textarea' ),
				'final_title' => array( 'Llamado final: título', 'text' ),
				'final_text'  => array( 'Llamado final: texto', 'textarea' ),
			),
		),
	);

	$priority = 30;
	foreach ( $sections as $section_id => $section ) {
		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $section['title'],
				'description' => isset( $section['description'] ) ? $section['description'] : '',
				'priority'    => $priority++,
			)
		);

		foreach ( $section['fields'] as $key => $field ) {
			list( $label, $type ) = $field;
			$setting_id           = 'tt_' . $key;

			switch ( $type ) {
				case 'color':
					$sanitize = 'sanitize_hex_color';
					break;
				case 'email':
					$sanitize = 'sanitize_email';
					break;
				case 'url':
				case 'image':
					$sanitize = 'esc_url_raw';
					break;
				case 'checkbox':
					$sanitize = 'tt_sanitize_checkbox';
					break;
				case 'textarea':
					$sanitize = 'sanitize_textarea_field';
					break;
				default:
					$sanitize = 'sanitize_text_field';
			}

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => isset( $d[ $key ] ) ? $d[ $key ] : '',
					'sanitize_callback' => $sanitize,
					'transport'         => 'refresh',
				)
			);

			if ( 'color' === $type ) {
				$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting_id, array( 'label' => $label, 'section' => $section_id ) ) );
			} elseif ( 'image' === $type ) {
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $setting_id, array( 'label' => $label, 'section' => $section_id ) ) );
			} else {
				$wp_customize->add_control(
					$setting_id,
					array(
						'label'   => $label,
						'section' => $section_id,
						'type'    => $type,
					)
				);
			}
		}
	}
}
add_action( 'customize_register', 'tt_customize_register' );

/**
 * Sanitiza un checkbox.
 *
 * @param mixed $value Valor.
 */
function tt_sanitize_checkbox( $value ) {
	return (bool) $value;
}
