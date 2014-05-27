$js('jquery',function(){
 "use strict";
  function transitionEnd() {
	var el = document.createElement('bootstrap')
	var transEndEventNames = {
	  'WebkitTransition' : 'webkitTransitionEnd'
	, 'MozTransition'    : 'transitionend'
	, 'OTransition'      : 'oTransitionEnd otransitionend'
	, 'transition'       : 'transitionend'
	}
	for (var name in transEndEventNames) {
	  if (el.style[name] !== undefined) {
		return { end: transEndEventNames[name] }
	  }
	}
  }
  $.fn.emulateTransitionEnd = function (duration) {
	var called = false, $el = this
	$(this).one($.support.transition.end, function () { called = true })
	var callback = function () { if (!called) $($el).trigger($.support.transition.end) }
	setTimeout(callback, duration)
	return this
  }
  $.support.transition = transitionEnd()  
});
