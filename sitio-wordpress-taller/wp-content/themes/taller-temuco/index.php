<?php
/**
 * Plantilla de respaldo (blog / archivos), por si el cliente publica noticias o consejos.
 *
 * @package TallerTemuco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-hero">
	<div class="container">
		<h1><?php echo is_singular() ? esc_html( get_the_title() ) : esc_html( wp_strip_all_tags( get_the_archive_title() ? get_the_archive_title() : __( 'Consejos y noticias', 'taller-temuco' ) ) ); ?></h1>
	</div>
</header>

<div class="section">
	<div class="container narrow">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				if ( is_singular() ) :
					?>
					<article <?php post_class( 'entry-content' ); ?>><?php the_content(); ?></article>
				<?php else : ?>
					<article <?php post_class( 'post-summary' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="muted"><?php echo esc_html( get_the_date() ); ?></p>
						<?php the_excerpt(); ?>
					</article>
					<?php
				endif;
			endwhile;
			the_posts_pagination();
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'No hay contenido para mostrar.', 'taller-temuco' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
