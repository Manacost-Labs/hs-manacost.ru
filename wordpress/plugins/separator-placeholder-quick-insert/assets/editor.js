( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.element || ! wp.components ) {
		return;
	}

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var useState          = wp.element.useState;
	var useEffect         = wp.element.useEffect;
	var registerPlugin    = wp.plugins.registerPlugin;
	var PluginMoreMenuItem = wp.editPost && wp.editPost.PluginMoreMenuItem;
	var Modal             = wp.components.Modal;
	var TabPanel          = wp.components.TabPanel;
	var Spinner           = wp.components.Spinner;
	var Button            = wp.components.Button;
	var apiFetch          = wp.apiFetch;
	var dispatch          = wp.data.dispatch;
	var createBlock       = wp.blocks.createBlock;
	var __                = wp.i18n.__;

	if ( ! PluginMoreMenuItem ) {
		// Site editor / other contexts without edit-post — bail silently.
		return;
	}

	function insertImage( item ) {
		var block = createBlock( 'core/image', {
			id:  item.id,
			url: item.url,
			alt: item.alt || '',
			sizeSlug: 'full',
		} );
		dispatch( 'core/block-editor' ).insertBlocks( block );
	}

	function Tile( props ) {
		return el(
			'button',
			{
				type: 'button',
				className: 'spqi-tile',
				onClick: props.onClick,
				title: props.item.title || '',
				'aria-label': props.item.alt || props.item.title || __( 'Вставить изображение', 'spqi' ),
			},
			el( 'img', {
				src: props.item.thumb,
				alt: props.item.alt || '',
				loading: 'lazy',
			} )
		);
	}

	function Grid( props ) {
		if ( props.loading ) {
			return el( 'div', { className: 'spqi-state' }, el( Spinner ) );
		}
		if ( ! props.items || ! props.items.length ) {
			return el(
				'div',
				{ className: 'spqi-state spqi-empty' },
				el( 'p', null, __( 'Здесь пока пусто.', 'spqi' ) ),
				el(
					'p',
					null,
					__( 'Добавьте изображения в Инструменты → Разделители и заглушки.', 'spqi' )
				)
			);
		}
		return el(
			'div',
			{ className: 'spqi-grid' },
			props.items.map( function ( it ) {
				return el( Tile, {
					key: it.id,
					item: it,
					onClick: function () { props.onPick( it ); },
				} );
			} )
		);
	}

	function Picker() {
		var openState   = useState( false );
		var open        = openState[0];
		var setOpen     = openState[1];
		var dataState   = useState( null );
		var data        = dataState[0];
		var setData     = dataState[1];
		var loadingState = useState( false );
		var loading     = loadingState[0];
		var setLoading  = loadingState[1];

		useEffect(
			function () {
				if ( ! open || data || loading ) {
					return;
				}
				setLoading( true );
				apiFetch( { path: '/spqi/v1/items' } )
					.then( function ( res ) {
						setData( res || { separators: [], placeholders: [] } );
					} )
					.catch( function () {
						setData( { separators: [], placeholders: [], misc: [], preview: [], descriptions: {} } );
					} )
					.then( function () {
						setLoading( false );
					} );
			},
			[ open ]
		);

		function pick( item ) {
			insertImage( item );
			setOpen( false );
		}

		var tabs = [
			{ name: 'separators',   title: __( 'Разделители', 'spqi' ), className: 'spqi-tab' },
			{ name: 'placeholders', title: __( 'Заглушки',    'spqi' ), className: 'spqi-tab' },
			{ name: 'misc',         title: __( 'Разное',      'spqi' ), className: 'spqi-tab' },
			{ name: 'preview',      title: __( 'Превью',      'spqi' ), className: 'spqi-tab' },
		];

		var menuItem = el(
			PluginMoreMenuItem,
			{
				icon: 'images-alt2',
				onClick: function () { setOpen( true ); },
			},
			__( 'Разделители и заглушки', 'spqi' )
		);

		var modal = open ? el(
			Modal,
			{
				title: __( 'Вставить изображение', 'spqi' ),
				onRequestClose: function () { setOpen( false ); },
				className: 'spqi-modal',
				size: 'large',
			},
			el(
				TabPanel,
				{
					className: 'spqi-tabs',
					activeClass: 'is-active',
					tabs: tabs,
				},
				function ( tab ) {
					var items = data ? ( data[ tab.name ] || [] ) : [];
					var desc  = data && data.descriptions ? ( data.descriptions[ tab.name ] || '' ) : '';
					var descNode = desc ? el( 'p', { className: 'spqi-desc' }, desc ) : null;
					return el( Fragment, null,
						descNode,
						el( Grid, { items: items, loading: loading, onPick: pick } )
					);
				}
			)
		) : null;

		return el( Fragment, null, menuItem, modal );
	}

	registerPlugin( 'spqi-picker', { render: Picker } );
} )( window.wp );
