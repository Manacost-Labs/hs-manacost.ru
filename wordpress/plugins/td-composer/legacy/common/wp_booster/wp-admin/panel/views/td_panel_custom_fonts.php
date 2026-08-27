<!-- CUSTOM FONTS SECTION -->
<div class="td-section-separator">Custom Fonts</div>

<!-- Custom fonts files -->
<?php echo td_panel_generator::box_start('Documentation on how to use custom fonts', true); ?>

<!-- info text -->
<div class="td-box-row">
	<div class="td-box-description td-box-full">
		<p><?php echo td_util::get_wl_val('tds_wl_theme_name', TD_THEME_NAME) ?> supports custom fonts files, Typekit Fonts and Google Fonts. Please refresh the main panel to see the fonts after you add them here!</p>
        <?php if ('enabled' !== td_util::get_option('tds_white_label')) { ?>
        <p><a href="http://forum.tagdiv.com/using-custom-fonts/" target="_blank">Read more</a> about the font system</p>
        <?php } ?>
	</div>
</div>

<?php echo td_panel_generator::box_end();?>

<!-- Custom fonts files -->
<?php echo td_panel_generator::box_start('Custom font files', false); ?>


<!-- info text -->
<div class="td-box-row">
	<div class="td-box-description td-box-full">
		<p>To use custom font files:</p>

		<ul>
			<li>Add the link to the font file in .woff format, and the font-face name in the Custom Font Files section and click save settings.</li>
			<li>You can convert your font files from any format into .woff format using <a href="http://www.fontsquirrel.com/tools/webfont-generator">fontsquirrel</a> (free tool)</li>
			<li>Once a font file url and a font family name are added, the font family will appear in the dropdown and it can be selected</li>
		</ul>
	</div>
</div>

<!-- Custom Font file 1 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FILE 1</span>
		<p>Add the link to the file ( .woff format )</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::upload_font_file(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_file_1'
		));
		?>
	</div>
</div>


<!-- Custom Font name 1 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 1</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_family_1'
		));
		?>
	</div>
</div>


<div class="td-box-row">
	<div class="td-box-description"></div>
	<div class="td-box-control-full"></div>
</div>



<!-- Custom Font file 2 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FILE 2</span>
		<p>Add the link to the file ( .woff format )</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::upload_font_file(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_file_2'
		));
		?>
	</div>
</div>


<!-- Custom Font name 2 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 2</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_family_2'
		));
		?>
	</div>
</div>


<div class="td-box-row">
	<div class="td-box-description"></div>
	<div class="td-box-control-full"></div>
</div>



<!-- Custom Font file 3 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FILE 3</span>
		<p>Add the link to the file ( .woff format )</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::upload_font_file(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_file_3'
		));
		?>
	</div>
</div>


<!-- Custom Font name 3 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 3</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_family_3'
		));
		?>
	</div>
</div>


<div class="td-box-row">
	<div class="td-box-description"></div>
	<div class="td-box-control-full"></div>
</div>


<!-- Custom Font name 4 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FILE 4</span>
		<p>Add the link to the file ( .woff format )</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::upload_font_file(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_file_4'
		));
		?>
	</div>
</div>


<!-- Custom Font name 4 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 4</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_family_4'
		));
		?>
	</div>
</div>


<div class="td-box-row">
	<div class="td-box-description"></div>
	<div class="td-box-control-full"></div>
</div>


<!-- Custom Font name 5 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FILE 5</span>
		<p>Add the link to the file ( .woff format )</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::upload_font_file(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_file_5'
		));
		?>
	</div>
</div>


<!-- Custom Font name 5 -->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 5</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'font_family_5'
		));
		?>
	</div>
</div>

<?php echo td_panel_generator::box_end();?>




<!-- typekit.com fonts -->
<?php echo td_panel_generator::box_start('Adobe/Typekit Fonts', false); ?>

<!-- javascript from typekit.com-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">Embed Code</span>
		<p>Copy the embed code from fonts.adobe.com and paste it here</p>
	</div>
	<div class="td-box-control-full td-panel-input-wide">
		<?php
		echo td_panel_generator::textarea(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'typekit_js',
		));
		?>
	</div>
</div>


<!-- typekit.com Custom Font font family 1-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 1</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'type_kit_font_family_1'
		));
		?>
	</div>
</div>


