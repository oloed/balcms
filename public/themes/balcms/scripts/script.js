(function($){
	// Cache
	var BalCMS = $.BalCMS, Ajaxy = $.Ajaxy||false, Sparkle = $.Sparkle||false, ie = $.browser.msie;

	// Sparkle
	if ( Sparkle ) {
		// Delete Warning
		Sparkle.addExtension('deletewarn', function(){
			var $this = $(this); var Sparkle = $.Sparkle;
			// Fetch
			var $inputs = $this.findAndSelf('.delete-action').click(function(event){
				var $this = $(this);
				if ( !confirm($this.attr('title')||'Are you sure you want to delete this?') ) {
					// Prevent
					event.stopPropagation();
					event.preventDefault();
				}
			});
		});
	}

	// changePopulate
	$.fn.changePopulate = $.fn.changePopulate || function(url, name, items, callback_before, callback_after) {
		// Prepare
		var $input = $(this),
			$inputs = $input.findAndSelf(':input');
		// Events
		var events = {
			change: function(event){
				// Prepare Data
				var data = {};
				data[name] = [];

				// Add Data
				var values = $input.values();
				for ( var values_key in values ) {
					data[name] = values[values_key];
					break;
				}

				// Define Events
				var events = {
					success: function(data, status){
						for ( var code in items ) {
							var item = items[code];
							var type = item.type||'option';
							var name = item.name||null;
							var current = item.current||[]; if ( typeof current !== 'array' && typeof current !== 'object'  ) {
								current = [current];
							}
							var $el = item.el||item;
							var keys = typeof item.keys !== 'undefined' ? item.keys : true;
							// Empty whatever it is
							$el.empty();
							// Fire our Before Handler
							if ( callback_before||false ) {
								callback_before(data);
							}
							// Handle Data
							if ( typeof data[code] === 'undefined' || data === null || typeof data[code].length !== 'undefined' /* is array */ ) {
								// For some reason we don't have data
							} else {
								// Cycle through our object
								for ( var key in data[code] ) {
									var title = data[code][key];
									var value = keys ? key : title;
									switch ( type ) {
										case 'option':
											var $option = $('<option>').val(value).text(title).appendTo($el);
											if ( current.has(value) ) $option.choose(value);
											break;
										case 'checkbox':
											if ( !name ) {
												console.warn('No name for checkbox in changePopulate', $el, item, items);
											}
											var $label = $('<label>').text(title).appendTo($el);
											var $checkbox = $('<input>').attr('type','checkbox').val(value).attr('name',name).prependTo($label);
											if ( current.has(value) ) $checkbox.choose(value);
											break;
									}
								}
							}
						}
						// Fire our after callback
						if ( callback_after||false ) {
							callback_after(data);
						}
						return true;
					}
				};

				// Prepare variables
				$.fn.changePopulate.cache = $.fn.changePopulate.cache||{};
				$.fn.changePopulate.xhr = $.fn.changePopulate.xhr||{};
				$.fn.changePopulate.timeout = $.fn.changePopulate.timeout||{};

				// Prepare our codes
				var cacheCode = url+JSON.stringify(data);
				var xhrCode = url+$input.attr('id');

				// Prepare our checks
				var checkXhr = function(){
					if ( typeof $.fn.changePopulate.xhr[xhrCode] !== 'undefined' && $.fn.changePopulate.xhr[xhrCode] ) {
						// XHR Still Running
						$.fn.changePopulate.xhr[xhrCode].abort(); // abort old
					}
				};
				var checkTimeout = function(){
					if ( typeof $.fn.changePopulate.timeout[xhrCode] !== 'undefined' && $.fn.changePopulate.timeout[xhrCode] ) {
						// Timeout Still Running
						clearTimeout($.fn.changePopulate.timeout[xhrCode]);
						$.fn.changePopulate.timeout[xhrCode] = false;
					}
				};

				// Check our cache
				if ( $.fn.changePopulate.cache[cacheCode]||false ) {
					// Use Cache
					checkXhr();
					checkTimeout();
					events.success($.fn.changePopulate.cache[cacheCode], true);
				}
				else {
					// Perform Request
					var fireRequest = function(){
						$inputs.attr('disabled',true); // Disable anything else for the mean time until this ajax request succeeds
						checkXhr();
						$.fn.changePopulate.xhr[xhrCode] = $.ajax({
							url:  url,
							type: 'POST',
							dataType: 'json',
							data: data,
							success: function(data,success) {
								// XHR Validity
								$.fn.changePopulate.xhr[xhrCode] = false; // completed
								$inputs.attr('disabled',false); // renable
								// Check validity
								if ( !(data||false) || data === null ) return;
								// Add to cache
								$.fn.changePopulate.cache[cacheCode] = data;
								return events.success(data,success);
							},
							error: function(){
								// XHR Validity
								$.fn.changePopulate.xhr[xhrCode] = false; // completed
								$inputs.attr('disabled',false); // renable
							}
						});
					};
					// Check Tmeout
					checkTimeout();
					// Fire timeout
					$.fn.changePopulate.timeout[xhrCode] = setTimeout(fireRequest,2000);
				}

			}
		};
		// Bind
		$inputs.unbind('change',events.change).change(events.change).filter(':first').trigger('change');
		// Done
		return $input;
	}

	// InlineEdit
	$.InlineEdit = new $.BalClass({
		'default': {
			edit_button: '<a class="inline-edit-button inline-edit-button-edit">Edit</a>',
			edit_button_class: '',
			remove_button: '<a class="inline-edit-button inline-edit-button-remove">Remove</a>',
			ok_button: '<a class="inline-edit-button inline-edit-button-ok button">OK</a>',
			cancel_button: '<a class="inline-edit-button inline-edit-button-cancel">Cancel</a>',
			panel: '<span class="inline-edit-panel"/>',
			view_panel: '<span class="inline-edit-panel-view"/>',
			edit_panel: '<span class="inline-edit-panel-edit"/>',
			hideClass: 'hide',
			highlightClass: 'editable',
			clickableSelector: '.inline-edit-clickable,label',
			open: function(e,els){
				els.$edit.data('orig', els.$edit.val());
				els.$views.hide();
				els.$edits.show();
				els.$edit.giveFocus();
				return true;
			},
			update: function(e,els){
				els.$view.html(els.$edit.value());
				els.$views.show();
				els.$edits.hide();
				return true;
			},
			cancel: function(e,els){
				els.$edit.val(els.$edit.data('orig'));
				els.$views.show();
				els.$edits.hide();
				return true;
			},
			remove: false
		},
		'editbutton': {
			edit_button_class: 'button'
		}
	});
	$.fn.inlineEdit = function(option, options) {
		// Prepare
		var Me = $.InlineEdit;
		var config = Me.getConfigWithDefault(option, options);
		// Fetch
		var $view = $(this).addClass(config.highlightClass);
		var $edit = $(config.edit).hide().removeClass(config.hideClass);
		var $edit_button = $(config.edit_button);
		var $ok_button = $(config.ok_button);
		var $cancel_button = $(config.cancel_button);
		var $remove_button = $(config.remove_button);
		var $panel = $(config.panel);
		var $view_panel = $(config.view_panel);
		var $edit_panel = $(config.edit_panel).hide();
		// Handle
		var insertPanel = !$panel.inDOM();
		if ( config.edit_button_class && config.edit_button_class.length ) {
			$edit_button.addClass(config.edit_button_class);
		}
		if ( !config.remove ) {
			$remove_button.hide();
		}
		// Build
		$view_panel.append($edit_button).append($remove_button);
		$edit_panel.append($ok_button).append($cancel_button);
		$panel.append($view_panel).append($edit_panel);
		if ( insertPanel ) {
			$panel.insertAfter($edit);
		}
		// Simplify
		var $views = $($view_panel).add($view);
		var $edits = $($edit_panel).add($edit);
		var $edit_buttons = $edit_button.add($view);
		if ( config.clickableSelector ) {
			$edit_buttons = $edit_buttons.add(
				$view.add($edit).add($panel).siblings(config.clickableSelector)
			);
		}
		// Functions
		var pack = function(){
			return {
				$views: $views,
				$edits: $edits,
				$view: $view,
				$edit: $edit,
				$panel: $panel
			};
		}
		var open = function(e){
			config.open.apply(this,[e,pack(),config]);
		};
		var cancel = function(e){
			config.cancel.apply(this,[e,pack(),config]);
		};
		var update = function(e){
			config.update.apply(this,[e,pack(),config]);
		};
		var remove = function(e){
			config.remove.apply(this,[e,pack(),config]);
		};
		// Bind
		$edit_buttons.click(open);
		$cancel_button.click(cancel); $edit.cancel(cancel);
		$ok_button.click(update); $edit.enter(update);
		if ( config.remove ) {
			$remove_button.click(remove);
		}
		// Done
		return this;
	};

})(jQuery);
