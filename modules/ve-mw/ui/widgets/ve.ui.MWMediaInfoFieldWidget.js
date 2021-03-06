/*!
 * VisualEditor user interface MWMediaDialog class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */
/* global moment */
/**
 * MWMediaInfoFieldWidget widget for displaying media information from the API.
 *
 * @class
 * @extends OO.ui.Widget
 * @mixins OO.ui.IconElement
 * @mixins OO.ui.TitledElement
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {string} [href] A url encapsulating the field text. If a label is attached it will include the label.
 * @cfg {string} [label] A ve.msg() label string for the field.
 * @cfg {boolean} [isDate] Field text is a date that will be converted to 'fromNow' string.
 * @cfg {string} [type] Field type, either 'description' or 'attribute'
 * @cfg {string} [descriptionHeight] Height limit for description fields; defaults to '4em'
 */
ve.ui.MWMediaInfoFieldWidget = function VeUiMWMediaInfoFieldWidget( content, config ) {
	var datetime;

	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.MWMediaInfoFieldWidget.super.call( this, config );

	// Mixin constructors
	OO.ui.IconElement.call( this, config );
	OO.ui.LabelElement.call( this, $.extend( { $label: this.$( '<div>' ) }, config ) );

	this.$text = this.$( '<div>' )
		.addClass( 've-ui-mwMediaInfoFieldWidget-text' );
	this.$overlay = null;
	this.type = config.type || 'attribute';

	// Initialization
	if ( config.isDate && ( datetime = moment( content ) ).isValid() ) {
		content = datetime.fromNow();
	}

	if ( config.label ) {
		content = ve.msg( config.label, content );
	}

	if ( config.href ) {
		this.$text.append(
			this.$( '<a>' )
				.attr( 'target', '_blank' )
				.attr( 'href', config.href )
				.append( content )
		);
	} else {
		this.$text.append( content );
	}

	this.$element
		.append( this.$icon, this.$label )
		.addClass( 've-ui-mwMediaInfoFieldWidget' )
		.addClass( 've-ui-mwMediaInfoFieldWidget-' + this.type );
	this.$icon.addClass( 've-ui-mwMediaInfoFieldWidget-icon' );

	if ( this.type === 'description' ) {
		// Limit height
		this.readMoreButton = new OO.ui.ButtonWidget( {
			$: this.$,
			framed: false,
			icon: 'expand',
			label: ve.msg( 'visualeditor-dialog-media-info-readmore' ),
			classes: [ 've-ui-mwMediaInfoFieldWidget-readmore' ]
		} );
		this.readMoreButton.toggle( false );
		this.readMoreButton.connect( this, { click: 'onReadMoreClick' } );

		this.$text
			.css( 'maxHeight', config.descriptionHeight || '4em' );

		this.$element
			.append( this.readMoreButton.$element );
	}

	this.setLabel( this.$text );
};

/* Setup */

OO.inheritClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.Widget );
OO.mixinClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.IconElement );
OO.mixinClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.LabelElement );

/* Static Properties */

ve.ui.MWMediaInfoFieldWidget.static.tagName = 'div';

/**
 * Toggle the read more button according to whether it should be
 * visible or not.
 */
ve.ui.MWMediaInfoFieldWidget.prototype.toggleReadMoreButton = function () {
	var show = this.$text.height() < this.$text.prop( 'scrollHeight' );
	// Only show the readMore button if it should be shown, and not while
	// the expansion animation is ongoing
	this.readMoreButton.toggle( show );
};

/**
 * Respond to read more button click event.
 */
ve.ui.MWMediaInfoFieldWidget.prototype.onReadMoreClick = function () {
	this.readMoreButton.toggle( false );
	this.$text.css( 'maxHeight', this.$text.prop( 'scrollHeight' ) );
};

/**
 * Get field type; 'attribute' or 'description'
 * @return {string} Field type
 */
ve.ui.MWMediaInfoFieldWidget.prototype.getType = function () {
	return this.type;
};
