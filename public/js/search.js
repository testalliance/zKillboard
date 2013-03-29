(function( $ ) {

	"use strict"

	var zz_search = function(element) {

		//create our jquery objects
		this.data = {}, this.data['element'] = element, this.data['menu'] = $('<ul class="autocomplete dropdown-menu" style="display: none;"></ul>').appendTo('body');

		//disable the parent form submit capability
		this.data['element'].parents('form').on('submit', $.proxy(function(event) { if (this.data['menu'].find('.active').length != 0) { return false; } return true; }, this));
		
		//bind our primary search event
		this.data['element'].on('keyup',  $.proxy(function(event) { if (!event.isDefaultPrevented() && event.keyCode != 9 && event.keyCode != 38 && event.keyCode != 40) { $.proxy(this.do_search(event), this); } }, this));

		//bind our utility key events
		this.data['element'].on('keydown', $.proxy(function(event) { 
			event.stopPropagation();
			switch(event.keyCode) {
				case 38: $.proxy(this.move_prev(event), this); break;
				case 40: $.proxy(this.move_next(event), this); break;
				case 13: $.proxy(this.goto_item(event), this); break;
				case 27: $.proxy(this.hide_menu(event), this); break;
			}
		}, this));

		//handle a couple of other types of event
		this.data['element'].on('blur',  $.proxy(function(event) { this.hide_menu(event); }, this));
		this.data['menu'].on('mouseenter', 'li', $.proxy(function() { this.data['menu'].find('.active').removeClass('active'); $(this).addClass('active'); }, this));		
	}

	//add all the functions we need
	zz_search.prototype = {
		
		constructor: zz_search,
		
		//get the position of the input so we can correctly offset the search window
		get_position: function() {
			var pos = $.extend({}, this.data['element'].offset(), { height: this.data['element'][0].offsetHeight });
			return { top: (pos.top + pos.height), left: pos.left };
		}, 
				
		//move the selection around
		move_prev: function(event) { event.preventDefault(); this.data['menu'].find('.active').removeClass('active').prev().addClass('active'); if ( this.data['menu'].find('.active').length == 0) { this.data['menu'].find('li').last().addClass('active'); } },
		move_next: function(event) { event.preventDefault(); this.data['menu'].find('.active').removeClass('active').next().addClass('active'); if ( this.data['menu'].find('.active').length == 0) { this.data['menu'].find('li').first().addClass('active'); } },
	
		//goto the selected items seach page
		goto_item: function(event) { event.preventDefault(); window.location = this.data['menu'].find('.active a').attr('href'); },
	
		//hide the drop down
		hide_menu: function(event) { this.data['menu'].fadeOut(200); },
		
		//the main event - perfom an lookup to see what there is to see
		do_search: function(event) { 
			//clear any throttled searched now now
			clearTimeout(this.data['throttle']);

			//if the saerch string is empty then dont' bother searching
			if ( this.data['element'].val() == '' ) { this.hide_menu(event); return; }

			//create our throttled search
			this.data['throttle'] = setTimeout($.proxy(function() {
				$.ajax('/autocomplete/', { 'data' : { 'query' : this.data['element'].val() }, 'type' : 'post', 'dataType' : 'json', 'success' : $.proxy(function(result) {
					//empty the dropdown and append the new data
					this.data['menu'].empty().append($.map(result, $.proxy(function(item, index) {
						return '<li data-value="' + item.id + '"><a href="/' + item.type + '/' + item.id + '/">' + ((item.image != '') ? '<img src="https://image.zkillboard.com/' + item.image + '" width="32" height="32" alt=" ">' : '') + item.name.replace(RegExp('(' + this.data['element'].val() + ')', "gi"), function($1, match){ return '<strong>' + match + '</strong>'; } ) + '<span>' + item.type + '</span></a></li>';
					}, this)));

					//if its not visible already fade it in - and position it as needed and autoselect the first item
					this.data['menu'].not(':visible').css(this.get_position()).fadeIn(200);
					this.data['menu'].find('li').first().addClass('active');
				}, this)});
			}, this), 300);
		}
	};

	//define the zz_search method
	$.fn.zz_search = function() {
		return this.each(function() {
			var $this = $(this), data = $this.data('zz_search');
			if (!data) { $this.data('zz_search', (data = new zz_search($this))); }
		});
	}
})( jQuery );