
<!-- tpl type -->
<div class="td-meta-box-row">

	<?php

	$post_id = get_the_id();
	$tdb_template_type = get_post_meta( $post_id, 'tdb_template_type', true );

    ?>

    <span class="td-page-o-custom-label">Template Type: </span>

	<div class="td-select-style-overwrite">
		<label for="tdb-mb-template-type">
			<select id="tdb-mb-template-type" name="tdb_template_type" class="td-panel-dropdown">
				<option value="">No template type</option>
				<?php

				$filters = array(
					'header',
					'footer',
					'single',
					'404',
					'attachment',
					'author',
					'category',
					'date',
					'search',
					'tag',
					'module',
                    'cpt',
                    'cpt_tax'
				);
				$filters = apply_filters( 'tdb_template_types', $filters );

				foreach ( $filters as $template_type ) {
					$selected = $tdb_template_type === $template_type ? ' selected="selected"' : '';
					?>
					<option value="<?php echo $template_type ?>"<?php echo $selected; ?>>
						<?php echo ucfirst($template_type) ?>
					</option>
					<?php

				}

				?>
			</select>
		</label>
	</div>

</div>

<!-- cpt -->
<div class="td-meta-box-row">

	<?php
	if ( $tdb_template_type === 'cpt' ) {
		$tdb_demo_cpt = get_post_meta( $post_id, 'tdb_demo_cpt', true );

		?>

        <span class="td-page-o-custom-label">
            Template demo cpt:

            <?php td_util::tooltip_html('<p>Use this option to set the custom post type to use for this template when generating a demo import.</p>', 'right' ); ?>

        </span>

        <div class="td-select-style-overwrite">
            <label for="tdb-mb-demo-import-cpt">
                <select id="tdb-mb-demo-import-cpt" name="tdb_demo_cpt" class="td-panel-dropdown">
                    <option value="">Not set</option>
					<?php

					$cpts = td_util::get_cpts();

					foreach ( $cpts as $cpt ) {

						// exclude default `post` post type
                        if ( $cpt->name === 'post' )
                            continue;

						$selected = $tdb_demo_cpt === $cpt->name ? ' selected="selected"' : '';
						?>
                        <option value="<?php echo $cpt->name ?>"<?php echo $selected; ?>>
							<?php echo $cpt->name ?>
                        </option>
						<?php

					}

					?>
                </select>
            </label>
        </div>

		<?php
	}
	?>

</div>