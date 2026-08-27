<div id="td-header-menu">
    <div id="td-top-mobile-toggle"><span><i class="td-icon-font td-icon-mobile"></i></span></div>
    <div class="td-main-menu-logo">
        <?php
            locate_template('parts/logo.php', true, false);
        ?>
    </div>
    <!-- Search -->
    <div class="td-search-icon">
            <span id="td-header-search-button"><i class="td-icon-search"></i></span>
    </div>
</div>

<?php
if( TD_THEME_NAME == 'Newspaper' ){
    td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAjaxSearch.js' . TDC_SCRIPTS_VER, 'tdAjaxSearch-js', '', 'footer' );
}
?>