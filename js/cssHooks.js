$js('jquery',function(){
	$.cssHooks.backgroundColor = {
		get: function(elem) {
			if (elem.currentStyle)
				var bg = elem.currentStyle["backgroundColor"];
			else if (window.getComputedStyle)
				var bg = document.defaultView.getComputedStyle(elem,
					null).getPropertyValue("background-color");
			if (bg.search("rgb") == -1)
				return bg;
			else {
				bg = bg.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
				function hex(x) {
					return ("0" + parseInt(x).toString(16)).slice(-2);
				}
				return "#" + hex(bg[1]) + hex(bg[2]) + hex(bg[3]);
			}
		}
	};
	$.cssHooks.color = {
		get: function(elem) {
			if (elem.currentStyle)
				var bg = elem.currentStyle["color"];
			else if (window.getComputedStyle)
				var bg = document.defaultView.getComputedStyle(elem,
					null).getPropertyValue("color");
			if (bg.search("rgb") == -1)
				return bg;
			else {
				bg = bg.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
				function hex(x) {
					return ("0" + parseInt(x).toString(16)).slice(-2);
				}
				return "#" + hex(bg[1]) + hex(bg[2]) + hex(bg[3]);
			}
		}
	};
});
