<?php
/**
 * Control de stock del taller (requiere WooCommerce, gratuito):
 * en cada cita se registran los repuestos usados (aceite, filtros, pastillas…).
 * Cuando la cita pasa a "Atendida" se descuentan del stock de WooCommerce; si vuelve a otro
 * estado, se elimina o se cambian los repuestos, el stock se corrige solo.
 * Así el inventario refleja tanto las ventas online como lo usado en el taller.
 *
 * @package TallerAgenda
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stock.
 */
class TTA_Stock {

	/**
	 * Hooks (solo si WooCommerce está activo).
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'add_meta_boxes', array( __CLASS__, 'box' ) );
		add_action( 'save_post_' . TTA_Booking::CPT, array( __CLASS__, 'save' ), 20 );
		add_action( 'wp_trash_post', array( __CLASS__, 'on_trash' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'on_untrash' ) );
	}

	/**
	 * Caja "Repuestos utilizados".
	 */
	public static function box() {
		add_meta_box( 'tta_stock', __( 'Repuestos utilizados (descuenta stock)', 'taller-agenda' ), array( __CLASS__, 'box_html' ), TTA_Booking::CPT, 'normal', 'default' );
	}

	/**
	 * Productos disponibles para elegir.
	 *
	 * @return WC_Product[]
	 */
	private static function products() {
		return wc_get_products(
			array(
				'status'  => 'publish',
				'type'    => array( 'simple' ),
				'limit'   => 500,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
	}

	/**
	 * HTML de la caja.
	 *
	 * @param WP_Post $post Cita.
	 */
	public static function box_html( $post ) {
		$used     = array_filter( (array) get_post_meta( $post->ID, '_tta_repuestos', true ) );
		$applied  = array_filter( (array) get_post_meta( $post->ID, '_tta_stock_aplicado', true ) );
		$products = self::products();
		$options  = '<option value="">' . esc_html__( '— Producto —', 'taller-agenda' ) . '</option>';
		foreach ( $products as $p ) {
			$stock    = $p->managing_stock() ? sprintf( /* translators: %s: stock */ __( 'stock: %s', 'taller-agenda' ), wc_stock_amount( $p->get_stock_quantity() ) ) : __( 'sin control de stock', 'taller-agenda' );
			$options .= '<option value="' . esc_attr( $p->get_id() ) . '">' . esc_html( $p->get_name() . ' (' . $stock . ')' ) . '</option>';
		}
		$row = function ( $pid = '', $qty = 1 ) use ( $options ) {
			$opts = $pid ? str_replace( 'value="' . esc_attr( $pid ) . '"', 'value="' . esc_attr( $pid ) . '" selected', $options ) : $options;
			return '<tr><td><select name="tta_rep[id][]" style="width:100%">' . $opts . '</select></td>'
				. '<td><input type="number" name="tta_rep[qty][]" min="1" step="1" value="' . esc_attr( $qty ) . '" style="width:80px"></td>'
				. '<td><button type="button" class="button-link-delete tta-rep-del" aria-label="' . esc_attr__( 'Quitar', 'taller-agenda' ) . '">✕</button></td></tr>';
		};
		?>
		<?php if ( ! $products ) : ?>
			<p><?php esc_html_e( 'Aún no hay productos en WooCommerce. Créalos en Productos → Añadir nuevo y activa "Gestionar inventario".', 'taller-agenda' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'El stock se descuenta cuando la cita se marca como "Atendida". Si cambias el estado o los repuestos, el stock se ajusta solo.', 'taller-agenda' ); ?></p>
			<table class="widefat striped" id="tta_rep_table">
				<thead><tr><th><?php esc_html_e( 'Producto', 'taller-agenda' ); ?></th><th><?php esc_html_e( 'Cantidad', 'taller-agenda' ); ?></th><th></th></tr></thead>
				<tbody>
					<?php
					foreach ( $used as $pid => $qty ) {
						echo $row( $pid, $qty ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado al construir.
					}
					if ( ! $used ) {
						echo $row(); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="tta_rep_add"><?php esc_html_e( '+ Agregar repuesto', 'taller-agenda' ); ?></button></p>
			<?php if ( array_filter( $applied ) ) : ?>
				<p style="color:#027a48;font-weight:600">✔ <?php esc_html_e( 'Stock ya descontado para esta cita.', 'taller-agenda' ); ?></p>
			<?php endif; ?>
			<template id="tta_rep_tpl"><?php echo $row(); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
			<script>
			(function () {
				var tb = document.querySelector('#tta_rep_table tbody');
				document.getElementById('tta_rep_add').addEventListener('click', function () {
					tb.insertAdjacentHTML('beforeend', document.getElementById('tta_rep_tpl').innerHTML);
				});
				tb.addEventListener('click', function (e) {
					if (e.target.classList.contains('tta-rep-del')) { e.target.closest('tr').remove(); }
				});
			})();
			</script>
		<?php endif; ?>
		<?php
	}

	/**
	 * Guarda los repuestos y ajusta el stock según el estado.
	 *
	 * @param int $post_id Cita.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST['tta_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tta_admin_nonce'] ) ), 'tta_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in   = isset( $_POST['tta_rep'] ) ? (array) wp_unslash( $_POST['tta_rep'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ids  = isset( $in['id'] ) ? array_map( 'absint', (array) $in['id'] ) : array();
		$qtys = isset( $in['qty'] ) ? array_map( 'absint', (array) $in['qty'] ) : array();
		$used = array();
		foreach ( $ids as $i => $pid ) {
			$qty = isset( $qtys[ $i ] ) ? $qtys[ $i ] : 0;
			if ( $pid && $qty > 0 && wc_get_product( $pid ) ) {
				$used[ $pid ] = ( isset( $used[ $pid ] ) ? $used[ $pid ] : 0 ) + $qty;
			}
		}
		update_post_meta( $post_id, '_tta_repuestos', $used );
		self::apply( $post_id );
	}

	/**
	 * Lleva el stock al estado correcto: descontado solo si la cita está "Atendida".
	 *
	 * @param int  $post_id Cita.
	 * @param bool $force_zero Forzar devolución total (al enviar a la papelera).
	 */
	public static function apply( $post_id, $force_zero = false ) {
		$used    = (array) get_post_meta( $post_id, '_tta_repuestos', true );
		$applied = array_filter( (array) get_post_meta( $post_id, '_tta_stock_aplicado', true ) );
		$target  = ( ! $force_zero && 'atendida' === get_post_meta( $post_id, '_tta_estado', true ) ) ? array_filter( $used ) : array();

		$final = array();
		foreach ( array_unique( array_merge( array_keys( $target ), array_keys( $applied ) ) ) as $pid ) {
			$want    = isset( $target[ $pid ] ) ? (int) $target[ $pid ] : 0;
			$have    = isset( $applied[ $pid ] ) ? (int) $applied[ $pid ] : 0;
			$product = wc_get_product( $pid );
			if ( ! $product || ! $product->managing_stock() ) {
				continue;
			}
			$delta = $want - $have;
			if ( $delta > 0 ) {
				wc_update_product_stock( $product, $delta, 'decrease' );
			} elseif ( $delta < 0 ) {
				wc_update_product_stock( $product, -$delta, 'increase' );
			}
			if ( $want ) {
				$final[ $pid ] = $want;
			}
		}
		update_post_meta( $post_id, '_tta_stock_aplicado', $final );
	}

	/**
	 * Al enviar una cita a la papelera se devuelve el stock.
	 *
	 * @param int $post_id Cita.
	 */
	public static function on_trash( $post_id ) {
		if ( TTA_Booking::CPT === get_post_type( $post_id ) ) {
			self::apply( $post_id, true );
		}
	}

	/**
	 * Al restaurarla, se vuelve a descontar si corresponde.
	 *
	 * @param int $post_id Cita.
	 */
	public static function on_untrash( $post_id ) {
		if ( TTA_Booking::CPT === get_post_type( $post_id ) ) {
			self::apply( $post_id );
		}
	}
}
