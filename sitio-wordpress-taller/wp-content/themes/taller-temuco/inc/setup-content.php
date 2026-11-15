<?php
/**
 * Contenido inicial: al activar el tema se crean (una sola vez) el Home, la página Servicios,
 * 8 páginas de servicio con SEO local para Temuco, Agendar hora y Contacto, más el menú principal.
 * Todo queda como páginas normales de WordPress: el cliente las edita en el editor de bloques.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contenido de cada servicio.
 * slug => [título, título SEO, meta descripción, intro, incluye[], señales[], faq[[p,r]]]
 */
function tt_seed_services() {
	return array(
		'mantencion-automotriz-temuco'  => array(
			'Mantención automotriz',
			'Mantención Automotriz en Temuco | Cambio de Aceite y Filtros',
			'Mantención de autos y camionetas en Temuco según pauta del fabricante: aceite, filtros, revisión de 30 puntos. Agenda online o cotiza por WhatsApp.',
			'Realizamos mantenciones preventivas por kilometraje según la pauta de cada marca, cuidando la garantía de tu vehículo. Ideal para autos, SUV y camionetas que circulan por Temuco y la Región de La Araucanía, donde el frío y la humedad exigen más al motor.',
			array( 'Cambio de aceite de motor y filtro', 'Cambio de filtros de aire, polen y combustible', 'Revisión de 30 puntos con informe', 'Revisión de niveles, correas y mangueras', 'Reinicio del indicador de mantención' ),
			array( 'Se encendió la luz de mantención', 'Superaste los 10.000 km desde el último cambio de aceite', 'Vas a salir de viaje fuera de Temuco' ),
			array(
				array( '¿Cada cuánto debo hacer la mantención?', 'En general cada 10.000 km o una vez al año, lo que ocurra primero. Revisamos la pauta exacta de tu marca y modelo.' ),
				array( '¿Pierdo la garantía si no voy a la automotora?', 'No. La ley permite realizar las mantenciones en talleres independientes si se siguen la pauta y los repuestos especificados. Te entregamos el respaldo por escrito.' ),
			),
		),
		'frenos-temuco'                 => array(
			'Frenos',
			'Cambio de Pastillas y Discos de Freno en Temuco',
			'Reparación de frenos en Temuco: pastillas, discos, líquido y ABS. Revisión gratuita del sistema de frenado y entrega en el día. Agenda tu hora.',
			'Los frenos son el sistema de seguridad más importante de tu vehículo. Con la lluvia de Temuco y los caminos rurales de la zona, pastillas y discos se desgastan más rápido. Revisamos todo el sistema y te mostramos su estado antes de cambiar cualquier pieza.',
			array( 'Cambio de pastillas delanteras y traseras', 'Rectificado o cambio de discos', 'Cambio de líquido de frenos', 'Revisión de calipers, tambores y freno de mano', 'Diagnóstico de luz ABS' ),
			array( 'Chirrido o ruido metálico al frenar', 'El pedal se siente esponjoso o baja demasiado', 'El volante vibra al frenar', 'Se encendió la luz de frenos o ABS' ),
			array(
				array( '¿Cuánto demora un cambio de pastillas?', 'Normalmente entre 1 y 2 horas. Puedes esperar en el taller o retirar el vehículo el mismo día.' ),
				array( '¿La revisión de frenos tiene costo?', 'La inspección visual del sistema de frenos no tiene costo al agendar cualquier servicio.' ),
			),
		),
		'scanner-automotriz-temuco'     => array(
			'Scanner y diagnóstico electrónico',
			'Scanner Automotriz en Temuco | Diagnóstico Check Engine',
			'Diagnóstico con scanner automotriz en Temuco: luz check engine, fallas eléctricas, sensores y ABS. Informe claro antes de reparar. Agenda hoy.',
			'¿Se encendió la luz de Check Engine? Con scanner multimarca leemos los códigos de falla, analizamos datos en vivo de sensores y te entregamos un diagnóstico claro, para reparar solo lo necesario.',
			array( 'Lectura y borrado de códigos de falla (OBD2)', 'Análisis de datos en vivo de sensores', 'Diagnóstico de sistemas ABS, airbag y transmisión', 'Prueba de batería y sistema de carga', 'Informe escrito con causa probable y presupuesto' ),
			array( 'Luz Check Engine encendida', 'El motor tironea o pierde potencia', 'Mayor consumo de combustible', 'El auto no parte o le cuesta arrancar en las mañanas frías' ),
			array(
				array( '¿Basta con borrar el código?', 'No. Borrar el código apaga la luz, pero la falla sigue. Identificamos la causa real para evitar daños mayores.' ),
				array( '¿Trabajan con todas las marcas?', 'Sí, nuestro scanner es multimarca: marcas asiáticas, europeas y americanas de autos y camionetas.' ),
			),
		),
		'suspension-y-direccion-temuco' => array(
			'Suspensión y dirección',
			'Reparación de Suspensión y Dirección en Temuco',
			'Taller de suspensión en Temuco: amortiguadores, bandejas, rótulas, terminales y dirección. Diagnóstico de ruidos y vibraciones. Cotiza por WhatsApp.',
			'Los caminos con baches y ripio de la Araucanía castigan la suspensión. Diagnosticamos ruidos, golpes y vibraciones, y reparamos amortiguadores, bandejas, rótulas, bujes y terminales de dirección para que recuperes estabilidad y confort.',
			array( 'Cambio de amortiguadores y espirales', 'Cambio de bandejas, rótulas y bujes', 'Terminales y axiales de dirección', 'Revisión de dirección hidráulica y eléctrica', 'Prueba de ruta con el cliente' ),
			array( 'Golpes o ruidos al pasar baches', 'El vehículo se va hacia un lado', 'Desgaste disparejo de neumáticos', 'Volante con juego o duro' ),
			array(
				array( '¿Cada cuánto se cambian los amortiguadores?', 'Entre 60.000 y 100.000 km según uso. En caminos rurales puede ser antes; los revisamos en cada mantención.' ),
				array( '¿Debo alinear después de reparar la suspensión?', 'Sí, recomendamos alinear siempre tras cambiar componentes de suspensión o dirección.' ),
			),
		),
		'alineacion-y-balanceo-temuco'  => array(
			'Alineación y balanceo',
			'Alineación y Balanceo en Temuco | Cuida tus Neumáticos',
			'Alineación computarizada y balanceo de ruedas en Temuco para autos, SUV y camionetas. Evita desgaste de neumáticos y vibraciones. Agenda online.',
			'Una buena alineación y balanceo prolongan la vida de tus neumáticos y mejoran la seguridad. Usamos equipos computarizados y entregamos el informe de ángulos antes y después del ajuste.',
			array( 'Alineación computarizada de 2 o 4 ruedas', 'Balanceo de ruedas', 'Rotación de neumáticos', 'Informe de ángulos antes y después', 'Revisión de presión y estado de neumáticos' ),
			array( 'El volante no queda derecho', 'Vibración a cierta velocidad', 'Cambiaste neumáticos o caíste en un bache fuerte' ),
			array(
				array( '¿Cada cuánto debo alinear?', 'Cada 10.000 km, al cambiar neumáticos o después de un golpe fuerte en la rueda.' ),
				array( '¿Cuánto demora?', 'Alrededor de 45 minutos para alineación y balanceo completo.' ),
			),
		),
		'embrague-temuco'               => array(
			'Embrague y transmisión',
			'Cambio de Embrague en Temuco | Kit de Embrague y Caja',
			'Cambio de kit de embrague en Temuco, volante bimasa y revisión de caja de cambios. Presupuesto por escrito y garantía. Cotiza por WhatsApp.',
			'Reparamos y reemplazamos kits de embrague (disco, prensa y collarín), volantes bimasa y revisamos cajas de cambio manuales y automáticas, con presupuesto cerrado antes de comenzar.',
			array( 'Cambio de kit de embrague completo', 'Rectificado o cambio de volante motor / bimasa', 'Bomba y cilindro de embrague', 'Cambio de aceite de caja y diferencial', 'Diagnóstico de caja automática' ),
			array( 'El embrague patina o el motor se acelera sin avanzar', 'Cuesta pasar los cambios', 'Pedal duro o con ruido', 'Olor a quemado al subir pendientes' ),
			array(
				array( '¿Cuánto dura un embrague?', 'Entre 80.000 y 150.000 km dependiendo de la conducción y la carga del vehículo.' ),
				array( '¿Cuánto tiempo toma el cambio?', 'Generalmente uno a dos días hábiles, según modelo y disponibilidad de repuestos.' ),
			),
		),
		'aire-acondicionado-automotriz-temuco' => array(
			'Aire acondicionado y climatización',
			'Carga de Aire Acondicionado Automotriz en Temuco',
			'Carga de gas y reparación de aire acondicionado automotriz en Temuco. Detección de fugas, compresor y calefacción. Agenda tu hora online.',
			'Mantén un buen clima dentro del vehículo todo el año: aire acondicionado en verano y calefacción y desempañado eficiente en el invierno de Temuco. Detectamos fugas, cargamos gas y reparamos compresores.',
			array( 'Carga de gas refrigerante con vacío', 'Detección de fugas con tinte UV', 'Reparación de compresor y electroventilador', 'Cambio de filtro de habitáculo', 'Revisión de calefacción y desempañador' ),
			array( 'El aire no enfría o sale tibio', 'Mal olor por las rejillas', 'Los vidrios se empañan y no se desempañan' ),
			array(
				array( '¿Cada cuánto se carga el aire?', 'Si el sistema no tiene fugas, cada 2 a 3 años. Si pierde gas antes, hay una fuga que se debe reparar.' ),
				array( '¿Sirve el filtro de habitáculo para la calefacción?', 'Sí. Un filtro sucio reduce el flujo de aire y genera malos olores; recomendamos cambiarlo una vez al año.' ),
			),
		),
		'pre-revision-tecnica-temuco'   => array(
			'Pre revisión técnica',
			'Pre Revisión Técnica en Temuco | Aprueba a la Primera',
			'Prepara tu vehículo para la revisión técnica en Temuco: luces, frenos, emisiones, suspensión y neumáticos. Evita rechazos y vuelve a la planta una sola vez.',
			'Antes de ir a la planta de revisión técnica, revisamos los mismos puntos que se inspeccionan oficialmente y corregimos lo necesario, para que tu vehículo apruebe a la primera y no pierdas tiempo.',
			array( 'Revisión de luces, focos y alineación de luces', 'Prueba de frenos y freno de mano', 'Revisión de emisiones de gases', 'Holguras de suspensión y dirección', 'Neumáticos, parabrisas y elementos de seguridad' ),
			array( 'Se acerca la fecha de tu revisión técnica', 'Fuiste rechazado y debes volver a la planta', 'Compraste un vehículo usado' ),
			array(
				array( '¿Ustedes hacen la revisión técnica oficial?', 'No, la revisión técnica la realizan las plantas autorizadas. Nosotros dejamos el vehículo listo para aprobar.' ),
				array( '¿Qué pasa si me rechazaron?', 'Trae el certificado de rechazo, corregimos las observaciones y te indicamos cuándo volver a la planta.' ),
			),
		),
	);
}

