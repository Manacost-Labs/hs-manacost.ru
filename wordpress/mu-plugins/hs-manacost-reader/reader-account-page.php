<?php
/**
 * Reader workspace; unique basename avoids Composer's legacy page replacement.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="td-main-content-wrap mc-reader-page">
	<div class="td-container">
		<div class="td-page-content">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</div>
</main>
<?php
get_footer();