<!-- typekit.com Custom Font font family 2-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 2</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'type_kit_font_family_2'
		));
		?>
	</div>
</div>


<!-- typekit.com Custom Font font family 3-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">CUSTOM FONT FAMILY 3</span>
		<p>Type your desired name for this font</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::input(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'type_kit_font_family_3'
		));
		?>
	</div>
</div>

<?php echo td_panel_generator::box_end();?>

<div class="td-clear"></div>

<?php echo td_panel_generator::box_start('Google Fonts Styles', false); ?>

<!-- google fonts settings-->
<div class="td-box-row">
    <div class="td-box-description">
        <span class="td-box-title">USE GOOGLE FONTS</span>
        <p>If you don't want to send data to google. Disable the use of google fonts.</p>
    </div>
    <div class="td-box-control-full">
        <?php
        echo td_panel_generator::checkbox(array(
            'ds' => 'td_option',
            'option_id' => 'g_use_google_fonts',
            'true_value' => '',
            'false_value' => 'disabled'
        ));
        ?>
    </div>
</div>

<div class="td-box-row">
    <div class="td-box-description">
        <span class="td-box-title">USE GOOGLE FONTS ON MOBILE</span>
        <p>Disable the use of google fonts on mobile</p>
    </div>
    <div class="td-box-control-full">
        <?php
        echo td_panel_generator::checkbox(array(
            'ds' => 'td_option',
            'option_id' => 'g_mob_use_google_fonts',
            'true_value' => '',
            'false_value' => 'disabled'
        ));
        ?>
    </div>
</div>

<!-- info text -->
<div class="td-box-row">
	<div class="td-box-description td-box-full">
		<p>More information:</p>
		<ul>
			<li>From here you can set global theme fonts weights & styles.</li>
			<li>These settings will load the font files with your chosen weights & styles, if available.</li>
		</ul>
		<p><strong>Notice: </strong> The <b>400 - NORMAL</b> font weight is missing from settings because it's hardcoded. If a font has only <b>400 normal</b> weight available, if 400 is missing, the font will not load for other weights.</p>
	</div>
</div>

<!-- google fonts style/type settings-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">Load default fonts</span>
        <p>From here you can disable to load the default fonts used by the theme<br>(<b>Open Sans and Roboto</b>)</p>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_default',
			'option_id' => 'default_fonts',
			'true_value' => '',
			'false_value' => 'disabled'
		));
		?>
	</div>
</div>

<?php if ( TD_THEME_NAME == 'Newspaper' ) { ?>
	<div class="td-box-row td-panel-box-google-fonts-replacements">
		<?php
		$typology_fonts_array = array(array('text' => 'Default font', 'val' => ''));
		$td_options = td_options::get_all();

		//read the user fonts array
		if(!empty($td_options['td_fonts_user_inserted'])) {

			$user_fonts = $td_options['td_fonts_user_inserted'];

			//custom font links & typekit
			foreach($user_fonts as $key_font => $value_font) {

				//look for the field number
				$revers_key_font = strrev($key_font);
				$explode_key_font = explode('_', $revers_key_font);
				$fld_number = intval($explode_key_font[0]);

				//add custom user fonts links    (numaratoare incepe de la 1)
				if(substr($key_font, 0, 10) == 'font_file_') {
					$font_family_field_nr = 'font_family_' . $fld_number;

					if(!empty($user_fonts['font_file_' . $fld_number]) and !empty($user_fonts[$font_family_field_nr])) {
						$typology_fonts_array[] = array('text' => $user_fonts[$font_family_field_nr], 'val' => 'file_' . $fld_number );
					}

					//add tipekit fonts                  (numaratoare incepe de la 1)
				} elseif(substr($key_font, 0, 21) == 'type_kit_font_family_') {
					$type_kit_font_family_field_nr = 'type_kit_font_family_' . $fld_number;

					if(!empty($user_fonts[$type_kit_font_family_field_nr])) {
						$typology_fonts_array[] = array('text' => $user_fonts[$type_kit_font_family_field_nr], 'val' => 'tk_' . $fld_number);
					}
				}

			}
		}

		//fonts stack
		foreach(td_fonts::$font_stack_list as $key_fs_id => $key_fs_value) {
			$typology_fonts_array[] = array('text' => $key_fs_value, 'val' => $key_fs_id);
		}

		//google fonts
		foreach(td_fonts::$font_names_google_list as $key_id => $key_value) {
			$typology_fonts_array[] = array('text' => $key_value, 'val' => 'g_' . $key_id);
		}
		?>

		<div class="td-box-description">
			<span class="td-box-title">Replace default fonts</span>
			<p>From here you can replace the default Google fonts.</p>
		</div>
		<div class="td-box-control-full">
			<div class="td-panel-font-family">
				<div class="td-panel-font-description">Open Sans replacement</div>

				<?php
				echo td_panel_generator::dropdown(array(
					'ds' => 'td_fonts',
					'item_id' => 'custom_def_googl_f_1',
					'option_id' => 'font_family',
					'values' => $typology_fonts_array
				));
				?>
			</div>
			<div class="td-panel-font-family">
				<div class="td-panel-font-description">Roboto replacement</div>

				<?php
				echo td_panel_generator::dropdown(array(
					'ds' => 'td_fonts',
					'item_id' => 'custom_def_googl_f_2',
					'option_id' => 'font_family',
					'values' => $typology_fonts_array
				));
				?>
			</div>
		</div>
	</div>
<?php } ?>

