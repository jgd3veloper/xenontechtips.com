( function( $, elementorFrontend ) {

	"use strict";

	var ee = { 

		isAdminBar : function() {
			return $('body').is('.admin-bar');
		},

		init : function() {

			var widgets = {
				'ee-calendar.default':			ee.Calendar,
				'ee-google-map.default':		ee.GoogleMap,
				'ee-audio-player.default':		ee.AudioPlayer,
				'ee-offcanvas.classic':			ee.Offcanvas,
				'ee-slide-menu.classic':		ee.SlideMenu,
				'ee-popup.classic': 			ee.Popup,
				'ee-age-gate.classic': 			ee.AgeGate,
				'ee-toggle-element.classic':	ee.ToggleElement,
				'ee-switcher.classic': 			ee.Switcher,
				'ee-inline-svg.default': 		ee.InlineSvg,
				'posts-extra.classic': 			ee.PostsClassic,
				'posts-extra.carousel': 		ee.PostsCarousel,
				'table.default': 				ee.Table,
				'unfold.default': 				ee.Unfold,
				'portfolio.default': 			ee.Portfolio,
				'gallery-extra.default': 		ee.GalleryExtra,
				'gallery-slider.default': 		ee.GallerySlider,
				'timeline.default': 			ee.Timeline,
				'heading-extended.default': 	ee.HeadingExtra,
				'image-comparison.default': 	ee.ImageComparison,
				'devices-extended.default': 	ee.Devices,
				'hotspots.default': 			ee.Tooltips,
				'button-group.default': 		ee.Tooltips,
				'html5-video.default': 			ee.VideoPlayer,
				'circle-progress.default': 		ee.CircleProgress,
				'ee-scroll-indicator.list': 	ee.ScrollIndicatorList,
				'ee-scroll-indicator.bar': 		ee.ScrollIndicatorBar,
				'ee-scroll-indicator.bullets': 	ee.ScrollIndicatorBullets,
				'ee-search-form.classic': 		ee.SearchFormFilters,
				'ee-search-form.expand': 		[ ee.SearchFormExpand, ee.SearchFormFilters ],
				'ee-search-form.fullscreen': 	[ ee.SearchFormExpand, ee.SearchFormFilters ],
			};

			var globals = {
				'sticky': 						ee.Sticky,
				'parallax': 					ee.ParallaxElement,
				'global-tooltip': 				ee.GlobalTooltip,
			};

			var sections = {
				'parallax-background': 			ee.ParallaxBackground,
			};

			$.each( widgets, function( widget, callback ) {
				if ( 'object' ===  typeof callback ) {
					$.each( callback, function( index, cb ) {
						elementorFrontend.hooks.addAction( 'frontend/element_ready/' + widget, cb );
					});
				} else {
					elementorFrontend.hooks.addAction( 'frontend/element_ready/' + widget, callback );
				}
			});

			$.each( globals, function( extension, callback ) {
				elementorFrontend.hooks.addAction( 'frontend/element_ready/global', callback );
			});

			$.each( sections, function( extension, callback ) {
				elementorFrontend.hooks.addAction( 'frontend/element_ready/section', callback );
			});
		},

		getGlobalSettings : function( section ) {

			if ( section in elementorFrontendConfig.settings ) {
				return elementorFrontendConfig.settings[section];
			}

			return false;
		},

		getItems : function ( items, itemKey ) {
			if ( itemKey) {
				var keyStack = itemKey.split('.'),
					currentKey = keyStack.splice(0, 1);

				if ( ! keyStack.length ) {
					return items[ currentKey ];
				}

				if ( ! items[ currentKey ] ) {
					return;
				}

				return this.getItems( items[ currentKey ], keyStack.join('.'));
			}

			return items;
		},

		getElementSettings : function( $element, setting ) {

			var elementSettings = {},
				modelCID 		= $element.data( 'model-cid' );

			if ( elementorFrontend.isEditMode() && modelCID ) {
				var settings 		= elementorFrontend.config.elements.data[ modelCID ],
					type 			= settings.attributes.widgetType || settings.attributes.elType,
					settingsKeys 	= elementorFrontend.config.elements.keys[ type ];

				if ( ! settingsKeys ) {
					settingsKeys = elementorFrontend.config.elements.keys[type] = [];

					jQuery.each( settings.controls, function ( name, control ) {
						if ( control.frontend_available ) {
							settingsKeys.push( name );
						}
					});
				}

				jQuery.each( settings.getActiveControls(), function( controlKey ) {
					if ( -1 !== settingsKeys.indexOf( controlKey ) ) {
						elementSettings[ controlKey ] = settings.attributes[ controlKey ];
					}
				} );
			} else {
				elementSettings = $element.data('settings') || {};
			}

			return ee.getItems( elementSettings, setting );
		},

		getElementType : function ( $element ) {
			if ( 'section' === $element.data( 'element_type' ) || 'column' === $element.data( 'element_type' ) ) {
				return $element.data( 'element_type' );
			}

			return 'widget';
		},

		getElementSkin : function ( $element ) {
			return $element.attr('data-widget_type').split('.')[1];
		},

		getWindow : function() {
			return elementorFrontend.isEditMode() ? window.elementor.$previewContents : $(window);
		},

		onElementRemove : function( $element, callback ) {
			if ( elementorFrontend.isEditMode() ) {
				// Make sure sticky is destroyed when element is removed in editor mode
				elementor.channels.data.on( 'element:before:remove', function ( model ) {
					if ( $element.data('id') === model.id ) {
						callback();
					}
				});
			}
		},

		////////////////////////////////////////////
		// SearchFormFilters 					////
		////////////////////////////////////////////

		SearchFormFilters : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope ),
				scopeId 		= $scope.data('id'),
				$form 			= $scope.find( '.ee-search-form' ),
				$hidden 		= $scope.find( 'input[type=hidden][name=ee_search_query]' ),
				$input 			= $scope.find( '.ee-search-form__input' ),
				$submit 		= $scope.find( '.ee-search-form__submit' ),
				$container 		= $scope.find( '.ee-search-form__container' ),
				$searchFields 	= $form.find(':input').filter('.ee-form__field__control--search'),
				$selectFields 	= $form.find('.ee-form__field--select.ee-custom').find(':input').filter('.ee-form__field__control--select'),
				$allFields 		= $form.find(':input').filter('.ee-form__field__control--all'),
				$filterFields 	= $form.find(':input').filter(':not(.ee-form__field__control--sent)'),
				$window 		= ee.getWindow(),
				skin 			= ee.getElementSkin( $scope ),

				isOpen 			= false;

			ee.SearchFormFilters.init = function() {

				// Initially set the query field
				setHiddenField();

				// Handle checkboxes
				if ( $allFields.filter('[type=checkbox]').length ) {
					$allFields.filter('[type=checkbox]').on( 'change', function() {
						allCheck( $(this) );
					});

					$searchFields.filter('[type=checkbox]').on( 'change', function() {
						optionsCheck( $(this) );
					});
				}

				// Beautify Selects
				$selectFields.select2({
					containerCssClass 		: 'ee-select2 ee-form__field__control ee-form__field__control--text ee-form__field__control--select2',
					dropdownCssClass 		: 'ee-select2__dropdown ee-select2__dropdown--' + scopeId,
					minimumResultsForSearch : -1,
					width 					: '100%',
				});

				// Not needed on edit more
				if ( ! elementorFrontend.isEditMode() ) {
					// Form submit event
					$form.on( 'submit', function( e ) {
						if ( ! $input.val() ) {
							$container.addClass( 'ee--empty' );
							e.preventDefault();
							return false;
						}

						$filterFields.attr( 'disabled', true );
					});

					$input.on( 'click blur', function() {
						$container.removeClass( 'ee--empty' );
					});

					// On filters change we set the query field
					$searchFields.on( 'change', function() {
						setHiddenField();
					});
				}
			};

			var optionsCheck = function( $control ) {
				var name = $control.attr('name'),
					$checkAllControl = $allFields.filter('[name=' + name + ']');

				if ( $checkAllControl.is(':checked') ) {
					$checkAllControl.attr( 'checked', false );
				}
			};

			var allCheck = function( $checkAllControl ) {

				var name = $checkAllControl.attr('name'),
					$controls = $searchFields.filter('[name=' + name + ']');

				if ( $checkAllControl.is(':checked') ) {
					$controls.attr( 'checked', true );
				} else {
					$controls.attr( 'checked', false );
				}

				setHiddenField();
			};

			var setHiddenField = function() {
				var data = JSON.stringify( ElementorExtrasUtils.serializeObject( $searchFields ) );

				$hidden.val( data.replace(/\\/g, "") );
			};

			ee.SearchFormFilters.destroy = function() {};

			ee.onElementRemove( $scope, function() {
				ee.SearchFormFilters.destroy();
			});

			ee.SearchFormFilters.init();
		},

		////////////////////////////////////////////
		// SearchFormExpand 					////
		////////////////////////////////////////////

		SearchFormExpand : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope ),
				$form 			= $scope.find( '.ee-search-form' ),
				$container 		= $scope.find( '.ee-search-form__container' ),
				$overlay 		= $scope.find( '.ee-search-form__overlay' ),
				$close 			= $scope.find( '.ee-search-form__overlay__close' ),
				$fields 		= $scope.find( '.ee-search-form__fields' ),
				$input 			= $scope.find( '.ee-search-form__input' ),
				$submit 		= $scope.find( '.ee-search-form__submit' ),
				$window 		= ee.getWindow(),
				skin 			= ee.getElementSkin( $scope ),

				isOpen 			= false;

			ee.SearchFormExpand.init = function() {
				$submit.on( 'click', ee.SearchFormExpand.onSubmitClick );

				if ( skin === 'expand' ) {
					$(document).on( 'click', ee.SearchFormExpand.onDocumentClick );
				} else {
					$overlay.on( 'click', ee.SearchFormExpand.onOverlayClick );
					$close.on( 'click', ee.SearchFormExpand.close );
				}
			};

			ee.SearchFormExpand.isOpen = function( $form ) {
				return $form.is('.ee--active');
			};

			ee.SearchFormExpand.open = function( $form ) {
				$form.addClass( 'ee--active' );
				$form.find('.ee-search-form__input').focus();
			};

			ee.SearchFormExpand.close = function( $form ) {
				$form.removeClass( 'ee--active' );
				$form.find('.ee-search-form__input').blur();
			};

			ee.SearchFormExpand.onSubmitClick = function( e ) {
				e.preventDefault();

				if ( ee.SearchFormExpand.isOpen( $form ) ) {
					if ( $input.val() ) $form.submit(); else ee.SearchFormExpand.close( $form );
					return;
				}

				ee.SearchFormExpand.open( $form );
			};

			ee.SearchFormExpand.onDocumentClick = function( e ) {

				var $target = $( e.target );
				
				if ( e.target !== $form[0] && ! $target.closest( '.ee-search-form' ).length )
					ee.SearchFormExpand.close( $form );
			};

			ee.SearchFormExpand.onOverlayClick = function( e ) {

				var $target = $( e.target );
				
				if ( e.target === $overlay[0] && ee.SearchFormExpand.isOpen( $form ) )
					ee.SearchFormExpand.close( $form );
			};

			ee.SearchFormExpand.destroy = function() {
				$submit.off( 'click', ee.SearchFormExpand.onSubmitClick );

				if ( skin === 'expand' ) {
					$(document).off( 'click', ee.SearchFormExpand.onDocumentClick );
				} else {
					$overlay.off( 'click', ee.SearchFormExpand.onOverlayClick );
					$close.off( 'click', ee.SearchFormExpand.close );
				}
			};

			ee.onElementRemove( $scope, function() {
				ee.SearchFormExpand.destroy();
			});

			ee.SearchFormExpand.init();
		},

		////////////////////////////////////////////
		// ScrollIndicatorList 					////
		////////////////////////////////////////////

		ScrollIndicatorList : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope ),
				$widget 		= $scope.find( '.ee-scroll-indicator' ),
				skin 			= ee.getElementSkin( $scope ),
				$window 		= ee.getWindow(),
				scrollIndicatorArgs = {
					progress 	: 'circle',
					click 		: 'yes' === elementSettings.click,
				};

			ee.ScrollIndicatorList.init = function() {

				ee.ScrollIndicatorList.destroy();

				$widget.scrollIndicator( scrollIndicatorArgs );
			};

			ee.ScrollIndicatorList.destroy = function() {
				if ( $widget.data( 'scrollIndicator' ) )
					$widget.data( 'scrollIndicator' ).destroy();
			};

			ee.onElementRemove( $scope, function() {
				ee.ScrollIndicatorList.destroy();
			});

			ee.ScrollIndicatorList.init();
		},

		////////////////////////////////////////////
		// ScrollIndicatorBar 					////
		////////////////////////////////////////////

		ScrollIndicatorBar : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope ),
				$widget 		= $scope.find( '.ee-scroll-indicator' ),
				skin 			= ee.getElementSkin( $scope ),
				$window 		= ee.getWindow(),
				scrollIndicatorArgs = {
					mode 		: 'anchor',
					progress 	: 'background',
					click 		: 'yes' === elementSettings.click,
				};

			ee.ScrollIndicatorBar.init = function() {

				ee.ScrollIndicatorBar.destroy();

				$widget.scrollIndicator( scrollIndicatorArgs );
			};

			ee.ScrollIndicatorBar.destroy = function() {
				if ( $widget.data( 'scrollIndicator' ) )
					$widget.data( 'scrollIndicator' ).destroy();
			};

			ee.onElementRemove( $scope, function() {
				ee.ScrollIndicatorBar.destroy();
			});

			ee.ScrollIndicatorBar.init();
		},

		////////////////////////////////////////////
		// ScrollIndicatorBullets 				////
		////////////////////////////////////////////

		ScrollIndicatorBullets : function( $scope, $ ) {

			ee.Tooltips( $scope, $ );

			var elementSettings = ee.getElementSettings( $scope ),
				$widget 		= $scope.find( '.ee-scroll-indicator' ),
				skin 			= ee.getElementSkin( $scope ),
				$window 		= ee.getWindow();

				skin 			= ( '' !== skin && 'default' !== skin ) ? skin + '_' : '';

			var scrollIndicatorArgs = {
					autoHover 		: 'scroll' === elementSettings[ skin + 'trigger' ],
					progress 		: 'background',
					click 			: 'yes' === elementSettings.click,
					property 		: 'height'
				};

			ee.ScrollIndicatorBullets.init = function() {

				ee.ScrollIndicatorBullets.destroy();

				$widget.scrollIndicator( scrollIndicatorArgs );
			};

			ee.ScrollIndicatorBullets.destroy = function() {
				if ( $widget.data( 'scrollIndicator' ) )
					$widget.data( 'scrollIndicator' ).destroy();
			};

			ee.onElementRemove( $scope, function() {
				ee.ScrollIndicatorBullets.destroy();
			});

			ee.ScrollIndicatorBullets.init();
		},

		////////////////////////////////////////////
		// SlideMenu 							////
		////////////////////////////////////////////

		SlideMenu : function( $scope, $ ) {

			ee.SlideMenu.elementSettings = ee.getElementSettings( $scope );

			var $menu = $scope.find( '.ee-slide-menu__menu' ),
				slideMenuArgs = {
					linkNavigation 	: 'yes' === ee.SlideMenu.elementSettings.link_navigation,
					backLabel 		: ee.SlideMenu.elementSettings.back_text,
				};

			ee.SlideMenu.init = function() {
				$menu.slideMenu( slideMenuArgs );
			};

			ee.SlideMenu.init();
		},

		////////////////////////////////////////////
		// Calendar 							////
		////////////////////////////////////////////

		Calendar : function( $scope, $ ) {

			ee.Calendar.elementSettings 	= ee.getElementSettings( $scope );

			var $calendar 	= $scope.find( '.ee-calendar' ),
				$template 	= $calendar.find( '#ee-calendar__template' ).html(),
				$events 	= $calendar.find( '.ee-calendar-event' ),
				leftArrow 	= elementorFrontend.config.is_rtl ? 'right' : 'left',
				rightArrow 	= elementorFrontend.config.is_rtl ? 'left' : 'right',

				eventDateFormat = $.trim( ee.Calendar.elementSettings.event_date_format ) || 'MMMM Do',
				
				eventsTemplate = 
				"<% if ( days[d].events.length ) { %>" +
					"<div class='ee-calendar__day__events'>" +
						"<% _.each(days[d].events, function(event) { %>" +
							"<div class='ee-calendar__day__event'>" +
								"<a <% if ( '' !== event.link ) { %>href='<%= event.link %>' <% if ( '' !== event.target ) { %>target='<%= event.target %>'<% } %> <% if ( '' !== event.rel ) { %>rel='<%= event.rel %>'<% } %><% } %> data-title='<%= event.name %>' class='ee-calendar__day__event__name'><%= event.name %></a>" +
							"</div>" +
						"<% }); %>" +
					"</div>" +
				"<% } %>",
				eventsMonthTemplate = 
				"<div class='ee-calendar__events'>" +
					"<div class='ee-calendar__events__header ee-calendar__table__head'>" +
						"<span class='ee-calendar__events__header__title'>" + ee.Calendar.elementSettings.event_list_heading + "</span>" +
						"<span class='ee-arrow ee-calendar__controls__button ee-calendar__events__hide'><i class='eicon-close'></i></span>" +
					"</div>" +
					"<div class='ee-calendar__events__list ee-nav ee-nav--stacked'>" +
						"<% _.each(eventsThisMonth, function(event) { %>" +
							"<a <% if ( '' !== event.link ) { %>href='<%= event.link %>' <% if ( '' !== event.target ) { %>target='<%= event.target %>'<% } %> <% if ( '' !== event.rel ) { %>rel='<%= event.rel %>'<% } %><% } %> class='ee-calendar__events__event ee-calendar__cell__content ee-nav__item'>" +
							"<%= moment(event.start).format('" + eventDateFormat + "') %>" +
							"<% if ( event.end !== event.start ) { %>" +
								" - <%= moment(event.end).format('" + eventDateFormat + "') %>" +
							"<% } %>" +
							": <%= event.name %>" +
							"</a>" +
						"<% }); %>" +
					"</div>" +
				"</div>",
				clndrTemplate =
				"<div class='ee-calendar__controls clndr-controls'>" +
					"<span class='ee-calendar__controls__button ee-calendar__button--previous ee-arrow ee-arrow--" + leftArrow + " clndr-control-button clndr-previous-button'><i class='eicon-chevron-" + leftArrow + "'></i></span>" +
					"<div class='ee-calendar__controls__month ee-calendar__controls__content month'><%= month %> <%= year %></div>" +
					"<span class='ee-calendar__controls__button ee-calendar__button--next ee-arrow ee-arrow--" + rightArrow + " clndr-control-button clndr-next-button'><i class='eicon-chevron-" + rightArrow + "'></i></span>" +
				"</div>" +
				"<div class='ee-calendar__month clndr-events'>" +
					"<table class='ee-table ee-calendar__table clndr-table' border='0' cellspacing='0' cellpadding='0'>" +
						"<thead class='ee-table__head ee-calendar__table__head'>" +
							"<tr class='ee-table__row ee-calendar__header header-days'>" +
							"<% _.each(daysOfTheWeek, function (day) { %>" +
								"<td class='ee-table__cell ee-calendar__cell ee-calendar__header__week'>" +
									"<div class='ee-calendar__week ee-calendar__cell__content'>" +
										"<div class='ee-calendar__cell__wrapper'>" +
											"<%= day %>" +
										"</div>" +
									"</div>" +
								"</td>" +
							"<% }); %>" +
							"</tr>" +
						"</thead>" +
						"<tbody class='ee-table__body ee-calendar__table__body'>" +
						"<% for(var i = 0; i < numberOfRows; i++){ %>" +
							"<tr class='ee-table__row'>" +
							"<% for(var j = 0; j < 7; j++){ %>" +
							"<% var d = j + i * 7; %>" +
								"<td class='ee-table__cell ee-calendar__cell ee-calendar__day align--top <%= days[d].classes %>'>" +
									"<div class='ee-table__cell__content ee-calendar__cell__content ee-calendar__day__content'>" +
										"<div class='ee-calendar__day__wrapper'>" +
											"<div class='ee-calendar__day__header day-contents'><%= days[d].day %></div>" +
											eventsTemplate +
										"</div>" +
									"</div>" +
								"</td>" +
							"<% } %>" +
							"</tr>" +
						"<% } %>" +
						"</tbody>" +
					"</table>" +
					eventsMonthTemplate +
				"</div>";

			moment.updateLocale('en', {
				months : [
					ee.Calendar.elementSettings.month_january,
					ee.Calendar.elementSettings.month_february,
					ee.Calendar.elementSettings.month_march,
					ee.Calendar.elementSettings.month_april,
					ee.Calendar.elementSettings.month_may,
					ee.Calendar.elementSettings.month_june,
					ee.Calendar.elementSettings.month_july,
					ee.Calendar.elementSettings.month_august,
					ee.Calendar.elementSettings.month_september,
					ee.Calendar.elementSettings.month_october,
					ee.Calendar.elementSettings.month_november,
					ee.Calendar.elementSettings.month_december,
				]
			});

			var thisMonth 		= moment().format('YYYY-MM'),
				eventArray 		= [],
				calendarArgs 	= {
					moment: moment,
					classes: {
						past: "ee-calendar__day--passed",
						today: "ee-calendar__day--today",
						event: "ee-calendar__day--event",
						inactive: "ee-calendar__day--inactive",
						lastMonth: "ee-calendar__month--last",
						nextMonth: "ee-calendar__month--next",
						adjacentMonth: "ee-calendar__day--adjacent",
					},
					template 		: clndrTemplate,
					lengthOfTime 	: {
						months 		: null,
						interval 	: 1,
					},
					events 			: eventArray,
					multiDayEvents 	: {
						endDate 	: 'end',
						startDate 	: 'start'
					},
					startWithMonth 	: ( 'yes' === ee.Calendar.elementSettings.default_current_month ) ? moment() : ee.Calendar.elementSettings.default_month,
					constraints: {
						startDate: ee.Calendar.elementSettings.constrain_start,
						endDate: ee.Calendar.elementSettings.constrain_end,
					},
					daysOfTheWeek 				: [
						ee.Calendar.elementSettings.day_sunday,
						ee.Calendar.elementSettings.day_monday,
						ee.Calendar.elementSettings.day_tuesday,
						ee.Calendar.elementSettings.day_wednesday,
						ee.Calendar.elementSettings.day_thursday,
						ee.Calendar.elementSettings.day_friday,
						ee.Calendar.elementSettings.day_saturday,
					],
					weekOffset 					: parseInt( ee.Calendar.elementSettings.first_day ),
					showAdjacentMonths 			: 'yes' === ee.Calendar.elementSettings.show_adjacent_months,
					adjacentDaysChangeMonth 	: 'yes' === ee.Calendar.elementSettings.click_adjacent,
					clickEvents 				: {
						click: function ( target ) {
							if ( target.events.length ) {
								var daysContainer = $calendar.find('.ee-calendar__month');
									daysContainer.toggleClass('show-events', true );
									
								$calendar.find('.ee-calendar__events__hide').click( function() {
									daysContainer.toggleClass('show-events', false);
								});
							}
						},
						nextInterval: function () {
							
						},
						previousInterval: function () {
							
						},
						onIntervalChange: function () {
							
						}
					},
				};

			ee.Calendar.init = function() {
				ee.Calendar.setupEvents();

				if ( $calendar.length )
					$calendar.clndr( calendarArgs );
			}

			ee.Calendar.setupEvents = function() {
				$events.each( function() {
					eventArray.push({
						name 	: $(this).html(),
						start 	: $(this).data('start'),
						end 	: $(this).data('end'),
						link 	: $(this).data('link'),
						target 	: $(this).data('target'),
						rel 	: $(this).data('rel'),
						archive : $(this).data('archive'),
					});
				});
			};

			ee.Calendar.init();
		},

		////////////////////////////////////////////
		// GoogleMap 							////
		////////////////////////////////////////////

		GoogleMap : function( $scope, $ ) {

			ee.GoogleMap.elementSettings = ee.getElementSettings( $scope );

			var $map 		= $scope.find( '.ee-google-map' );

			// Bail out early
			if ( ! $map.length ) return;

			var $pins 		= $map.find( '.ee-google-map__pin' ),
				$navigation = $scope.find( '.ee-google-map__navigation' ),
				settings 	= ee.GoogleMap.elementSettings,
				gmapArgs 	= {
					center 					: [ 48.8583736, 2.2922873 ],

					mapTypeId 				: google.maps.MapTypeId[ settings.map_type ],
					scrollwheel 			: 'yes' === settings.scrollwheel,
					clickableIcons 			: 'yes' === settings.clickable_icons,
					disableDoubleClickZoom 	: 'yes' !== settings.doubleclick_zoom,
					keyboardShortcuts 		: 'yes' === settings.keyboard_shortcuts,
					draggable 				: ( ! elementorFrontend.isEditMode() && 'yes' === settings.draggable ),

					fullscreenControl 		: 'yes' === settings.fullscreen_control,
					mapTypeControl 			: 'yes' === settings.map_type_control,
					rotateControl 			: 'yes' === settings.rotate_control,
					scaleControl 			: 'yes' === settings.scale_control,
					streetViewControl 		: 'yes' === settings.streetview_control,
					zoomControl 			: 'yes' === settings.zoom_control,
				},
				polygonArgs = {
					default : {
						strokeColor 	: ( settings.polygon_stroke_color ) ? settings.polygon_stroke_color : '#FF0000',
						strokeWeight 	: ( settings.polygon_stroke_weight ) ? settings.polygon_stroke_weight.size : 2,
						strokeOpacity 	: ( settings.polygon_stroke_opacity ) ? settings.polygon_stroke_opacity.size : 0.8,
						fillColor 		: ( settings.polygon_fill_color ) ? settings.polygon_fill_color : '#FF0000',
						fillOpacity 	: ( settings.polygon_fill_opacity ) ? settings.polygon_fill_opacity.size : 0.35,
					},
					hover : {
						strokeColor 	: ( settings.polygon_stroke_color_hover ) ? settings.polygon_stroke_color_hover : '#FF0000',
						strokeWeight 	: ( settings.polygon_stroke_weight_hover ) ? settings.polygon_stroke_weight_hover.size : 2,
						strokeOpacity 	: ( settings.polygon_stroke_opacity_hover ) ? settings.polygon_stroke_opacity_hover.size : 0.8,
						fillColor 		: ( settings.polygon_fill_color_hover ) ? settings.polygon_fill_color_hover : '#FF0000',
						fillOpacity 	: ( settings.polygon_fill_opacity_hover ) ? settings.polygon_fill_opacity_hover.size : 0.35,
					}
				},
				markers 	= [],
				paths 		= [],
				instance 	= null;

			ee.GoogleMap.init = function() {

				var mapStyle = settings.map_style_json;

				if ( 'api' === settings.map_style_type && settings.map_style_api ) {
					var jsonParse = JSON.parse( settings.map_style_api );

					if ( jsonParse ) {
						mapStyle = JSON.parse( settings.map_style_api ).json;
					}
				}

				if ( '' !== $.trim( mapStyle ) && undefined !== mapStyle ) {
					gmapArgs.styles = ee.GoogleMap.parseStyles( mapStyle );
				}

				if ( 'yes' !== settings.fit ) {
					if ( 'undefined' !== typeof settings.zoom ) {
						gmapArgs.zoom = settings.zoom.size;
					}

					if ( $map.data('lat') && $map.data('lng') ) {
						gmapArgs.center = [ $map.data('lat'), $map.data('lng') ];
					}
				}

				instance = $map.gmap3( gmapArgs );

				ee.GoogleMap.addPins();

				if ( 'yes' === settings.popups )
					ee.GoogleMap.addInfoWindows();

				if ( 'yes' === settings.route && $pins.length > 1 )
					ee.GoogleMap.addRoute();

				if ( 'yes' === settings.polygon )
					ee.GoogleMap.addPolygon();

				if ( 'yes' === settings.navigation )
					ee.GoogleMap.navigation();

				// Init events
				ee.GoogleMap.events();

				// Center to fit or custom
				ee.GoogleMap.center();
			};

			ee.GoogleMap.events = function() {
				$map._resize( ee.GoogleMap.onResize );
			};

			ee.GoogleMap.onResize = function() {
				ee.GoogleMap.center();
			};

			ee.GoogleMap.center = function() {
				if ( 'yes' === settings.fit ) {
					instance.wait(2000).fit();
				} else {
					instance.get(0).setCenter( new google.maps.LatLng( gmapArgs.center[0], gmapArgs.center[1] ) );
				}
			};

			ee.GoogleMap.parseStyles = function( style ) {

				try {
					var json = JSON.parse( style );

					if ( json && typeof json === "object") { return json; }
				}
				catch ( e ) {
					alert( 'Invalid JSON' );
				}

				return false;
			};

			ee.GoogleMap.addPolygon = function() {

				if ( $pins.length <= 1 )
					return;

				instance
					.polygon( {
						strokeColor 	: polygonArgs.default.strokeColor,
						strokeWeight 	: polygonArgs.default.strokeWeight,
						strokeOpacity 	: polygonArgs.default.strokeOpacity,
						fillColor 		: polygonArgs.default.fillColor,
						fillOpacity 	: polygonArgs.default.fillOpacity,
						paths 			: paths,
					} )
					.on({
						mouseover: function ( polygon, event ) {
							polygon.setOptions( {
								strokeColor 	: polygonArgs.hover.strokeColor,
								strokeWeight 	: polygonArgs.hover.strokeWeight,
								strokeOpacity 	: polygonArgs.hover.strokeOpacity,
								fillColor 		: polygonArgs.hover.fillColor,
								fillOpacity 	: polygonArgs.hover.fillOpacity,
							} );
						},
						mouseout: function ( polygon, event ) {
							polygon.setOptions( {
								strokeColor 	: polygonArgs.default.strokeColor,
								strokeWeight 	: polygonArgs.default.strokeWeight,
								strokeOpacity 	: polygonArgs.default.strokeOpacity,
								fillColor 		: polygonArgs.default.fillColor,
								fillOpacity 	: polygonArgs.default.fillOpacity,
							} );
						}
					});
			};

			ee.GoogleMap.addPins = function() {
				if ( ! $pins.length )
					return;

				$pins.each( function() {
					var marker = {},
						pin = {
							id 			: $(this).data('id'),
							input 		: $(this).data('input'),
							lat 		: $(this).data('lat'),
							lng 		: $(this).data('lng'),
							trigger 	: $(this).data('trigger'),
							icon 		: $(this).data('icon'),
							content 	: $(this).html(),
						};

					if ( ! pin.lat || ! pin.lng ) {
						return;
					}

					marker.id 		= pin.id;
					marker.trigger 	= pin.trigger;
					marker.position = [ pin.lat, pin.lng ];

					paths.push( marker.position );

					if ( pin.icon ) {
						var iconSize = ( settings.pin_size ) ? settings.pin_size.size : 50,
							iconPosition = ee.GoogleMap.getIconPosition( iconSize );

						marker.icon = {
							url 		: pin.icon,
							scaledSize	: new google.maps.Size( iconSize, iconSize ),
							origin 		: new google.maps.Point( 0, 0 ),
							anchor 		: new google.maps.Point( iconPosition[0], iconPosition[1] ),
						};
					}

					if ( pin.content && settings.popups )
						marker.content = pin.content;

					markers.push( marker );
				});

				instance.marker( markers );
			};

			ee.GoogleMap.getIconPosition = function( size ) {
				var horiz = 25,
					vert = 50;

				switch ( settings.pin_position_horizontal ) {
					case 'left' :
						horiz = size;
						break;
					case 'center' :
						horiz = size / 2;
						break;
					case 'right' :
						horiz = 0;
						break;
					default :
						horiz = size / 2;
				}

				switch ( settings.pin_position_vertical ) {
					case 'top' :
						vert = size;
						break;
					case 'middle' :
						vert = size / 2;
						break;
					case 'bottom' :
						vert = 0;
						break;
					default :
						vert = size;
				}

				return [ horiz, vert ];
			};

			ee.GoogleMap.addInfoWindows = function() {
				if ( ! $pins.length )
					return;

				instance
					.infowindow( markers )
					.then( function( infowindow ) {

						var map = this.get(0),
							marker = this.get(1);

						marker.forEach( function( pin, index ) {

							if ( 'auto' === pin.trigger ) {
								infowindow[ index ].open( map, pin );

								pin.addListener( 'click', function() {
									infowindow[ index ].open( map, pin );
								});
							} else if ( 'mouseover' === pin.trigger ) {
								pin.addListener( 'mouseover', function() {
									infowindow[ index ].open( map, pin );
								});
								pin.addListener( 'mouseout', function() {
									infowindow[ index ].close( map, pin );
								});
							} else if ( 'click' === pin.trigger ) {
								pin.addListener( 'click', function() {
									infowindow[ index ].open( map, pin );
								});
							}
						})
					});
			};

			ee.GoogleMap.addRoute = function() {

				if ( $pins.length <= 1 )
					return;

				var points = markers.slice(),
					origin = ee.GoogleMap.getMarkerDataForRoutes( markers[0] ),
					destination = ee.GoogleMap.getMarkerDataForRoutes( markers[ markers.length - 1 ] ),
					waypoints = ( markers.length >= 3 ) ? ee.GoogleMap.getWaypoints( points ) : null; // Waypoints make sense for more than 2 markers

				instance
					.route({
						origin : origin,
						destination : destination,
						waypoints : waypoints,
						travelMode : google.maps.DirectionsTravelMode.DRIVING
					})
					.directionsrenderer( function( results ) {
						if ( results ) {
							return {
								suppressMarkers: 'yes' !== settings.route_markers,
								directions: results,
							}
						}
					});
			};

			ee.GoogleMap.getWaypoints = function( points ) {
				var waypoints = [];

				// Remove first and last markers
				points.shift();
				points.pop();

				points.forEach( function( point, index ) {
					waypoints.push( {
						location : ee.GoogleMap.getMarkerDataForRoutes( point ),
						stopover : true,
					} );
				} );

				return waypoints;
			};

			ee.GoogleMap.getMarkerDataForRoutes = function( marker ) {
				return new google.maps.LatLng( marker.position[0], marker.position[1] );
			};

			ee.GoogleMap.navigation = function() {
				var $items 	= $navigation.find( '.ee-google-map__navigation__item' ),
					$all 	= $items.filter( '.ee-google-map__navigation__item--all' );

				$all.addClass( 'ee--is-active' );

				$items.on( 'click', function( e ) {
					e.preventDefault();
					e.stopPropagation();

					$items.removeClass( 'ee--is-active' );
					$(this).addClass( 'ee--is-active' );

					var marker = ElementorExtrasUtils.findObjectByKey( markers, 'id', $(this).data('id') );

					if ( marker ) {
						instance.get(0).setCenter( new google.maps.LatLng( marker.position[0], marker.position[1] ) );
						instance.get(0).setZoom( 18 );
					} else {
						instance.fit();
					}
				});
			};

			ee.GoogleMap.init();
		},

		////////////////////////////////////////////
		// AudioPlayer 							////
		////////////////////////////////////////////

		AudioPlayer : function( $scope, $ ) {

			ee.AudioPlayer.elementSettings = ee.getElementSettings( $scope );

			var $player = $scope.find( '.ee-audio-player' );

			ee.AudioPlayer.init = function() {

				$player.audioPlayer({
					restartOnPause		: 'yes' === ee.AudioPlayer.elementSettings.restart_on_pause,
					loopPlaylist 		: 'yes' === ee.AudioPlayer.elementSettings.loop_playlist,
					autoplay 			: 'yes' === ee.AudioPlayer.elementSettings.autoplay && ! elementorFrontend.isEditMode(),
					volume				: ee.AudioPlayer.elementSettings.volume.size,
				});
			};

			ee.AudioPlayer.init();
		},

		////////////////////////////////////////////
		// Offcanvas 							////
		////////////////////////////////////////////

		Offcanvas : function( $scope, $ ) {

			ee.Offcanvas.elementSettings 	= ee.getElementSettings( $scope );

			var slidebarPos 	= ee.Offcanvas.elementSettings.position,
				slidebarAnim 	= ee.Offcanvas.elementSettings.animation,
				scopeId 		= $scope.data('id'),
				$trigger 		= $scope.find( '#slidebar-trigger_' + scopeId ),
				$content 		= $scope.find( '.ee-offcanvas__content' ),
				slidebarId 		= 'oc' + scopeId,
				$body 			= $( 'body' ),
				$window 		= ee.getWindow(),
				$html 			= elementorFrontend.isEditMode() ? window.elementor.$previewContents.find('html') : $('html'),
				$close 			= $( '.ee-offcanvas__header__close' ),
				$overlay 		= $( '<div class="ee-offcanvas__overlay"></div>' ),
				$wrapper 		= $( '<div class="ee-offcanvas__container" canvas="container"></div>' ),
				$slidebar 		= $( '<div class="ee-offcanvas__slidebar" id="' + slidebarId + '" off-canvas="' + slidebarId + ' ' + slidebarPos + ' ' + slidebarAnim + '" />' );

			ee.Offcanvas.setTriggers = function() {
				if ( 'id' === ee.Offcanvas.elementSettings.trigger_source && '' !== ee.Offcanvas.elementSettings.trigger_id ) {
					$trigger = $( '#' + ee.Offcanvas.elementSettings.trigger_id );

					$trigger
						.addClass( 'ee-offcanvas__trigger' )
						.attr( 'data-offcanvas-id', slidebarId );
				}

				if ( 'class' === ee.Offcanvas.elementSettings.trigger_source && '' !== ee.Offcanvas.elementSettings.trigger_class ) {
					$trigger = $( '.' + ee.Offcanvas.elementSettings.trigger_class );

					$trigger
						.addClass( 'ee-offcanvas__trigger' )
						.attr( 'data-offcanvas-id', slidebarId );
				}

				if ( 'id' === ee.Offcanvas.elementSettings.header_close_source && '' !== ee.Offcanvas.elementSettings.header_close_id ) {
					$close = $content.find( '#' + ee.Offcanvas.elementSettings.header_close_id );
					$close.addClass( 'ee-offcanvas__close' );
				}

				if ( 'class' === ee.Offcanvas.elementSettings.header_close_source && '' !== ee.Offcanvas.elementSettings.header_close_class ) {
					$close = $content.find( '.' + ee.Offcanvas.elementSettings.header_close_class );
					$close.addClass( 'ee-offcanvas__close' );
				}
			};

			ee.Offcanvas.prepare = function() {

				// Offcanvas doesn't work with 100% height on the document,
				// so we take the risk of removing it
				$('html').css( { 'height' : 'auto' } );

				// Wrap body in container only if it's not already wrapped
				// which is the case when offcanvas is already available through
				// other widgets on the page
				if ( ! $body.find( '.ee-offcanvas__container' ).length )
					$body.wrapInner( $wrapper );
				
				// Make sure we redefine the wrapper after it wrap body
				$wrapper = $body.find( '.ee-offcanvas__container' );

				// Remove slidebar if it exists
				$( '#' + slidebarId ).remove();

				// Append content to slidebar
				$slidebar.append( $content );

				// Add slidebar to body
				$body.prepend( $slidebar );

				// $slidebar.find( '.elementor-widget' ).each( function() {
				// 	elementorFrontend.hooks.doAction( 'frontend/element_ready/' + $(this).data('element_type') );
				// });
			};

			ee.Offcanvas.onResize = function() {
				if ( elementorFrontend.isEditMode() )
					offcanvas.controller.css();
			};

			ee.Offcanvas.destroy = function() {

				// Remove this slidebar
				$slidebar.remove();
			};

			ee.Offcanvas.events = function() {

				if ( elementorFrontend.isEditMode() )
					$slidebar._resize( ee.Offcanvas.onResize );

				$trigger.on( 'click', function ( event ) {

					// Stop default action and bubbling
					event.stopPropagation();
					event.preventDefault();

					// Restyle elements
					offcanvas.controller.css();

					// Toggle this offcanvas
					if ( offcanvas.controller.isActiveSlidebar( slidebarId ) ) {
						offcanvas.controller.close( slidebarId );
					} else {
						offcanvas.controller.open( slidebarId );
					}

					// Add active class to trigger
					$(this).addClass( 'ee--is-active' );
				} );

				$close.on( 'click', function ( event ) {

					// Stop default action and bubbling
					event.stopPropagation();
					event.preventDefault();
					
					offcanvas.controller.close();
				} );

				if ( ! $body.find( '.ee-offcanvas__overlay' ).length ) {
					$wrapper.append( $overlay );
				} else {
					$overlay = $wrapper.find( '> .ee-offcanvas__overlay' );
				}

				$overlay.on('click', function () {
					if ( offcanvas.controller.isActiveSlidebar( slidebarId ) )
						offcanvas.controller.close();
				});

				$( offcanvas.controller.events ).on( 'opening', function ( event, id ) {

					// Add widget specific body class
					$body.addClass( 'ee-offcanvas--id-' + id );

					$body.removeClass( 'ee-offcanvas--closed ee-offcanvas--open' );
					$body.addClass( 'ee-offcanvas--opening' );

					ee.Offcanvas.setOverflows();
				} );

				$( offcanvas.controller.events ).on( 'opened', function ( event, id ) {

					$body.removeClass( 'ee-offcanvas--closed ee-offcanvas--opening' );
					$body.addClass('ee-offcanvas--open');

					if ( 'yes' === ee.Offcanvas.elementSettings.container_scroll ) {
						$body.addClass('ee-offcanvas--scroll');
					}
				} );

				$( offcanvas.controller.events ).on( 'closing', function ( event, id ) {

					$body.removeClass( 'ee-offcanvas--open ee-offcanvas--opening ee-offcanvas--closed' );
					$body.addClass( 'ee-offcanvas--closing' );
				} );

				$( offcanvas.controller.events ).on( 'closed', function ( event, id ) {

					$body.removeClass( 'ee-offcanvas--open ee-offcanvas--closing' );

					ee.Offcanvas.removeOverflows();

					if ( 'yes' === ee.Offcanvas.elementSettings.container_scroll ) {
						$body.removeClass('ee-offcanvas--scroll');
					}

					$body.removeClass( function ( index, className ) {
						return (className.match (/(^|\s)ee-offcanvas--id-\S+/g) || []).join(' ');
					});

					$body.addClass( 'ee-offcanvas--closed' );

					$( '.ee-offcanvas__trigger' ).removeClass( 'ee--is-active' );

					$(window).trigger('resize');
				} );

				ee.onElementRemove( $scope, function() {
					ee.Offcanvas.destroy();
				});
			};

			ee.Offcanvas.anchorNavigation = function() {

				var $links = $content.find("a[href*=\\#]");

				$links.each( function() {
					var $link 	= $(this),
						url 	= $link.attr('href'),
						hash 	= url.substring( url.indexOf('#') + 1 ),
						$el 	= $( '#' + hash ),
						speed 	= ( 'undefined' !== typeof ee.Offcanvas.elementSettings.anchor_navigation_speed.size ) ? ee.Offcanvas.elementSettings.anchor_navigation_speed.size : 0;

					if ( ! $el.length )
						return;

					var top = $el.offset().top + $wrapper.scrollTop();

					$link.on( 'click', function( e ) {
						e.preventDefault();
						
						$wrapper.animate({ scrollTop: top }, speed, function(){
							if( 'yes' === ee.Offcanvas.elementSettings.anchor_navigation_close ) {
								offcanvas.controller.close();
							}
						});
					});
				});

			};

			ee.Offcanvas.setOverflows = function() {
				// Get current scroll
				var _scroll = $window.scrollTop();

				$('html').css( { 'height' : '100%' } );

				$wrapper.on( 'scroll', function() {
					$overlay.css({
						'top' : $wrapper.scrollTop(),
					});
				});

				$wrapper.animate({ scrollTop : _scroll }, 0 );
			};

			ee.Offcanvas.removeOverflows = function() {
				var _scroll = $wrapper.scrollTop();

				$wrapper.animate({ scrollTop : 0 }, 0 );

				$html.css( { 'height' : 'auto' } );

				$('html, body').animate({ scrollTop : _scroll }, 0 );
			};

			ee.Offcanvas.getClickedTriggerId = function( $trigger ) {
				return $trigger.closest( '.elementor-element' ).data('id');
			};

			ee.Offcanvas.init = function() {

				ee.Offcanvas.setTriggers();

				if ( offcanvas.initialized )
					offcanvas.controller.close();

				if ( $body.is(':not(.ee-offcanvas)') )
					$body.addClass( 'ee-offcanvas' );

				ee.Offcanvas.prepare();

				offcanvas.init();
				
				ee.Offcanvas.events();

				if ( 'yes' === ee.Offcanvas.elementSettings.anchor_navigation ) {
					ee.Offcanvas.anchorNavigation();
				}

				if ( 'yes' === ee.Offcanvas.elementSettings.editor_open ) {
					offcanvas.controller.open( slidebarId );

					// Add widget specific body class
					$body.addClass( 'ee-offcanvas--id-' + slidebarId );

					// Add active class to trigger
					$trigger.addClass( 'ee--is-active' );
				}
			};

			ee.Offcanvas.init();
		},

		////////////////////////////////////////////
		// Popup 								////
		////////////////////////////////////////////

		Popup : function( $scope, $ ) {

			ee.Popup.elementSettings 	= ee.getElementSettings( $scope );

			var popupOpened 		= false,
				scopeId 			= $scope.data('id'),
				$trigger 			= $scope.find( '.ee-popup__trigger' ),
				storageID 			= 'ee_PopupShown_' + scopeId,
				$window 			= ee.getWindow(),
				$html 				= elementorFrontend.isEditMode() ? $window.find('html') : $('html'),
				popupPersist 		= ee.Popup.elementSettings.popup_persist,
				isAdmin 			= 'undefined' !== typeof ee.Popup.elementSettings.popup_open_admin && 'yes' === ee.Popup.elementSettings.popup_open_admin,
				$closeButton 		= 'default' === ee.Popup.elementSettings.popup_close_button_position ? $scope.find( '.ee-popup__footer__button' ) : $scope.find( ee.Popup.elementSettings.popup_close_button_selector ),

				popupVAlignClass 	= 'mfp-popup--valign-' + ee.Popup.elementSettings.popup_valign,
				closeHAlignClass 	= 'mfp-close--halign-' + ee.Popup.elementSettings.popup_close_halign,
				closeVAlignClass 	= 'mfp-close--valign-' + ee.Popup.elementSettings.popup_close_valign,
				noOverlayClass 	 	= 'yes' === ee.Popup.elementSettings.popup_no_overlay ? 'ee-mfp-popup--no-overlay' : 'ee-mfp-popup--overlay',

				popupArgs 			= {
					autoFocusLast 		: false,
					mainClass 			: 'ee-mfp-popup ee-mfp-popup-' + scopeId + ' ' + noOverlayClass + ' ' + popupVAlignClass + ' ' + ee.Popup.elementSettings.popup_animation,
					type 				: 'inline',
					disableOn 			: ee.Popup.elementSettings.popup_disable_on,
					fixedContentPos		: 'yes' === ee.Popup.elementSettings.popup_fixed || 'yes' === ee.Popup.elementSettings.popup_prevent_scroll,
					preloader			: 'yes' === ee.Popup.elementSettings.popup_preloader,
					closeOnContentClick : 'yes' === ee.Popup.elementSettings.popup_close_on_content,
					closeOnBgClick 		: 'yes' === ee.Popup.elementSettings.popup_close_on_bg,
					enableEscapeKey 	: 'yes' === ee.Popup.elementSettings.popup_close_on_escape,
					closeBtnInside 		: 'inside' === ee.Popup.elementSettings.popup_close_position,
					showCloseBtn 		: '' !== ee.Popup.elementSettings.popup_close_position,
					focus 				: '.no-focus',
					closeMarkup 		: '<button title="%title%" type="button" class="ee-popup__close mfp-close ' + closeHAlignClass + ' ' + closeVAlignClass + ' eicon-close"></button>',
					callbacks			: {
						open : function() {
							popupOpened = true;

							if ( 'yes' !== ee.Popup.elementSettings.popup_prevent_scroll ) {
								$html.css({ 'overflow' : '' });
							}
						},
						beforeOpen : function() {},
					},
				};

			ee.Popup.init = function() {

				if ( $scope.is(':not(:visible)') )
					return;

				if ( ! elementorFrontend.isEditMode() && '' !== ee.Popup.elementSettings.popup_animation ) {
					popupArgs.removalDelay = 500;
				}

				if ( $closeButton.length ) {
					$closeButton.on( 'click', function( e ) {
						e.preventDefault();
						e.stopPropagation();
						$trigger.magnificPopup( 'close' );
					});
				}

				if ( 'iframe' === ee.Popup.elementSettings.popup_type ) {
					popupArgs.type = 'iframe';

					if ( '' !== ee.Popup.elementSettings.popup_animation ) {
						popupArgs.callbacks.beforeOpen = function() {
							// just a hack that adds mfp-anim class to markup 
							this.st.iframe.markup = this.st.iframe.markup.replace('class="mfp-iframe"', 'class="mfp-iframe mfp-with-anim"');
						};
					}
				}

				if ( 'image' === ee.Popup.elementSettings.popup_type ) {
					popupArgs.type = 'image';
					popupArgs.image = {
						verticalFit : 'yes' === ee.Popup.elementSettings.popup_vertical_fit
					};

					if ( '' !== ee.Popup.elementSettings.popup_animation ) {
						popupArgs.callbacks.beforeOpen = function() {
							// just a hack that adds mfp-anim class to markup 
							this.st.image.markup = this.st.image.markup.replace('mfp-figure', 'mfp-figure mfp-with-anim');
						};
					}
				}

				if ( elementorFrontend.isEditMode() ) {
					$trigger.magnificPopup( 'close' ).magnificPopup( popupArgs );

					if ( 'yes' === ee.Popup.elementSettings.popup_open )
						$trigger.magnificPopup( 'open' );
				} else {

					$trigger.magnificPopup( popupArgs );
					ee.Popup.behaviour();
				}
			};

			ee.Popup.behaviour = function() {

				switch ( ee.Popup.elementSettings.popup_trigger ) {

					case 'click':
						ee.Popup.behaviourClick();
						break;

					case 'instant':
						ee.Popup.behaviourInstant();
						break;

					case 'scroll':
						ee.Popup.behaviourScroll();
						break;

					case 'intent':
						ee.Popup.behaviourIntent();
						break;

					default:
						console.log( 'No popup trigger selected' );
				}
			};

			ee.Popup.behaviourClick = function() {

				if ( 'text' !== ee.Popup.elementSettings.popup_click_target ) {
					var $custom_trigger = null,
						_selector 		= null,
						$selector 		= null;

					if ( 'id' === ee.Popup.elementSettings.popup_click_target ) {
						_selector = '#' + $trigger.data('trigger-id');
					} else if ( 'class' === ee.Popup.elementSettings.popup_click_target ) {
						_selector = '.' + $trigger.data('trigger-class');
					}

					$selector = $(document).find( _selector );

					if ( $selector ) $custom_trigger = $selector;

					if ( $custom_trigger.length ) {
						$custom_trigger.on( 'click', function( e ) {
							e.preventDefault();
							e.stopPropagation();
							$trigger.magnificPopup( 'open' );
						});
					}
				}
			};

			ee.Popup.behaviourInstant = function() {
				setTimeout( ee.Popup.open, ee.Popup.elementSettings.popup_delay );
			};

			ee.Popup.behaviourScroll = function() {
				var doc = document.documentElement,
					limit;

				if ( 'amount' === ee.Popup.elementSettings.popup_scroll_type ) {
					limit = ee.Popup.elementSettings.popup_scroll_amount;
				} else if ( 'element' === ee.Popup.elementSettings.popup_scroll_type ) {
					var scrollElement = $( '#' + ee.Popup.elementSettings.popup_scroll_element );

					if ( scrollElement.length ) {
						limit = scrollElement.offset().top;
					}
				}

				$window.on( 'scroll', function() {
					var scrollTop = ( window.pageYOffset || doc.scrollTop )  - ( doc.clientTop || 0 );
					if ( scrollTop >= limit && ! popupOpened ) ee.Popup.open();
				});
			};

			ee.Popup.behaviourIntent = function() {
				var exitIntentArgs = {};

				if ( ee.Popup.elementSettings.popup_intent_sensitivity )
					exitIntentArgs.sensitivity = ee.Popup.elementSettings.popup_intent_sensitivity.size;
				
				$.exitIntent( 'enable', exitIntentArgs );

				$(document).bind( 'exitintent', function () {
					if ( ! popupOpened ) ee.Popup.open();
				});
			};

			ee.Popup.open = function() {
				var now = new Date(),
					lastDatePopupShowed, canOpen;

				if ( ! popupPersist && ! isAdmin ) {
					if ( localStorage.getItem( storageID ) !== null ) {
						lastDatePopupShowed = new Date(parseInt(localStorage.getItem( storageID )));
					}

					if ( ( ( now - lastDatePopupShowed ) >= ( ee.Popup.elementSettings.popup_days * 86400000 ) ) || ! lastDatePopupShowed ) {
						$trigger.magnificPopup( 'open' );

						localStorage.setItem( storageID, now );
					}
				} else {
					localStorage.removeItem( storageID );
					$trigger.magnificPopup( 'open' );
				}
			}

			ee.Popup.init();
		},

		////////////////////////////////////////////
		// Age Gate 							////
		////////////////////////////////////////////

		AgeGate : function( $scope, $ ) {

			ee.AgeGate.elementSettings 	= ee.getElementSettings( $scope );

			var scopeId 			= $scope.data('id'),
				$trigger 			= $scope.find( '.ee-age-gate__trigger' ),
				$form 				= $scope.find( '.ee-form--age-gate' ),
				$header 			= $scope.find( '.ee-age-gate__header' ),
				$popup 				= $scope.find( '.ee-popup__content' ),
				$age 				= $scope.find( '[name=ee-age-gate-age]'),
				$denied 			= $scope.find( '.ee-notification--error' ),
				storageID 			= 'ee_AgeGate',
				isAdmin 			= 'undefined' !== typeof ee.AgeGate.elementSettings.popup_open_admin && 'yes' === ee.AgeGate.elementSettings.popup_open_admin,
				popupVAlignClass 	= 'mfp-popup--valign-' + ee.AgeGate.elementSettings.popup_valign,
				popupArgs 			= {
					mainClass 			: 'ee-mfp-popup ee-mfp-popup-' + scopeId + ' ' + popupVAlignClass + ' ' + ee.AgeGate.elementSettings.popup_animation,
					type 				: 'inline',
					showCloseBtn 		: false,
					modal 				: ( elementorFrontend.isEditMode() ) ? false : true,
					focus 				: ( elementorFrontend.isEditMode() ) ? '.no-focus' : '.ee-age-gate__form__age',
					autoFocusLast 		: false,
				};

			ee.AgeGate.init = function() {
				if ( elementorFrontend.isEditMode() ) {

					popupArgs.closeOnBgClick = true;
					popupArgs.enableEscapeKey = true;

					$trigger.magnificPopup( 'close' ).magnificPopup( popupArgs );

					if ( 'yes' === ee.AgeGate.elementSettings.popup_open )
						$trigger.magnificPopup( 'open' );

				} else {

					$trigger.magnificPopup( popupArgs );

					if ( isAdmin )
						localStorage.removeItem( storageID );

					if ( ! localStorage.getItem( storageID ) || localStorage.getItem( storageID ) < ee.AgeGate.elementSettings.age ) {

						if ( '' !== ee.AgeGate.elementSettings.popup_animation ) { popupArgs.removalDelay = 500; }
						
						$trigger.magnificPopup( 'open' );

						$form.on('submit', ee.AgeGate.onSubmit );
					}
				}
			};

			ee.AgeGate.onSubmit = function( e ) {
				e.preventDefault();

				var age = $age.val();
				
				if ( age >= Math.abs( parseFloat( ee.AgeGate.elementSettings.age ) ) ) {
					$trigger.magnificPopup( 'close' );

					if ( ! isAdmin )
						localStorage.setItem( storageID, age );
				} else {
					$denied.show();
					if ( ee.AgeGate.elementSettings.hide_form_on_denied ) {
						$form.remove();
						$header.remove();
					} else if ( ee.AgeGate.elementSettings.clear_form_on_denied ) {
						$form[0].reset();
					}
				}
			};

			ee.AgeGate.init();
		},

		////////////////////////////////////////////
		// Toggle Element 						////
		////////////////////////////////////////////

		ToggleElement : function( $scope, $ ) {

			ee.ToggleElement.elementSettings 	= ee.getElementSettings( $scope );

			var $wrapper 			= $scope.find( '.ee-toggle-element' ),
				toggleElementArgs 	= {
					// onAfterToggle : ee.doAction( 'ee-toggle-element/on_after_toggle' ),
					active : ee.ToggleElement.elementSettings.toggle_active_index,
				};

			ee.ToggleElement.init = function() {
				if ( '' !== ee.ToggleElement.elementSettings.indicator_color ) {
					toggleElementArgs.indicatorColor = ee.ToggleElement.elementSettings.indicator_color;
				}

				if ( ee.ToggleElement.elementSettings.indicator_speed.size ) {
					toggleElementArgs.speed = ee.ToggleElement.elementSettings.indicator_speed.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					toggleElementArgs.watchControls = true;
				}

				$wrapper.toggleElement( toggleElementArgs );
			};

			ee.ToggleElement.init();
		},

		////////////////////////////////////////////
		// Switcher 							////
		////////////////////////////////////////////

		Switcher : function( $scope, $ ) {

			ee.Switcher.elementSettings 	= ee.getElementSettings( $scope );

			var $media 			= $scope.find( '.ee-switcher__media-wrapper' ),
				$content 		= $scope.find( '.ee-switcher__titles' ),
				switcherArgs 	= {
					mediaEffect 		: ee.Switcher.elementSettings.effect_media,
					contentEffect 		: ee.Switcher.elementSettings.effect_title,
					entranceAnimation 	: 'yes' === ee.Switcher.elementSettings.effect_entrance,
					contentEffectZoom 	: 'yes' === ee.Switcher.elementSettings.effect_media_zoom,
					contentStagger		: 'yes' === ee.Switcher.elementSettings.effect_title_stagger,
					autoplay 			: 'yes' === ee.Switcher.elementSettings.autoplay,
					loop 				: 'yes' === ee.Switcher.elementSettings.loop,
					cancelOnInteraction : 'yes' === ee.Switcher.elementSettings.autoplay_cancel,
					changeBackground 	: 'yes' === ee.Switcher.elementSettings.background_switcher,
				},
				mediaParallaxArgs = {
					type 	: 'mouse',
					mouse 	: {
						relative : 'viewport',
						axis 	 : ee.Switcher.elementSettings.parallax_pan_axis,
					},
					speed 	: {
						desktop: 0.20
					},
				},
				titleParallaxArgs = {
					type 	: 'mouse',
					invert 	: true,
					mouse 	: {
						relative : 'viewport',
						axis 	 : ee.Switcher.elementSettings.parallax_pan_axis,
					},
					speed 	: {
						desktop: 0.20
					},
				};

			ee.Switcher.maybeDestroy = function() {
				if ( $scope.data( 'eeSwitcher' ) ) {
					$scope.data( 'eeSwitcher' ).destroy();
				}

				if ( $media.data( 'parallaxElement' ) ) {
					$media.data( 'parallaxElement' ).destroy();
				}

				if ( $content.data( 'parallaxElement' ) ) {
					$content.data( 'parallaxElement' ).destroy();
				}
			};

			ee.Switcher.init = function() {

				if ( elementorFrontend.isEditMode() ) {
					switcherArgs.scope 			= window.elementor.$previewContents;
					mediaParallaxArgs.scope 	= window.elementor.$previewContents;
					mediaParallaxArgs.scope 	= window.elementor.$previewContents;

					if ( 'yes' === ee.Switcher.elementSettings.autoplay && 'yes' !== ee.Switcher.elementSettings.autoplay_preview ) {
						switcherArgs.autoplay = false;
					}

					if ( 'yes' === ee.Switcher.elementSettings.effect_entrance && 'yes' !== ee.Switcher.elementSettings.effect_entrance_preview ) {
						switcherArgs.entranceAnimation = false;
					}
				}

				if ( 'yes' === ee.Switcher.elementSettings.autoplay ) {
					if ( ee.Switcher.elementSettings.duration.size ) {
						switcherArgs.duration = ee.Switcher.elementSettings.duration.size;
					}
				}

				if ( ee.Switcher.elementSettings.speed.size ) {
					switcherArgs.speed = ee.Switcher.elementSettings.speed.size;
				}

				if ( 'yes' === ee.Switcher.elementSettings.parallax_enable ) {
					if ( 'undefined' !== typeof ee.Switcher.elementSettings.parallax_amount && '' !== ee.Switcher.elementSettings.parallax_amount.size ) {
						mediaParallaxArgs.speed.desktop = ee.Switcher.elementSettings.parallax_amount.size;
						titleParallaxArgs.speed.desktop = ee.Switcher.elementSettings.parallax_amount.size;
					}

					$media.parallaxElement( mediaParallaxArgs );
					$content.parallaxElement( titleParallaxArgs );
				}

				switch ( ee.Switcher.elementSettings.background_switcher_element ) {
					case 'widget':
						switcherArgs.background = $scope.find('.elementor-widget-container');
						break;
					case 'section':
						switcherArgs.background = $scope.parents('.elementor-section').first();
						break;
					default:
						switcherArgs.background = elementorFrontend.isEditMode() ? switcherArgs.scope.find('body') : $('body');
				}

				$scope.eeSwitcher( switcherArgs );

				ee.onElementRemove( $scope, function() {
					ee.Switcher.maybeDestroy();
				});
			};

			ee.Switcher.maybeDestroy();
			ee.Switcher.init();
		},

		////////////////////////////////////////////
		// InlineSVG 							////
		////////////////////////////////////////////

		InlineSvg : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope ),
				$wrapper 	= $scope.find( '.ee-inline-svg' ),
				url 		= '' !== elementSettings.svg.url ? elementSettings.svg.url : $wrapper.data('url');

			ee.InlineSvg.init = function() {

				// Initially we have no value so lets ignore this case
				if ( ! url )
					return;

				// Check the extension means we're expecting an svg file type or quit
				if ( url.split('.').pop().toLowerCase() !== 'svg' ) {
					alert( "Please select a SVG file format." );
					return;
				}

				// Get the file
				jQuery.get( url, ee.InlineSvg.callback );
			};

			ee.InlineSvg.callback = function( data ) {
				// And append the the first node to our wrapper
				$wrapper.html( $( data ).find('svg') );

				var $svg = $wrapper.find( 'svg' ),
				
					svgTitle 		= $svg.find( 'title' ),
					svgDesc 		= $svg.find( 'desc' ),
					svgFills 		= $svg.find( '*[fill]' ),
					svgShapes 		= $svg.find( 'circle, ellipse, polygon, rect, path, line, polyline' ),
					svgNonFills 	= $svg.find( 'circle, ellipse, polygon, rect, path' ).filter(':not([fill])'),
					svgStrokes 		= $svg.find( '*[stroke]' ),
					svgNonStrokes 	= $svg.find( 'line, polyline' ).filter(':not([fill])');

				// Remove unnecessary tags
				svgTitle.remove();
				svgDesc.remove();

				// Remove inline CSS
				if ( 'yes' === elementSettings.remove_inline_css ) {
					// Convert css styles to attributes
					svgShapes.each( function() {
						
						var stroke = $(this).css( 'stroke' ),
							strokeWidth = $(this).css( 'stroke-width' ),
							strokeLinecap = $(this).css( 'stroke-linecap' ),
							strokeDasharray = $(this).css( 'stroke-dasharray' ),
							strokeMiterlimit = $(this).css( 'stroke-miterlimit' ),
							fill = $(this).css( 'fill' );

						// Fix IE silly interpretation of computed stroke width
						strokeWidth = ( strokeWidth > 0 && strokeWidth < 1 ) ? 1 : strokeWidth;

						$(this).attr( 'stroke', stroke );
						$(this).attr( 'stroke-width', strokeWidth );
						$(this).attr( 'stroke-linecap', strokeLinecap );
						$(this).attr( 'stroke-dasharray', strokeDasharray );
						$(this).attr( 'stroke-miterlimit', strokeMiterlimit );
						$(this).attr( 'fill', fill );

					});

					$svg.find( 'style' ).remove();
				}

				// Color override
				if ( 'yes' === elementSettings.override_colors ) {
					svgShapes.filter('[fill]:not([fill="none"])').attr( 'fill', 'currentColor' );
					svgShapes.filter('[stroke]:not([stroke="none"])').attr( 'stroke', 'currentColor' );

					// Remove comments from markup
					// $svg.contents().each( function() {
					//     if ( this.nodeType === Node.COMMENT_NODE ) { $(this).remove(); }
					// });
				}

				if ( 'yes' !== elementSettings.maintain_ratio ) {
					$svg[0].setAttribute( 'preserveAspectRatio', 'none' );
				}

				if ( 'yes' === elementSettings.sizing ) {
					$svg[0].removeAttribute( 'width' );
					$svg[0].removeAttribute( 'height' );
				}
			}

			ee.InlineSvg.init();
		},

		////////////////////////////////////////////
		// Posts.Classic 						////
		////////////////////////////////////////////

		PostsClassic : function( $scope, $ ) {

			if ( elementorFrontend.isEditMode() )
				return;

			ee.PostsClassic.elementSettings 	= ee.getElementSettings( $scope );

			var scopeId 		= $scope.data('id'),
				$loop 			= $scope.find( '.ee-loop' ),
				$filters 		= $scope.find('.ee-filters'),
				$triggers 		= $filters.find( '[data-filter]' ),

				elementClass 	= '.elementor-element-' + scopeId,
				isLayout 		= 'default' !== ee.PostsClassic.elementSettings.classic_layout && 1 < ee.PostsClassic.elementSettings.columns,
				isInfinite 		= 'yes' === ee.PostsClassic.elementSettings.classic_infinite_scroll,
				isFiltered 		= 'yes' === ee.PostsClassic.elementSettings.classic_filters,
				hasHistory 		= 'yes' === ee.PostsClassic.elementSettings.classic_infinite_scroll_history ? 'replace' : false,
				isotopeInstance = null;

			if ( ! $scope.find('.ee-pagination').length )
					isInfinite = false;

				var infiniteScrollArgs = {
						history 	: hasHistory,
						path 		: elementClass + ' .ee-pagination__next',
						append 		: elementClass + ' .ee-loop__item',
						hideNav 	: elementClass + ' .ee-pagination',
						status 		: elementClass + ' .ee-load-status',
					},

					isotopeArgs = {
						isOriginLeft 	: elementorFrontend.config.is_rtl ? false : true,
						itemSelector	: elementClass + ' .ee-loop__item',
						layoutMode 		: isLayout ? ee.PostsClassic.elementSettings.classic_layout : 'masonry',
						masonry			: isLayout ? { columnWidth: elementClass + ' .ee-grid__item--sizer' } : '',
						percentPosition : true,
						hiddenStyle 	: {
							opacity 	: 0,
						},
					},

					filteryArgs = {
						wrapper 			: $loop,
						filterables 		: '.ee-loop__item',
						activeFilterClass 	: 'ee--active',
						notFound 			: $scope.find('.ee-grid__notice--not-found'),
					};

			ee.PostsClassic.init = function() {

				ee.PostsClassic.infinitescroll();

				if ( isFiltered && $triggers.length )
					ee.PostsClassic.filters();
			};

			ee.PostsClassic.infinitescroll = function() {
				if ( isInfinite && 'yes' === ee.PostsClassic.elementSettings.classic_infinite_scroll_button ) {

					infiniteScrollArgs.loadOnScroll 	= false;
					infiniteScrollArgs.scrollThreshold 	= false;
					infiniteScrollArgs.button 			= '.ee-load-button__trigger--' + scopeId;

					$loop.on( 'request.infiniteScroll', function( event, path ) {
						$scope.find( '.ee-load-button' ).hide();
					});

					$loop.on( 'load.infiniteScroll', function( event, response, path ) {
						$scope.find( '.ee-load-button' ).show();
					});

				}

				if ( isInfinite && ! isLayout ) {

					$loop.infiniteScroll( infiniteScrollArgs );

				} else if ( isLayout ) {

					$loop.imagesLoaded( function() {

						var $isotope = $loop.isotope( isotopeArgs );
							isotopeInstance = $loop.data( 'isotope' );

						if ( isInfinite ) {

							var $filters 	= $scope.find('.ee-filters');

							if ( ! isFiltered || ! $triggers.length ) {
								infiniteScrollArgs.outlayer = isotopeInstance;
							}

							$isotope.infiniteScroll( infiniteScrollArgs );
							// $isotope.masonry();
						}

					});

				}
			};

			ee.PostsClassic.filters = function() {
				if ( isLayout ) {

					if ( isInfinite ) {

						$loop.on( 'load.infiniteScroll', function( event, response, path ) {
							var $items = $( response ).find( elementClass + ' .ee-loop__item' );
							
							$items.imagesLoaded( function() {
								$loop.append( $items );

								if ( isotopeInstance ) {
									$loop.isotope( 'insert', $items );
									// $loop.masonry();
								}
							});
						});

					}

					// Filter by default
					var $default_trigger = $triggers.filter('.ee--active');

					if ( $default_trigger.length ) {
						var default_filter = $default_trigger.data('filter');
						$loop.isotope(
							$.extend({}, isotopeArgs, {
								filter: default_filter,
							} )
						);
					}

					// Filter by click
					$triggers.on( 'click', function() {
						var _filter = $(this).data('filter');

						$loop.isotope(
							$.extend({}, isotopeArgs, {
								filter 	: _filter,
							} )
						);

						$triggers.removeClass('ee--active');
						$(this).addClass('ee--active');
					});

				} else {

					$filters.filtery( filteryArgs );

					var filteryInstance = $filters.data( 'filtery' );

					if ( isInfinite ) {
						$loop.on( 'load.infiniteScroll', function( event, response, path ) {
							var $items = $( response ).find( elementClass + ' .ee-loop__item' );
							
							$items.imagesLoaded( function() {
								$loop.append( $items );
								filteryInstance.update();
							});
						});
					}

				}
			};

			ee.PostsClassic.init();
		},

		////////////////////////////////////////////
		// PostsCarousel 						////
		////////////////////////////////////////////

		PostsCarousel : function( $scope, $ ) {

			ee.PostsCarousel.elementSettings 	= ee.getElementSettings( $scope );

			var scopeId 		= $scope.data('id'),
				$swiper 		= $scope.find( '.ee-swiper__container' ),
				$slides 		= $swiper.find( '.ee-grid__item' ),

				pagination_pos 	= ( 'outside' === ee.PostsCarousel.elementSettings.carousel_pagination_position ) ? 'outside' : 'inside',

				breakpoints 	= {
					tablet : 1024,
					mobile : 767,
				},
				swiperInstance 	= $swiper.data( 'swiper' ),
				swiperArgs 		= {
					direction 				: ee.PostsCarousel.elementSettings.carousel_direction,
					slidesPerView 			: 3,
					slidesPerGroup			: 3,
					slidesPerColumn 		: 1,
					autoplay 				: false,
					autoHeight				: ee.PostsCarousel.elementSettings.carousel_autoheight,
					spaceBetween 			: 0,
					pagination 				: {},
					navigation 				: {},
					grabCursor 				: true,
					effect 					: ee.PostsCarousel.elementSettings.carousel_effect,
					observer 				: true,
					observeParents			: true,
					breakpoints: {
						1024 : {
							slidesPerView 	: 2,
							slidesPerGroup	: 2,
							spaceBetween 	: 12,
							slidesPerColumn : 1,
						},
						767 : {
							slidesPerView 	: 1,
							slidesPerGroup	: 1,
							spaceBetween 	: 12,
							slidesPerColumn : 1,
						},
					}
				};

			ee.PostsCarousel.destroy = function() {
				swiperInstance.destroy( true, true );
			};

			ee.PostsCarousel.init = function() {
				if ( swiperInstance ) {
					ee.PostsCarousel.destroy();
					return;
				}

				// Number of columns

				if ( ee.PostsCarousel.elementSettings.columns ) {
					swiperArgs.slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.columns || 3 );
				} else {
					if ( ee.PostsCarousel.elementSettings.carousel_slides_per_view ) {
						swiperArgs.slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_per_view || 3 );
					}
				}

				if ( ee.PostsCarousel.elementSettings.columns_tablet ) {
					swiperArgs.breakpoints[ breakpoints.tablet ].slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.columns_tablet || 2 );
				} else {
					if ( ee.PostsCarousel.elementSettings.carousel_slides_per_view_tablet ) {
						swiperArgs.breakpoints[ breakpoints.tablet ].slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_per_view_tablet || 2 );
					}
				}

				if ( ee.PostsCarousel.elementSettings.columns_mobile ) {
					swiperArgs.breakpoints[ breakpoints.mobile ].slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.columns_mobile || 1 );
				} else {
					if ( ee.PostsCarousel.elementSettings.carousel_slides_per_view_mobile ) {
						swiperArgs.breakpoints[ breakpoints.mobile ].slidesPerView = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_per_view_mobile || 1 );
					}
				}

				// Number of slides to scroll

				if ( ee.PostsCarousel.elementSettings.carousel_slides_to_scroll ) {
					swiperArgs.slidesPerGroup = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_to_scroll || 3 );
				}

				if ( ee.PostsCarousel.elementSettings.carousel_slides_to_scroll_tablet ) {
					swiperArgs.breakpoints[ breakpoints.mobile ].slidesPerGroup = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_to_scroll_tablet || 2 );
				}

				if ( ee.PostsCarousel.elementSettings.carousel_slides_to_scroll_mobile ) {
					swiperArgs.breakpoints[ breakpoints.mobile ].slidesPerGroup = Math.min( $slides.length, +ee.PostsCarousel.elementSettings.carousel_slides_to_scroll_mobile || 1 );
				}

				// Rows

				if ( ee.PostsCarousel.elementSettings.carousel_slides_per_column ) {
					swiperArgs.slidesPerColumn = ee.PostsCarousel.elementSettings.carousel_slides_per_column;
				}

				if ( ee.PostsCarousel.elementSettings.carousel_slides_per_column_tablet ) {
					swiperArgs.breakpoints[ breakpoints.tablet ].slidesPerColumn = ee.PostsCarousel.elementSettings.carousel_slides_per_column_tablet;
				}

				if ( ee.PostsCarousel.elementSettings.carousel_slides_per_column_mobile ) {
					swiperArgs.breakpoints[ breakpoints.mobile ].slidesPerColumn = ee.PostsCarousel.elementSettings.carousel_slides_per_column_mobile;
				}

				// Column spacing

				if ( ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing.size && 1 < ee.PostsCarousel.elementSettings.carousel_slides_per_view ) {
					swiperArgs.spaceBetween = ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing.size;
				}

				if ( ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing_tablet.size && 1 < ee.PostsCarousel.elementSettings.carousel_slides_per_view_tablet ) {
					swiperArgs.breakpoints[ breakpoints.tablet ].spaceBetween = ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing_tablet.size;
				}

				if ( ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing_mobile.size && 1 < ee.PostsCarousel.elementSettings.carousel_slides_per_view_mobile ) {
					swiperArgs.breakpoints[ breakpoints.mobile ].spaceBetween = ee.PostsCarousel.elementSettings.carousel_grid_columns_spacing_mobile.size;
				}

				if ( 'vertical' === ee.PostsCarousel.elementSettings.carousel_direction ) {
					swiperArgs.slidesPerColumnFill = 'row';
				}

				// Arrows and pagination

				if ( 'on' === ee.PostsCarousel.elementSettings.carousel_arrows ) {
					swiperArgs.navigation.disabledClass = 'ee-swiper__button--disabled';
					swiperArgs.navigation.prevEl = '.ee-swiper__button--prev-' + scopeId;
					swiperArgs.navigation.nextEl = '.ee-swiper__button--next-' + scopeId;
				}

				if ( 'on' === ee.PostsCarousel.elementSettings.carousel_pagination ) {

					swiperArgs.pagination.el = '.ee-swiper__pagination-' + scopeId + '.ee-swiper__pagination--' + pagination_pos;
					swiperArgs.pagination.type = ee.PostsCarousel.elementSettings.carousel_pagination_type;
					swiperArgs.pagination.clickable = true;

					if ( 'yes' === ee.PostsCarousel.elementSettings.carousel_pagination_clickable ) {
						swiperArgs.paginationClickable = true;
					}
				}

				// Loop

				if ( 'yes' === ee.PostsCarousel.elementSettings.carousel_loop ) {
					swiperArgs.loop = true;
					swiperArgs.loopedSlides = $slides.length;
				}

				// Autoplay

				if ( ! elementorFrontend.isEditMode() && 'yes' === ee.PostsCarousel.elementSettings.carousel_autoplay ) {
					swiperArgs.autoplay = {};
					swiperArgs.autoplay.delay = ee.PostsCarousel.elementSettings.carousel_autoplay_speed;
					swiperArgs.autoplay.disableOnInteraction = !! ee.PostsCarousel.elementSettings.carousel_pause_on_interaction;
				}

				// Speed 

				if ( ee.PostsCarousel.elementSettings.carousel_speed.size ) {
					swiperArgs.speed = ee.PostsCarousel.elementSettings.carousel_speed.size;
				}

				// Resistance 

				if ( ee.PostsCarousel.elementSettings.carousel_resistance_ratio.size ) {
					swiperArgs.resistanceRatio = 1 - ee.PostsCarousel.elementSettings.carousel_resistance_ratio.size;
				}

				if ( 'yes' === ee.PostsCarousel.elementSettings.carousel_free_mode ) {
					swiperArgs.freeMode = true;
					swiperArgs.freeModeMomentum = false;
					swiperArgs.freeModeSticky = false;

					if ( 'yes' === ee.PostsCarousel.elementSettings.carousel_free_mode_momentum ) {
						swiperArgs.freeModeMomentum = true;
					}

					if ( 'yes' === ee.PostsCarousel.elementSettings.carousel_free_mode_sticky ) {
						swiperArgs.freeModeSticky = true;
					}
				}

				swiperInstance = new Swiper( $swiper, swiperArgs );
			};

			ee.PostsCarousel.init();

			ee.onElementRemove( $scope, function() {
				ee.PostsCarousel.destroy();
			});
		},

		////////////////////////////////////////////
		// Sticky 								////
		////////////////////////////////////////////

		Sticky : function( $scope, $ ) {

			ee.Sticky.elementSettings 	= ee.getElementSettings( $scope );
			ee.Sticky.elementType 		= ee.getElementType( $scope );

			var instance = new eeSticky( $scope, ee.Sticky.elementSettings );
			instance.init();
		},

		////////////////////////////////////////////
		// Table 								////
		////////////////////////////////////////////

		Table : function( $scope, $ ) {

			ee.Table.elementSettings 	= ee.getElementSettings( $scope );

			var $table 				= $scope.find('table.ee-table'),
				sortableInstance 	= $table.data('tablesorter');

			ee.Table.init = function() {
				if ( 'yes' == ee.Table.elementSettings.sortable ) {
					$table.tablesorter({
						cssHeader 	: 'ee-table__sort',
						cssAsc 		: 'ee-table__sort--up',
						cssDesc 	: 'ee-table__sort--down',
					});
				} else {
					$table.removeData('tablesorter');
				}
			};

			ee.Table.init();
		},

		////////////////////////////////////////////
		// ParallaxBackground 					////
		////////////////////////////////////////////

		ParallaxBackground : function( $scope, $ ) {

			if ( 'section' !== ee.getElementType( $scope ) )
				return;

			ee.ParallaxBackground.elementSettings 	= ee.getElementSettings( $scope );

			var instance = $scope.data( 'parallaxBackground' ),
				parallaxBackgroundArgs = {
					parallaxResizeWatch : $scope.find('.elementor-container'),
				};

			ee.ParallaxBackground.maybeDestroy = function() {
				if ( instance )
					instance.destroy();
			};

			ee.ParallaxBackground.init = function() {

				// Reinit parallax background
				ee.ParallaxBackground.maybeDestroy();

				// Bail if not enabled
				if ( ! ee.ParallaxBackground.elementSettings.parallax_background_enable || '' === ee.ParallaxBackground.elementSettings.parallax_background_enable )
					return;

				// Check for background images for the section
				if ( ! elementorFrontend.isEditMode() && ( $scope.css('background-image') === '' || $scope.css('background-image') === 'none' ) )
					return;

				parallaxBackgroundArgs.parallaxBgImage = ee.ParallaxBackground.elementSettings.background_image['url'];

				if ( "" !== ee.ParallaxBackground.elementSettings.parallax_background_speed.size ) {
					parallaxBackgroundArgs.parallaxSpeed = ee.ParallaxBackground.elementSettings.parallax_background_speed.size;
				}

				if ( "" !== ee.ParallaxBackground.elementSettings.parallax_background_speed_tablet.size ) {
					parallaxBackgroundArgs.parallaxSpeedTablet = ee.ParallaxBackground.elementSettings.parallax_background_speed_tablet.size;
				}

				if ( "" !== ee.ParallaxBackground.elementSettings.parallax_background_speed_mobile.size ) {
					parallaxBackgroundArgs.parallaxSpeedMobile = ee.ParallaxBackground.elementSettings.parallax_background_speed_mobile.size;
				}

				if ( ee.ParallaxBackground.elementSettings.parallax_background_direction ) {
					parallaxBackgroundArgs.parallaxDirection = ee.ParallaxBackground.elementSettings.parallax_background_direction;
				}

				// Editor mode
				if ( elementorFrontend.isEditMode() ) {
					parallaxBackgroundArgs.win = window.elementor.$previewContents;
				}

				$scope.parallaxBackground( parallaxBackgroundArgs );

				ee.onElementRemove( $scope, function() {
					ee.ParallaxBackground.maybeDestroy();
				});
			};

			ee.ParallaxBackground.init();
		},

		////////////////////////////////////////////
		// Unfold 								////
		////////////////////////////////////////////

		Unfold : function( $scope, $ ) {

			ee.Unfold.elementSettings 	= ee.getElementSettings( $scope );

			var $unfold 		= $scope.find('.ee-unfold'),
				$unfold_text 	= $unfold.find('.ee-button-text'),
				instance 		= $unfold.data( 'unfold' ),
				unfoldArgs		= {};

			ee.Unfold.maybeDestroy = function() {
				if ( instance )
					instance.destroy();
			};

			ee.Unfold.init = function() {

				ee.Unfold.maybeDestroy();

				if ( ee.Unfold.elementSettings.animation_unfold ) {
					unfoldArgs.animation_unfold  = ee.Unfold.elementSettings.animation_unfold;
				}

				if ( ee.Unfold.elementSettings.animation_fold ) {
					unfoldArgs.animation_fold  = ee.Unfold.elementSettings.animation_fold;
				}

				if ( ee.Unfold.elementSettings.easing_unfold ) {
					unfoldArgs.easing_unfold  = ee.Unfold.elementSettings.easing_unfold;
				}

				if ( ee.Unfold.elementSettings.easing_fold ) {
					unfoldArgs.easing_fold  = ee.Unfold.elementSettings.easing_fold;
				}

				if ( ee.Unfold.elementSettings.steps_unfold ) {
					unfoldArgs.steps_unfold  = ee.Unfold.elementSettings.steps_unfold.size;
				}

				if ( ee.Unfold.elementSettings.steps_fold ) {
					unfoldArgs.steps_fold  = ee.Unfold.elementSettings.steps_fold.size;
				}

				if ( ee.Unfold.elementSettings.slow_unfold ) {
					unfoldArgs.slow_unfold  = ee.Unfold.elementSettings.slow_unfold.size;
				}

				if ( ee.Unfold.elementSettings.slow_fold ) {
					unfoldArgs.slow_fold  = ee.Unfold.elementSettings.slow_fold.size;
				}

				if ( 'yes' === ee.Unfold.elementSettings.focus_close ) {
					unfoldArgs.focusOnClose = true;
				}

				if ( 'yes' === ee.Unfold.elementSettings.focus_open ) {
					unfoldArgs.focusOnOpen = ee.Unfold.elementSettings.focus_open;
				}

				if ( ee.Unfold.elementSettings.duration_unfold ) {
					unfoldArgs.duration_unfold  = ee.Unfold.elementSettings.duration_unfold.size;
				}

				if ( ee.Unfold.elementSettings.duration_fold ) {
					unfoldArgs.duration_fold  = ee.Unfold.elementSettings.duration_fold.size;
				}

				if ( 'lines' === ee.Unfold.elementSettings.visible_type ) {
					unfoldArgs.visible_lines  = ee.Unfold.elementSettings.visible_lines.size;
				}

				if ( ee.Unfold.elementSettings.visible_percentage ) {
					unfoldArgs.visible_percentage  = ee.Unfold.elementSettings.visible_percentage.size;
				}

				if ( '' !== $unfold_text.data('open-label') ) {
					unfoldArgs.text_closed  = $unfold_text.data('open-label');
				}

				if ( '' !== $unfold_text.data('close-label') ) {
					unfoldArgs.text_open  = $unfold_text.data('close-label');
				}

				$unfold.unfold( unfoldArgs );

				ee.onElementRemove( $scope, function() {
					ee.Unfold.maybeDestroy();
				});
			};

			ee.Unfold.init();
		},

		////////////////////////////////////////////
		// Portfolio 							////
		////////////////////////////////////////////

		Portfolio : function( $scope, $ ) {

			ee.Portfolio.elementSettings 	= ee.getElementSettings( $scope );

			if ( 'yes' !== ee.Portfolio.elementSettings.parallax_enable )
				return;

			var parallaxGalleryArgs = {
					transformItem 	: 'a.elementor-post__thumbnail__link',
					columns 		: ee.Portfolio.elementSettings.columns,
				};

			ee.Portfolio.init = function() {
				if ( 'none' !== ee.Portfolio.elementSettings.parallax_disable_on ) {
					parallaxGalleryArgs.responsive = ee.Portfolio.elementSettings.parallax_disable_on;
				}

				if ( ee.Portfolio.elementSettings.columns_tablet ) {
					parallaxGalleryArgs.columnsTablet = ee.Portfolio.elementSettings.columns_tablet;
				}

				if ( ee.Portfolio.elementSettings.columns_mobile ) {
					parallaxGalleryArgs.columnsMobile = ee.Portfolio.elementSettings.columns_mobile;
				}

				if ( ee.Portfolio.elementSettings.parallax_speed_tablet.size ) {
					parallaxGalleryArgs.speedTablet = ee.Portfolio.elementSettings.parallax_speed_tablet.size;
				}

				if ( ee.Portfolio.elementSettings.parallax_speed_mobile.size ) {
					parallaxGalleryArgs.speedMobile = ee.Portfolio.elementSettings.parallax_speed_mobile.size;
				}

				if ( ee.Portfolio.elementSettings.parallax_speed.size ) {
					parallaxGalleryArgs.speed = ee.Portfolio.elementSettings.parallax_speed.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					parallaxGalleryArgs.scope = window.elementor.$previewContents;
				}

				$scope.find('.elementor-portfolio').parallaxGallery( parallaxGalleryArgs );
			};

			ee.Portfolio.init();
		},

		////////////////////////////////////////////
		// GalleryExtra 						////
		////////////////////////////////////////////

		GalleryExtra : function( $scope, $ ) {

			ee.GalleryExtra.elementSettings 	= ee.getElementSettings( $scope );

			var $gallery = $scope.find( '.ee-gallery' ),
				parallaxGalleryArgs = {
					columns : ee.GalleryExtra.elementSettings.columns,
				};

			ee.GalleryExtra.parallax = function() {
				if ( 'none' !== ee.GalleryExtra.elementSettings.parallax_disable_on ) {
					parallaxGalleryArgs.responsive = ee.GalleryExtra.elementSettings.parallax_disable_on;
				}

				if ( ee.GalleryExtra.elementSettings.columns_tablet ) {
					parallaxGalleryArgs.columnsTablet = ee.GalleryExtra.elementSettings.columns_tablet;
				}

				if ( ee.GalleryExtra.elementSettings.columns_mobile ) {
					parallaxGalleryArgs.columnsMobile = ee.GalleryExtra.elementSettings.columns_mobile;
				}

				if ( ee.GalleryExtra.elementSettings.parallax_speed.size ) {
					parallaxGalleryArgs.speed = ee.GalleryExtra.elementSettings.parallax_speed.size;
				}

				if ( ee.GalleryExtra.elementSettings.parallax_speed_tablet.size ) {
					parallaxGalleryArgs.speedTablet = ee.GalleryExtra.elementSettings.parallax_speed_tablet.size;
				}

				if ( ee.GalleryExtra.elementSettings.parallax_speed_mobile.size ) {
					parallaxGalleryArgs.speedMobile = ee.GalleryExtra.elementSettings.parallax_speed_mobile.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					parallaxGalleryArgs.scope = window.elementor.$previewContents;
				}

				$gallery.parallaxGallery( parallaxGalleryArgs );
			};

			ee.GalleryExtra.masonry = function() {
				$gallery.imagesLoaded( function() {
					var $isotope = $gallery.isotope({
						itemSelector	: '.ee-gallery__item',
						percentPosition : true,
						hiddenStyle 	: {
							opacity 	: 0,
						},
					}),
					
					isotopeInstance = $gallery.data( 'isotope' );

					$isotope.masonry();

					$(window).on( 'resize', function() {
						$isotope.masonry();
					});
				});
			};

			ee.GalleryExtra.tilt = function() {
				$gallery.find( '.ee-gallery__tilt' ).tilt({
					maxTilt 		: ee.GalleryExtra.elementSettings.tilt_amount.size,
					scale 			: ee.GalleryExtra.elementSettings.tilt_scale.size,
					speed 			: ee.GalleryExtra.elementSettings.tilt_speed.size,
					axis 			: ee.GalleryExtra.elementSettings.tilt_axis,
					perspective 	: 1000,
				});
			};

			ee.GalleryExtra.init = function() {
				if ( 'yes' === ee.GalleryExtra.elementSettings.parallax_enable ) {
					ee.GalleryExtra.parallax();
				} else {

					if ( 'yes' === ee.GalleryExtra.elementSettings.masonry_enable && ! elementorFrontend.isEditMode() ) {
						ee.GalleryExtra.masonry();
					}
				}

				if ( 'yes' === ee.GalleryExtra.elementSettings.tilt_enable ) {
					ee.GalleryExtra.tilt();
				}
			};

			ee.GalleryExtra.init();
		},

		////////////////////////////////////////////
		// ParallaxElement 						////
		////////////////////////////////////////////

		ParallaxElement : function( $scope, $ ) {

			ee.ParallaxElement.elementSettings 	= ee.getElementSettings( $scope );

			var $element = $scope,
				instance = $scope.data( 'parallaxElement' ),
				parallaxElementArgs = {
					type 			: ee.ParallaxElement.elementSettings.parallax_element_type,
					invert 			: 'yes' === ee.ParallaxElement.elementSettings.parallax_element_invert,
					moveOutside 	: 'yes' === ee.ParallaxElement.elementSettings.parallax_off_viewport,
					scroll 			: {
						relative 	: ee.ParallaxElement.elementSettings.parallax_element_relative,
					},
					mouse 			: {
						relative 	: ee.ParallaxElement.elementSettings.parallax_element_pan_relative,
						axis 	 	: ee.ParallaxElement.elementSettings.parallax_element_pan_axis,
					},
					speed 			: {},
				};;

			ee.ParallaxElement.maybeDestroy = function() {
				if ( instance )
					instance.destroy();
			};

			ee.ParallaxElement.init = function() {

				if ( 'column' === ee.getElementType( $scope ) ) $element = $scope.find( '> .elementor-column-wrap' );
				if ( 'widget'=== ee.getElementType( $scope ) ) $element = $scope.find( '.elementor-widget-container' );

				ee.ParallaxElement.maybeDestroy();

				if ( 'yes' !== ee.ParallaxElement.elementSettings.parallax_element_enable )
					return;

				if ( 'scroll' === ee.ParallaxElement.elementSettings.parallax_element_type ) {
					if ( 'none' !== ee.ParallaxElement.elementSettings.parallax_element_disable_on ) {
						parallaxElementArgs.scroll.responsive = ee.ParallaxElement.elementSettings.parallax_element_disable_on;
					}
				} else if ( 'mouse' === ee.ParallaxElement.elementSettings.parallax_element_type ) {
					if ( undefined !== typeof ee.ParallaxElement.elementSettings.parallax_element_pan_distance && 'element' === ee.ParallaxElement.elementSettings.parallax_element_pan_relative ) {
						parallaxElementArgs.mouse.distance = ee.ParallaxElement.elementSettings.parallax_element_pan_distance.size;
					}
				}

				if ( ee.ParallaxElement.elementSettings.parallax_element_speed.size ) {
					parallaxElementArgs.speed.desktop = ee.ParallaxElement.elementSettings.parallax_element_speed.size;
				}

				if ( ee.ParallaxElement.elementSettings.parallax_element_speed_tablet.size ) {
					parallaxElementArgs.speed.tablet = ee.ParallaxElement.elementSettings.parallax_element_speed_tablet.size;
				}

				if ( ee.ParallaxElement.elementSettings.parallax_element_speed_mobile.size ) {
					parallaxElementArgs.speed.mobile = ee.ParallaxElement.elementSettings.parallax_element_speed_mobile.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					parallaxElementArgs.scope = window.elementor.$previewContents;
				}

				$element.parallaxElement( parallaxElementArgs );
			};

			ee.ParallaxElement.init();

			ee.onElementRemove( $scope, function() {
				ee.ParallaxElement.maybeDestroy();
			});
		},

		////////////////////////////////////////////
		// GallerySlider 						////
		////////////////////////////////////////////

		GallerySlider : function( $scope, $ ) {

			ee.GallerySlider.elementSettings 	= ee.getElementSettings( $scope );

			var $carousel 		= $scope.find('.ee-gallery-slider__carousel'),
				$preview 		= $scope.find('.ee-gallery-slider__preview'),
				$thumbs 		= $scope.find('.ee-gallery .ee-gallery__item'),

				start 			= elementorFrontend.config.is_rtl ? 'right' : 'left',
				end 			= elementorFrontend.config.is_rtl ? 'left' : 'right',

				slickArgs 		= {
					slidesToShow 	: 1,
					slidesToScroll	: 1,
					adaptiveHeight 	: 'yes' === ee.GallerySlider.elementSettings.adaptive_height,
					autoplay 		: 'yes' === ee.GallerySlider.elementSettings.autoplay,
					autoplaySpeed 	: ee.GallerySlider.elementSettings.autoplay_speed,
					infinite		: 'yes' === ee.GallerySlider.elementSettings.infinite,
					pauseOnHover 	: 'yes' === ee.GallerySlider.elementSettings.pause_on_hover,
					speed 			: ee.GallerySlider.elementSettings.speed,
					arrows 			: 'yes' === ee.GallerySlider.elementSettings.show_arrows,
					prevArrow 		: '<div class="ee-carousel__arrow ee-arrow ee-arrow--prev"><i class="eicon-chevron-' + start + '"></i></div>',
					nextArrow 		: '<div class="ee-carousel__arrow ee-arrow ee-arrow--next"><i class="eicon-chevron-' + end + '"></i></div>',
					dots 			: false,
					rtl 			: 'rtl' === ee.GallerySlider.elementSettings.direction,
					fade			: 'fade' === ee.GallerySlider.elementSettings.effect,
				};

			ee.GallerySlider.events = function() {
				$carousel.on( 'beforeChange', function ( event, slick, currentSlide, nextSlide ) {
					var currentSlide = nextSlide;
					$thumbs.removeClass('is--active');
					$thumbs.eq( currentSlide ).addClass('is--active');
				});

				$thumbs.each( function( currentSlide ) {
					$(this).on( 'click', function ( e ) {
						e.preventDefault();
						$carousel.slick( 'slickGoTo', currentSlide );
					});
				});
			};

			ee.GallerySlider.init = function() {
				$carousel.slick( slickArgs );

				$thumbs.removeClass('is--active');
				$thumbs.eq( 0 ).addClass('is--active');

				$carousel.slick( 'setPosition' );

				ee.GallerySlider.events();

				if ( elementorFrontend.isEditMode() ) {
					$preview._resize( function() {
						$carousel.slick( 'setPosition' );
					});
				}
			};

			ee.GallerySlider.init();
		},

		////////////////////////////////////////////
		// Timeline 							////
		////////////////////////////////////////////

		Timeline : function( $scope, $ ) {

			ee.Timeline.elementSettings 	= ee.getElementSettings( $scope );

			var $timeline = $scope.find('.ee-timeline'),
				timelineArgs = {};

			ee.Timeline.init = function() {
				if ( elementorFrontend.isEditMode() ) {
					timelineArgs.scope = window.elementor.$previewContents;
				}

				if ( 'undefined' !== typeof ee.Timeline.elementSettings.line_location && ee.Timeline.elementSettings.line_location.size ) {
					timelineArgs.lineLocation = ee.Timeline.elementSettings.line_location.size;
				}

				$timeline.eeTimeline( timelineArgs );
			};

			ee.Timeline.init();
		},

		////////////////////////////////////////////
		// HeadingExtra 						////
		////////////////////////////////////////////

		HeadingExtra : function( $scope, $ ) {

			ee.HeadingExtra.elementSettings 	= ee.getElementSettings( $scope );

			var $heading 		= $scope.find('.ee-heading'),
				$longShadow 	= $heading.find('.ee-heading__long-shadow'),
				longShadowArgs 	= {};

			if ( 'yes' !== ee.HeadingExtra.elementSettings.title_long_shadow_enable )
					return;

			ee.HeadingExtra.init = function() {

				if ( ee.HeadingExtra.elementSettings.title_long_shadow_color ) {
					longShadowArgs.colorShadow = ee.HeadingExtra.elementSettings.title_long_shadow_color;
				}

				if ( ee.HeadingExtra.elementSettings.title_long_shadow_size ) {
					longShadowArgs.sizeShadow = ee.HeadingExtra.elementSettings.title_long_shadow_size.size;
				}

				if ( ee.HeadingExtra.elementSettings.title_long_shadow_direction ) {
					longShadowArgs.directionShadow = ee.HeadingExtra.elementSettings.title_long_shadow_direction;
				}

				$longShadow.longShadow( longShadowArgs );
			};

			ee.HeadingExtra.init();
		},

		////////////////////////////////////////////
		// ImageComparison 						////
		////////////////////////////////////////////

		ImageComparison : function( $scope, $ ) {

			ee.ImageComparison.elementSettings = ee.getElementSettings( $scope );

			var $images = $scope.find('.ee-image-comparison'),
				imageComparisonArgs = {
					animation 		: 'yes' === ee.ImageComparison.elementSettings.entrance_animation,
					clickToMove 	: 'yes' === ee.ImageComparison.elementSettings.click_to_move,
					clickLabels 	: 'yes' === ee.ImageComparison.elementSettings.click_labels,
					animateClick 	: 'yes' === ee.ImageComparison.elementSettings.click_animate,
				};

			ee.ImageComparison.init = function() {
				if ( elementorFrontend.isEditMode() ) {
					imageComparisonArgs.scope = window.elementor.$previewContents;
					imageComparisonArgs.editMode = true;
				}

				$images.imageComparison( imageComparisonArgs );
			};

			ee.ImageComparison.init();
		},

		////////////////////////////////////////////
		// Tooltips 							////
		////////////////////////////////////////////

		Tooltips : function( $scope, $ ) {

			ee.Tooltips.elementSettings = ee.getElementSettings( $scope );
			ee.Tooltips.globalSettings 	= ee.getGlobalSettings( 'extras' );

			var $hotspots	= $scope.find( '.hotip' ),
				tooltips 	= new eeTooltips( $scope ),
				scopeId 	= $scope.data( 'id' ),
				skin 		= ee.getElementSkin( $scope );

				skin 		= ( '' !== skin && 'default' !== skin ) ? skin + '_' : '';

			var hotipsArgs 	= {
					id 				: scopeId,
					fixed 			: 'fixed' === ee.Tooltips.elementSettings[ skin + 'css_position' ],
					position 		: ee.Tooltips.elementSettings[ skin + 'position' ] || ee.Tooltips.globalSettings.ee_tooltips_position,
					arrowPositionH 	: ee.Tooltips.elementSettings[ skin + 'arrow_position_h' ] || ee.Tooltips.globalSettings.ee_tooltips_arrow_position_h,
					arrowPositionV 	: ee.Tooltips.elementSettings[ skin + 'arrow_position_v' ] || ee.Tooltips.globalSettings.ee_tooltips_arrow_position_v,
					trigger 		: {
						desktop 	: ee.Tooltips.elementSettings[ skin + 'trigger' ],
						tablet 		: ee.Tooltips.elementSettings[ skin + 'trigger_tablet' ],
						mobile 		: ee.Tooltips.elementSettings[ skin + 'trigger_mobile' ],
					},
					hide 			: {
						desktop 	: ee.Tooltips.elementSettings[ skin + '_hide' ],
						tablet 		: ee.Tooltips.elementSettings[ skin + '_hide_tablet' ],
						mobile 		: ee.Tooltips.elementSettings[ skin + '_hide_mobile' ],
					},
					responsive 		: {
						disable 	: ee.Tooltips.elementSettings[ skin + 'disable' ] || ee.Tooltips.globalSettings.ee_tooltips_disable,
						breakpoints		: {
							'mobile'	: 768,
							'tablet' 	: 1024,
						},
					},
				};

			ee.Tooltips.init = function() {

				tooltips.remove( $scope );

				if ( '' !== ee.Tooltips.elementSettings[ skin + 'delay_in' ].size ) {
					hotipsArgs.delayIn = ee.Tooltips.elementSettings[ skin + 'delay_in' ].size;
				} else if ( ee.Tooltips.globalSettings.ee_tooltips_delay_in.size ) {
					hotipsArgs.delayIn = ee.Tooltips.globalSettings.ee_tooltips_delay_in.size;
				}

				if ( '' !== ee.Tooltips.elementSettings[ skin + 'delay_out' ].size ) {
					hotipsArgs.delayOut = ee.Tooltips.elementSettings[ skin + 'delay_out' ].size;
				} else if ( ee.Tooltips.globalSettings.ee_tooltips_delay_out.size ) {
					hotipsArgs.delayOut = ee.Tooltips.globalSettings.ee_tooltips_delay_out.size;
				}

				if ( '' !== ee.Tooltips.elementSettings[ skin + 'duration' ].size ) {
					hotipsArgs.speed = ee.Tooltips.elementSettings[ skin + 'duration' ].size;
				} else if ( ee.Tooltips.globalSettings.ee_tooltips_duration.size ) {
					hotipsArgs.speed = ee.Tooltips.globalSettings.ee_tooltips_duration.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					hotipsArgs.scope = ee.getWindow();
					$hotspots.attr( 'data-hotips-class', 'ee-global ee-tooltip ee-tooltip-' + scopeId );
				}

				$hotspots.hotips( hotipsArgs );
			};

			ee.onElementRemove( $scope, function() {
				tooltips.remove( $scope );
			});

			ee.Tooltips.init();
		},

		////////////////////////////////////////////
		// VideoPlayer 							////
		////////////////////////////////////////////

		VideoPlayer : function( $scope, $ ) {

			ee.VideoPlayer.elementSettings 	= ee.getElementSettings( $scope );

			var $video = $scope.find( '.ee-video-player' ),
				videoPlayerArgs = {
					playOnViewport		: 'yes' === ee.VideoPlayer.elementSettings.video_play_viewport,
					stopOffViewport		: 'yes' === ee.VideoPlayer.elementSettings.video_stop_viewport,
					endAtLastFrame 		: 'yes' === ee.VideoPlayer.elementSettings.video_end_at_last_frame,
					restartOnPause		: 'yes' === ee.VideoPlayer.elementSettings.video_restart_on_pause,
					stopOthersOnPlay	: 'yes' === ee.VideoPlayer.elementSettings.video_stop_others,
				};

			if ( 'undefined' !== typeof ee.VideoPlayer.elementSettings.video_speed ) {
				videoPlayerArgs.speed = ee.VideoPlayer.elementSettings.video_speed.size;
			}

			if ( ! $video.length )
				return;

			ee.VideoPlayer.init = function() {
				if ( 'undefined' !== typeof ee.VideoPlayer.elementSettings.video_volume ) {
					videoPlayerArgs.volume = ee.VideoPlayer.elementSettings.video_volume.size;
				}

				$video.videoPlayer( videoPlayerArgs );
			};

			ee.VideoPlayer.init();
		},

		////////////////////////////////////////////
		// Devices 								////
		////////////////////////////////////////////

		Devices : function( $scope, $ ) {

			ee.Devices.elementSettings 	= ee.getElementSettings( $scope );

			var $wrapper 		= $scope.find( '.ee-device-wrapper' ),
				$device 		= $scope.find( '.ee-device' ),
				$shape 			= $scope.find( '.ee-device__shape' ),
				url 			= null,
				svg 			= null;

			ee.Devices.init = function() {

				// Fallback to phone when no switcher option is selected
				if ( ! ee.Devices.elementSettings.device_type ) {
					ee.Devices.elementSettings.device_type = 'phone';
				}

				// Set SVG URL
				url = elementorExtrasFrontendConfig.urls.assets + 'shapes/' + ee.Devices.elementSettings.device_type + '.svg';

				// Get the file
				jQuery.get( url, function( data ) {

					// And append the the first node to our wrapper
					$shape.html( data.childNodes[0] );

					// Set the svg element
					svg = $shape.find( "svg.devices-elementor-svg" ).get(0);

				});

				if ( 'yes' === ee.Devices.elementSettings.device_orientation_control ) {
					$scope.find('.ee-device__orientation').on( 'click', function() {
						$scope.toggleClass( 'ee-device-orientation-landscape' );
					});
				}

				ee.VideoPlayer( $scope );
			};

			ee.Devices.init();
		},

		////////////////////////////////////////////
		// CircleProgress 						////
		////////////////////////////////////////////

		CircleProgress : function( $scope, $ ) {

			var elementSettings = ee.getElementSettings( $scope );

			var $circle 		= $scope.find( '.ee-circle-progress' ),
				$value 			= $scope.find( '.ee-circle-progress__value .value' ),
				$suffix 		= $scope.find( '.ee-circle-progress__value .suffix' ),
				_value 			= ElementorExtrasUtils.parseValue( $scope.find( '.ee-circle-progress__value' ).data('cp-value'), 75 ),
				_move_decimal 	= 0,
				_absolute 		= _value,
				_max_value 		= 100,
				_decimals 		= ElementorExtrasUtils.countDecimals( _value ),
				cpArgs 			= {
					value 		: 0.75,
					reverse 	: 'yes' === elementSettings.reverse,
					lineCap		: elementSettings.lineCap,
					startAngle 	: -Math.PI,
					animation 	: {
						easing 	: elementSettings.easing,
					},
				};

			ee.CircleProgress.init = function() {

				if ( elementSettings.value_max ) {
					_max_value = elementSettings.value_max;
				}

				if ( 'undefined' !== typeof elementSettings.value_decimal_move ) {
					_move_decimal = elementSettings.value_decimal_move.size * -1;
				}

				if ( 'undefined' !== typeof _value ) {

					if ( 'percentage' === elementSettings.value_progress ) {
						_value = _value / 100;
					} else if ( 'absolute' === elementSettings.value_progress ) {
						_value = _value / _max_value;
					}

					cpArgs.value = _value;
				}

				if ( elementSettings.size.size ) {
					cpArgs.size = elementSettings.size.size;
				}

				if ( elementSettings.thickness.size ) {

					// Prevent thickness from going over the radius value of the circle
					if ( elementSettings.thickness.size > ( elementSettings.size.size / 2 ) ) {
						cpArgs.thickness = elementSettings.size.size / 2;
					} else {
						cpArgs.thickness = elementSettings.thickness.size;
					}
				}

				if ( elementSettings.angle.size ) {
					cpArgs.startAngle = cpArgs.startAngle + elementSettings.angle.size;
				}

				if ( elementSettings.emptyFill ) {
					cpArgs.emptyFill = elementSettings.emptyFill;
				}

				if ( ! elementSettings.animate ) {

					cpArgs.animation = false;

					$circle.circleProgress( cpArgs );
					$value.text( ee.CircleProgress.getStepValue( cpArgs.value, _value, _absolute ) );

				} else {
					if ( elementSettings.duration )
						cpArgs.animation.duration = elementSettings.duration.size;

					$circle.circleProgress( cpArgs ).on( 'circle-animation-progress', ee.CircleProgress.onProgress );
				}

				if ( ! elementorFrontend.isEditMode() ) {
					ee.CircleProgress.appear();
				}
			};

			ee.CircleProgress.appear = function() {
				var canvas = $( $circle.circleProgress( 'widget' ) );
					canvas.stop();

				$circle._appear({
					force_process: true,
				});

				$circle.on('_appear', function() {
					if ( ! $circle.data('animated') ) {
						$circle.circleProgress( 'value', _value );
						$circle.data('animated', true);
					}
				});
			};

			ee.CircleProgress.onProgress = function( event, progress, stepValue ) {
				$value.text( ee.CircleProgress.getStepValue( stepValue, _value, _absolute ) );
			};

			ee.CircleProgress.getStepValue = function( stepValue, _value, _absolute ) {
				var _stepValue = ( 'percentage' === elementSettings.value_progress ) ? stepValue * 100 : _absolute * stepValue / _value;
					_stepValue = _stepValue.toFixed( _decimals );

				if ( _move_decimal ) {
					_stepValue = ElementorExtrasUtils.moveDecimal( _stepValue, _move_decimal );
				}

				return _stepValue;
			}

			ee.CircleProgress.init();
		},

		////////////////////////////////////////////
		// GlobalTooltip 						////
		////////////////////////////////////////////

		GlobalTooltip : function( $scope, $ ) {

			ee.GlobalTooltip.elementSettings 	= ee.getElementSettings( $scope );
			ee.GlobalTooltip.globalSettings 	= ee.getGlobalSettings( 'extras' );

			var $target 	= $scope,
				scopeId 	= $scope.data( 'id' ),
				instance 	= $target.data( 'hotips' ),
				tooltips 	= new eeTooltips( $scope ),
				hotipsArgs 	= {
					fixed 			: 'fixed' === ee.GlobalTooltip.elementSettings.tooltip_css_position,
					position 		: ee.GlobalTooltip.elementSettings.tooltip_position || ee.GlobalTooltip.globalSettings.ee_tooltips_position,
					arrowPositionH 	: ee.GlobalTooltip.elementSettings.tooltip_arrow_position_h || ee.GlobalTooltip.globalSettings.ee_tooltips_arrow_positio_h,
					arrowPositionV 	: ee.GlobalTooltip.elementSettings.tooltip_arrow_position_v || ee.GlobalTooltip.globalSettings.ee_tooltips_arrow_positio_v,
					responsive 		: {
						disable : ee.GlobalTooltip.elementSettings.tooltip_disable || ee.GlobalTooltip.globalSettings.ee_tooltips_disable,
						breakpoints		: {
							'mobile'	: 768,
							'tablet' 	: 1024,
						},
					},
					source 		: '#hotip-content-' + scopeId,
				};

			ee.GlobalTooltip.setTarget = function() {
				if ( 'custom' === ee.GlobalTooltip.elementSettings.tooltip_target ) {
					if ( '' !== ee.GlobalTooltip.elementSettings.tooltip_selector ) {
						var $_target = $scope.find( ee.GlobalTooltip.elementSettings.tooltip_selector );

						if ( $_target.length ) {
							$target = $_target;
							instance = $target.data( 'hotips' );
						}
					}
				}
			};

			ee.GlobalTooltip.maybeDestroy = function() {
				if ( instance )
					instance.destroy();
			};

			ee.GlobalTooltip.init = function() {

				if ( $target.data( 'hotips' ) ) {
					tooltips.remove( $scope );
					$target.data( 'hotips' ).destroy();
				}

				if ( 'yes' !== ee.GlobalTooltip.elementSettings.tooltip_enable )
					return;

				ee.GlobalTooltip.setTarget();

				ee.GlobalTooltip.maybeDestroy();

				if ( ee.GlobalTooltip.elementSettings.tooltip_trigger ) {
					hotipsArgs.trigger = {
						desktop : ee.GlobalTooltip.elementSettings.tooltip_trigger,
						tablet : ee.GlobalTooltip.elementSettings.tooltip_trigger_tablet,
						mobile : ee.GlobalTooltip.elementSettings.tooltip_trigger_mobile,
					};
				}

				if ( ee.GlobalTooltip.elementSettings.tooltip__hide ) {
					hotipsArgs.hide = {
						desktop : ee.GlobalTooltip.elementSettings.tooltip__hide,
						tablet : ee.GlobalTooltip.elementSettings.tooltip__hide_tablet,
						mobile : ee.GlobalTooltip.elementSettings.tooltip__hide_mobile,
					};
				}

				if ( '' !== ee.GlobalTooltip.elementSettings.tooltip_delay_in.size ) {
					hotipsArgs.delayIn = ee.GlobalTooltip.elementSettings.tooltip_delay_in.size;
				} else if ( ee.GlobalTooltip.globalSettings.ee_tooltips_delay_in.size ) {
					hotipsArgs.delayIn = ee.GlobalTooltip.globalSettings.ee_tooltips_delay_in.size;
				}

				if ( '' !== ee.GlobalTooltip.elementSettings.tooltip_delay_out.size ) {
					hotipsArgs.delayOut = ee.GlobalTooltip.elementSettings.tooltip_delay_out.size;
				} else if ( ee.GlobalTooltip.globalSettings.ee_tooltips_delay_out.size ) {
					hotipsArgs.delayOut = ee.GlobalTooltip.globalSettings.ee_tooltips_delay_out.size;
				}

				if ( '' !== ee.GlobalTooltip.elementSettings.tooltip_duration.size ) {
					hotipsArgs.speed = ee.GlobalTooltip.elementSettings.tooltip_duration.size;
				} else if ( ee.GlobalTooltip.globalSettings.ee_tooltips_duration.size ) {
					hotipsArgs.speed = ee.GlobalTooltip.globalSettings.ee_tooltips_duration.size;
				}

				if ( elementorFrontend.isEditMode() ) {
					hotipsArgs.scope = window.elementor.$previewContents;
				}

				$target.attr( 'data-hotips-class', 'ee-global ee-tooltip ee-tooltip-' + scopeId );
				
				$target.hotips( hotipsArgs );

				ee.onElementRemove( $scope, function() {
					ee.GlobalTooltip.maybeDestroy();
				});
			};

			ee.GlobalTooltip.init();
		},
	};

	var ElementorExtrasUtils = {

		timer : null,

		countDecimals : function ( value ) {
			if( Math.floor( value ) === value ) return 0;
			return value.toString().split(".")[1].length || 0;
		},

		parseValue : function ( value, _default ) {
			var _value = value;

			if ( 'string' === typeof _value ) {
				_value = _value.replace(/\s/g, '');
				_value = _value.replace( ',', '.' );

				if ( _value.indexOf('/') > -1 ) {
					var _div_value = _value.split('/');

					if ( ! isNaN( _div_value[0] ) && ! isNaN( _div_value[1] ) ) {
						_div_value = parseInt(_div_value[0]) / _div_value[1];
						_value = _div_value * 100;
						_value = _value.toFixed( 0 );
					}
				}
			}

			if ( ! isNaN( _value ) ) {
				_value = Math.abs( parseFloat( _value ) );
			} else {
				_value = _default;
			}
			return _value;
		},

		findObjectByKey : function( array, key, value ) {
			for ( var i = 0; i < array.length; i++ ) {
				if ( array[ i ][ key ] === value ) {
					return array[ i ];
				}
			}
			return null;
		},

		moveDecimal : function( n, x ) {
			var v = n / Math.pow( 10, x );
				v = ( v > 1 ) ? Math.round( v ) : Math.round( v * Math.pow ( 10, x + 1 ) ) / Math.pow ( 10, x + 1 );
			return v;
		},

		trackLeave : function (ev) {
			if ( ev.clientY > 0 ) {
				return;
			}

			if ( ElementorExtrasUtils.timer ) {
				clearTimeout( ElementorExtrasUtils.timer );
			}

			if ( $.exitIntent.settings.sensitivity <= 0 ) {
				$.event.trigger('exitintent');
				return;
			}

			ElementorExtrasUtils.timer = setTimeout( function() {
				ElementorExtrasUtils.timer = null;
				$.event.trigger( 'exitintent' );
			}, $.exitIntent.settings.sensitivity );
		},

		serializeObject : function( data ) {
			var o = {};
			var a = data.serializeArray();

			$.each(a, function() {
				if (!this.value) return;
				if (o[this.name]) {
					if (!o[this.name].push) {
						o[this.name] = [o[this.name]];
					}
					o[this.name].push(this.value || '');
				} else {
					o[this.name] = this.value || '';
				}
			});
			return o;
		},

		trackEnter : function() {
			if ( ElementorExtrasUtils.timer ) {
				clearTimeout( ElementorExtrasUtils.timer );
				ElementorExtrasUtils.timer = null;
			}
		},
	};

	window.ElementorExtrasOffcanvas = function() {
		var self = this;

		self.initialized 	= false;
		self.controller 	= null;

		self.init = function() {
			self.controller = new slidebars();
			self.controller.init();
			self.initialized = true;
		};
	};

	var offcanvas = new ElementorExtrasOffcanvas();

	elementorFrontend.eeOffcanvas = offcanvas;

	window.eeTooltips = function() {
		var self = this;

		self.remove = function( $scope ) {

			if ( $scope.length ) {
				// Remove just the tooltips within the scope
				var scopeId = $scope.data('id'),
					$hotips = $( '.hotip-tooltip[data-target-id="' + scopeId + '"]' );
			} else {

				// Remove all tooltips on page
				$hotips = $( '.hotip-tooltip' );
			}

			if ( $hotips.length ) {
				$hotips.remove();
			}
		};
	};

	window.eeSticky = function( $scope, settings ) {
		var self            = this,
			$stickyParent 	= null,
			$stickyElement 	= $scope,
			$column 		= $scope.closest('.elementor-column'),
			$section 		= $scope.closest('.elementor-section'),
			$selector 		= null,
			$window 		= ee.getWindow(),
			$body 			= elementorFrontend.isEditMode() ? window.elementor.$previewContents.find('body') : $('body'),

			customParent 	= false,
			timeout 		= null,
			instance 		= null,
			breakpoint 		= 'tablet' === settings.sticky_unstick_on ? 1023 : 767,
			
			stickyArgs 		= {
				top 			: ( ee.isAdminBar() ) ? 32 : 0,
				stickyClass 	: 'ee-sticky--stuck',
				followScroll	: 'yes' === settings.sticky_follow_scroll,
				bottomEnd		: 0,
				responsive		: {},
				onResize 		: function() {
					$stickyElement.hcSticky( 'update', {
						bottomEnd : self.getBottomEnd(),
					});
				},
			};

		self.isEnabled = function() {
			return 'yes' === settings.sticky_enable;
		};

		self.getStickyContainer = function() {

			var $container = $scope.parent();

			if ( '' === settings.sticky_parent ) { // Column
				$container = ( 'widget' === ee.getElementType( $scope ) ) ? $column : $container;
			} else if ( 'section' === settings.sticky_parent ) { // Section
				$container = ( 'widget' === ee.getElementType( $scope ) ) ? $section : $container;
			} else if ( 'body' === settings.sticky_parent ) { // Body
				$container = $body;
			} else if ( 'custom' === settings.sticky_parent && '' !== settings.sticky_parent_selector ) { // Custom
				if ( $scope.closest( settings.sticky_parent_selector ).length ) {
					$container = $scope.closest( settings.sticky_parent_selector );
				}
			}
			return $container;
		};

		self.setStickyParent = function() {
			$stickyParent = $scope.parent();
			$stickyParent.addClass( 'ee-sticky-parent' );
			stickyArgs.stickTo = $stickyParent.get(0);
		};

		self.getBottomEndValue = function( $element ) {
			return ( ( $element.offset().top + $element.outerHeight() ) - ( $stickyParent.offset().top + $stickyParent.outerHeight() ) ) * -1;
		};

		self.getBottomEnd = function() {
			var bottomEnd = 0;

			bottomEnd += self.getBottomEndValue( self.getStickyContainer() );

			if ( settings.sticky_offset_bottom ) {
				bottomEnd += settings.sticky_offset_bottom.size;
			}

			return bottomEnd;
		};

		self.setBottomEnd = function() {
			stickyArgs.bottomEnd = self.getBottomEnd();
		};

		self.events = function() {
			$window.on( 'resize', function() {
				if ( $stickyElement.data( 'hcSticky' ) )
					$stickyElement.hcSticky( 'refresh' );
			});

			ee.onElementRemove( $scope, function() {
				$stickyElement.hcSticky( 'detach' );
			});
		};

		self.init = function() {

			if ( $stickyElement.data( 'hcSticky' ) )
				$stickyElement.hcSticky( 'destroy' );

			// Exit if sticky not enabled
			if ( ! self.isEnabled() || ! $stickyElement.length )
				return;

			// Set sticky parent element
			self.setStickyParent();

			if ( ! $stickyParent.length )
				return;

			self.setBottomEnd();

			stickyArgs.onStart = function() {
				$stickyParent.addClass( 'ee-sticky-parent--stuck' );
			};

			stickyArgs.onStop = function() {
				$stickyParent.removeClass( 'ee-sticky-parent--stuck' );
			};

			// Set offset option
			if ( settings.sticky_offset ) {
				stickyArgs.top += settings.sticky_offset.size;
			}

			// Set responsive options
			if ( 'none' !== settings.sticky_unstick_on ) {
				stickyArgs.responsive[ breakpoint ] = {
					disable: true
				};
			}

			$stickyElement
				.addClass( 'ee-sticky' )
				.hcSticky( stickyArgs );

			if ( elementorFrontend.isEditMode() ) {
				$stickyElement.hcSticky( 'update', stickyArgs );
			}

			self.events();
		};
	};

	$.exitIntent = function( enable, options ) {
		$.exitIntent.settings = $.extend($.exitIntent.settings, options);

		if ( enable == 'enable' ) {
			$(window).mouseleave( ElementorExtrasUtils.trackLeave );
			$(window).mouseenter( ElementorExtrasUtils.trackEnter );
		} else if ( enable == 'disable' ) {
			trackEnter(); // Turn off any outstanding timer
			$(window).unbind( 'mouseleave', ElementorExtrasUtils.trackLeave );
			$(window).unbind( 'mouseenter', ElementorExtrasUtils.trackEnter );
		} else {
			throw "Invalid parameter to jQuery.exitIntent -- should be 'enable'/'disable'";
		}
	}

	$.exitIntent.settings = {
		'sensitivity': 300
	};

	$( window ).on( 'elementor/frontend/init', ee.init );

}( jQuery, window.elementorFrontend ) );