/**
 * Construye el contenido en bloques (editable en Gutenberg) de una página de servicio.
 *
 * @param array $s Datos del servicio.
 */
function tt_seed_service_content( $s ) {
	list( $title, , , $intro, $includes, $signs, $faq ) = $s;

	$li = function ( $items ) {
		return implode(
			'',
			array_map(
				function ( $i ) {
					return '<!-- wp:list-item --><li>' . esc_html( $i ) . '</li><!-- /wp:list-item -->';
				},
				$items
			)
		);
	};

	$html  = '<!-- wp:paragraph {"className":"lead"} --><p class="lead">' . esc_html( $intro ) . '</p><!-- /wp:paragraph -->';
	$html .= '<!-- wp:heading --><h2 class="wp-block-heading">¿Qué incluye nuestro servicio de ' . esc_html( mb_strtolower( $title ) ) . ' en Temuco?</h2><!-- /wp:heading -->';
	$html .= '<!-- wp:list {"className":"checklist"} --><ul class="checklist">' . $li( $includes ) . '</ul><!-- /wp:list -->';
	$html .= '<!-- wp:heading --><h2 class="wp-block-heading">¿Cuándo debes traer tu vehículo?</h2><!-- /wp:heading -->';
	$html .= '<!-- wp:list --><ul>' . $li( $signs ) . '</ul><!-- /wp:list -->';
	$html .= '<!-- wp:heading --><h2 class="wp-block-heading">Atendemos Temuco y alrededores</h2><!-- /wp:heading -->';
	$html .= '<!-- wp:paragraph --><p>Recibimos vehículos de Temuco, Padre Las Casas, Labranza, Lautaro, Vilcún, Freire y comunas cercanas. Agenda tu hora online o escríbenos por WhatsApp para cotizar.</p><!-- /wp:paragraph -->';
	$html .= '<!-- wp:heading --><h2 class="wp-block-heading">Preguntas frecuentes</h2><!-- /wp:heading -->';
	foreach ( $faq as $q ) {
		$html .= '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html( $q[0] ) . '</summary><!-- wp:paragraph {"placeholder":"Respuesta"} --><p>' . esc_html( $q[1] ) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
	}
	return $html;
}

