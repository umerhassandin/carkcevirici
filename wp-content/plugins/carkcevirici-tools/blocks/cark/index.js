/**
 * Editor-only script for the cark/wheel block. No build step: it uses the
 * wp.* globals WordPress already ships, and previews the real front-end
 * markup via ServerSideRender since the block is rendered dynamically
 * (render_callback in PHP, save() returns null).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;
	var __ = wp.i18n.__;

	registerBlockType( 'cark/wheel', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Çark Ayarları', 'carkcevirici-tools' ) },
						el( TextControl, {
							label: __( 'Hazır liste (slug)', 'carkcevirici-tools' ),
							value: attributes.preset,
							help: __( 'Örn: isim-carki, karar, evet-hayir. Boş bırakırsan aşağıdaki seçenekler kullanılır.', 'carkcevirici-tools' ),
							onChange: function ( value ) {
								setAttributes( { preset: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Başlık', 'carkcevirici-tools' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( TextareaControl, {
							label: __( 'Seçenekler (| ile ayır)', 'carkcevirici-tools' ),
							value: attributes.entries,
							help: __( 'Örn: Evet|Hayır|Belki', 'carkcevirici-tools' ),
							onChange: function ( value ) {
								setAttributes( { entries: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Renk teması', 'carkcevirici-tools' ),
							value: attributes.theme,
							options: [
								{ label: __( 'Klasik', 'carkcevirici-tools' ), value: 'klasik' },
								{ label: __( 'Okyanus', 'carkcevirici-tools' ), value: 'okyanus' },
								{ label: __( 'Orman', 'carkcevirici-tools' ), value: 'orman' },
								{ label: __( 'Gün Batımı', 'carkcevirici-tools' ), value: 'gunbatimi' },
								{ label: __( 'Parti', 'carkcevirici-tools' ), value: 'parti' },
								{ label: __( 'Pastel', 'carkcevirici-tools' ), value: 'pastel' },
							],
							onChange: function ( value ) {
								setAttributes( { theme: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Kazananı otomatik kaldır', 'carkcevirici-tools' ),
							checked: attributes.removeWinner,
							onChange: function ( value ) {
								setAttributes( { removeWinner: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Her çevirişte kazanan sayısı', 'carkcevirici-tools' ),
							value: attributes.winners,
							min: 1,
							max: 20,
							onChange: function ( value ) {
								setAttributes( { winners: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Dönüş süresi (saniye)', 'carkcevirici-tools' ),
							value: attributes.duration,
							min: 3,
							max: 10,
							step: 0.5,
							onChange: function ( value ) {
								setAttributes( { duration: value } );
							},
						} )
					)
				),
				ServerSideRender
					? el( ServerSideRender, {
						block: 'cark/wheel',
						attributes: attributes,
					} )
					: el( 'p', null, __( 'Önizleme için sayfayı kaydet.', 'carkcevirici-tools' ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