<!-- google fonts style/type settings-->
<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">100 - Thin (Hairline)</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_100_thin',
			'true_value' => '100',
			'false_value' => ''
		));
		?>
	</div>

	<div class="td-box-description">
		<span class="td-box-title">100 - Thin Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_100_thin_italic',
			'true_value' => '100italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">200 - Extra Light (Ultra Light)</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_200_extra_light',
			'true_value' => '200',
			'false_value' => ''
		));
		?>
	</div>

	<div class="td-box-description">
		<span class="td-box-title">200 - Extra Light (Ultra Light) Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_200_extra_light_italic',
			'true_value' => '200italic',
			'false_value' => ''
		));
		?>
	</div>
</div>


<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">300 - Light</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_300_light',
			'true_value' => '300',
			'false_value' => ''
		));
		?>
	</div>

	<div class="td-box-description">
		<span class="td-box-title">300 - Light Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_300_light_italic',
			'true_value' => '300italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">

	<div class="td-box-description">
		<span class="td-box-title">400 - Normal</span>
	</div>
	<div class="td-box-control-full td-always-on">
		<span>Active</span>
	</div>

	<div class="td-box-description">
		<span class="td-box-title">400 - Normal Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_400_normal_italic',
			'true_value' => '400italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">500 - Medium</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_500_medium',
			'true_value' => '500',
			'false_value' => ''
		));
		?>
	</div>
	<div class="td-box-description">
		<span class="td-box-title">500 - Medium Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_500_medium_italic',
			'true_value' => '500italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">600 - Semi Bold (Demi Bold)</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_600_semi_bold',
			'true_value' => '600',
			'false_value' => ''
		));
		?>
	</div>
	<div class="td-box-description">
		<span class="td-box-title">600 - Semi Bold (Demi Bold) Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_600_semi_bold_italic',
			'true_value' => '600italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">700 - Bold</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_700_bold',
			'true_value' => '700',
			'false_value' => ''
		));
		?>
	</div>
	<div class="td-box-description">
		<span class="td-box-title">700 - Bold Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_700_bold_italic',
			'true_value' => '700italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">800 - Extra Bold (Ultra Bold)</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_800_extra_bold',
			'true_value' => '800',
			'false_value' => ''
		));
		?>
	</div>
	<div class="td-box-description">
		<span class="td-box-title">800 - Extra Bold (Ultra Bold) Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_800_extra_bold_italic',
			'true_value' => '800italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<div class="td-box-row">
	<div class="td-box-description">
		<span class="td-box-title">900 - Black (Heavy)</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_900_black',
			'true_value' => '900',
			'false_value' => ''
		));
		?>
	</div>
	<div class="td-box-description">
		<span class="td-box-title">900 - Black (Heavy) Italic</span>
	</div>
	<div class="td-box-control-full">
		<?php
		echo td_panel_generator::checkbox(array(
			'ds' => 'td_fonts_user_insert',
			'option_id' => 'g_900_black_italic',
			'true_value' => '900italic',
			'false_value' => ''
		));
		?>
	</div>
</div>

<?php echo td_panel_generator::box_end();?>

<div class="td-clear"></div>