/**
 * Crea o reutiliza una página por slug.
 *
 * @param string $slug   Slug.
 * @param array  $args   Datos de wp_insert_post.
 * @param array  $meta   Metadatos.
 * @return int ID.
 */
function tt_seed_page( $slug, $args, $meta = array() ) {
	$parent   = isset( $args['post_parent'] ) ? (int) $args['post_parent'] : 0;
	$existing = get_page_by_path( $parent ? get_page_uri( $parent ) . '/' . $slug : $slug );
	if ( $existing ) {
		return $existing->ID;
	}
	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => $slug,
			),
			$args
		)
	);
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
}

/**
 * Ejecuta la carga inicial una sola vez.
 */
function tt_seed_content() {
	if ( get_option( 'tt_seeded' ) ) {
		return;
	}

	// Inicio.
	$home = tt_seed_page(
		'inicio',
		array(
			'post_title'   => 'Inicio',
			'post_content' => '<!-- wp:heading --><h2 class="wp-block-heading">Un taller de confianza en Temuco</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Somos un taller automotriz familiar con años de experiencia atendiendo a conductores de Temuco y la Araucanía. Trabajamos con diagnóstico transparente, presupuesto por escrito y garantía en cada trabajo. Edita este texto desde Páginas → Inicio.</p><!-- /wp:paragraph -->',
		),
		array(
			'_tt_seo_title' => 'Taller Mecánico en Temuco | Mantención, Frenos y Scanner',
			'_tt_seo_desc'  => 'Taller automotriz en Temuco con diagnóstico transparente y garantía. Mantención, frenos, scanner, suspensión y más. Agenda online o cotiza por WhatsApp.',
		)
	);

	// Servicios (página padre con listado).
	$services = tt_seed_page(
		'servicios',
		array(
			'post_title'   => 'Servicios',
			'post_content' => '<!-- wp:paragraph --><p>Conoce todos los servicios de nuestro taller automotriz en Temuco. Selecciona uno para ver detalles, preguntas frecuentes y agendar tu hora.</p><!-- /wp:paragraph -->',
		),
		array(
			'_tt_seo_title' => 'Servicios de Taller Mecánico en Temuco',
			'_tt_seo_desc'  => 'Mantención, frenos, scanner, suspensión, alineación, embrague, aire acondicionado y pre revisión técnica en Temuco. Revisa nuestros servicios y agenda.',
		)
	);
	update_option( 'tt_services_page_id', $services );

	$order = 1;
	foreach ( tt_seed_services() as $slug => $s ) {
		$id = tt_seed_page(
			$slug,
			array(
				'post_title'   => $s[0],
				'post_parent'  => $services,
				'menu_order'   => $order++,
				'post_excerpt' => $s[2],
				'post_content' => tt_seed_service_content( $s ),
			),
			array(
				'_tt_seo_title'     => $s[1],
				'_tt_seo_desc'      => $s[2],
				'_wp_page_template' => 'page-servicio.php',
			)
		);
	}

	// Agenda y contacto.
	$booking = tt_seed_page(
		'agendar-hora',
		array(
			'post_title'   => 'Agendar hora',
			'post_content' => '<!-- wp:paragraph --><p>Elige el servicio, el día y el horario. Te confirmaremos la hora por WhatsApp o teléfono.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[taller_agenda]<!-- /wp:shortcode -->',
		),
		array(
			'_tt_seo_title' => 'Agendar Hora en Taller Mecánico en Temuco',
			'_tt_seo_desc'  => 'Agenda online tu hora en nuestro taller automotriz en Temuco. Elige servicio, día y horario en menos de un minuto.',
		)
	);
	update_option( 'tt_booking_page_id', $booking );

	$contact = tt_seed_page(
		'contacto',
		array(
			'post_title'   => 'Contacto',
			'post_content' => '<!-- wp:paragraph --><p>Escríbenos por WhatsApp, llámanos o visítanos en el taller. Los datos de contacto y el mapa se actualizan desde Apariencia → Personalizar → Taller: datos del negocio.</p><!-- /wp:paragraph -->',
			'page_template' => 'page-contacto.php',
		),
		array(
			'_tt_seo_title'     => 'Contacto y Ubicación | Taller Mecánico en Temuco',
			'_tt_seo_desc'      => 'Dirección, horario, teléfono y WhatsApp de nuestro taller automotriz en Temuco. Cómo llegar y formas de contacto.',
			'_wp_page_template' => 'page-contacto.php',
		)
	);

	// Portada estática.
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// Enlaces permanentes amigables (clave para SEO).
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	// Idioma/zona horaria por defecto para Chile (solo si no se han configurado).
	if ( ! get_option( 'timezone_string' ) ) {
		update_option( 'timezone_string', 'America/Santiago' );
	}

	// Menú principal.
	$menu_name = 'Menú principal';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( ! is_wp_error( $menu_id ) ) {
			$add = function ( $page_id, $parent = 0 ) use ( $menu_id ) {
				return wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $page_id,
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-parent-id' => $parent,
					)
				);
			};
			$add( $home );
			$parent_item = $add( $services );
			foreach ( tt_services() as $p ) {
				$add( $p->ID, $parent_item );
			}
			$add( $contact );
			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	update_option( 'tt_seeded', 1 );
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'tt_seed_content' );
