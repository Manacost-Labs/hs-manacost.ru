<!-- STYLE 2 -->
<div class="td-section-separator" style="margin-top:0;margin-bottom:25px">Style 2</div>

<!-- Fill color -->
<div class="td-box-row">
    <div class="td-box-description">
        <span class="td-box-title">FILL COLOR</span>
        <p>Select the color for the circle progress fill, arrow and tooltip.</p>
    </div>
    <div class="td-box-control-full">
        <?php
        echo td_panel_generator::color_picker(array(
            'ds' => 'td_option',
            'option_id' => 'tds_to_top_style2_fill_color',
            'default_color' => '#222222'
        ));
        ?>
    </div>
</div>

<!-- Fill color -->
<div class="td-box-row">
    <div class="td-box-description">
        <span class="td-box-title">EMPTY/HOVER CIRCLE COLOR</span>
        <p>Select the color for the empty/hover circle.</p>
    </div>
    <div class="td-box-control-full">
        <?php
        echo td_panel_generator::color_picker(array(
            'ds' => 'td_option',
            'option_id' => 'tds_to_top_style2_empty_color',
            'default_color' => '#e7e7e7'
        ));
        ?>
    </div>
</div>