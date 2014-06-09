$css('/x-dom/dropdown');
$js('jquery',function(){
	"use strict";
	var event = 'click'
	var event2 = 'dblclick'
	var backdrop = '.dropdown-backdrop'
	var toggle   = '[is=dropdown] li:has(ul)>a'
	var Dropdown = function (element) {
		var $el = $(element).on(event+'.xdom.dropdown', this.toggle)
	}
	Dropdown.prototype.toggle = function (e) {
		var $this = $(this)
		if ($this.is('.disabled, :disabled')) return
		var $parent  = $this.parent()
		var isActive = $parent.hasClass('open')
		clearMenus()
		if (!isActive) {
			if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
				// if mobile we we use a backdrop because click events don't delegate
				$('<div class="dropdown-backdrop"/>').insertAfter($(this)).on('click', clearMenus)
			}
			$parent.trigger(e = $.Event('show.xdom.dropdown'))
			if (e.isDefaultPrevented()) return
			$parent
				.toggleClass('open')
				.trigger('shown.xdom.dropdown')
			$this.focus()
		}
		return false
	}
	Dropdown.prototype.keydown = function (e) {
		if (!/(38|40|27)/.test(e.keyCode)) return
		var $this = $(this)
		e.preventDefault()
		e.stopPropagation()
		if ($this.is('.disabled, :disabled')) return
		var $parent  = $this.parent()
		var isActive = $parent.hasClass('open')
		if (!isActive || (isActive && e.keyCode == 27)) {
			if (e.which == 27) $parent.find(toggle).focus()
			return $this.click()
		}
		var $items = $('[role=menu] li:not(.divider):visible a', $parent)
		if (!$items.length) return
		var index = $items.index($items.filter(':focus'))
		if (e.keyCode == 38 && index > 0)                 index--                        // up
		if (e.keyCode == 40 && index < $items.length - 1) index++                        // down
		if (!~index)                                      index=0
		$items.eq(index).focus()
	}
	Dropdown.prototype.godirect = function (e) {
		var $this = $(this)
		var href = $this.attr('href')
		if(href&&href.indexOf('javascript:')!==0&&href!='#')
			document.location = href
	}
	var clearMenus = function(){
		$(toggle).each(function(e){
			var $parent = $(this).parent()
			if (!$parent.hasClass('open')) return
			$parent.trigger(e = $.Event('hide.xdom.dropdown'))
			if (e.isDefaultPrevented()) return
			$parent.removeClass('open').trigger('hidden.xdom.dropdown')
		})
	}
	$.fn.dropdown = function (option) {
		return this.each(function () {
			var $this = $(this)
			var data  = $this.data('dropdown')
			if (!data) $this.data('dropdown', (data = new Dropdown(this)))
			if (typeof option == 'string') data[option].call($this)
		})
	}
	$(document)
		.on(event+'.xdom.dropdown.data-api', clearMenus)
		.on(event+'.xdom.dropdown.data-api'  , toggle, Dropdown.prototype.toggle)
		.on(event2+'.xdom.dropdown.data-api'  , toggle, Dropdown.prototype.godirect)
		.on('keydown.xdom.dropdown.data-api', toggle + ', [role=menu]' , Dropdown.prototype.keydown)
});
