var $is = {
	prefix: 'is.',
	load: function(){
		var els = window.getElementsByXDom();
		var src = [];
		for(var k in els){
			var is = els[k].getAttribute('is');
			if(!is)
				is = els[k].tagName;
			var u = $is.prefix+is.toLowerCase();
			if(indexOf(src,u)<0)
				src.push(u);
		}
		$js(src,true);
	}
};
$js().preloaders.push($is.load);