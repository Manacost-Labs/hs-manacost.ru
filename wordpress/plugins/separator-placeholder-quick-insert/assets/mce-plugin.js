( function () {
	'use strict';
	if ( ! window.tinymce ) {
		return;
	}
	tinymce.PluginManager.add( 'spqi', function ( editor ) {
		editor.addButton( 'spqi_picker', {
			tooltip: ( window.SPQI_CLASSIC && window.SPQI_CLASSIC.i18n && window.SPQI_CLASSIC.i18n.buttonTitle ) || 'Separators & Placeholders',
			icon: 'spqi-icon',
			onclick: function () {
				if ( window.SPQI && typeof window.SPQI.openPicker === 'function' ) {
					window.SPQI.openPicker( editor );
				}
			},
		} );
	} );
} )();
