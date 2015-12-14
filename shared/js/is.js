$is = (function(d,w){
	w.getElementsByXDom = function(){
		var elArray = [];
		var tmp = d.getElementsByTagName('*');
		var regex	= new RegExp('(?:[a-z][a-z]+)-(?:[a-z][a-z]+)',['i']);
		for(var i=0;i<tmp.length;i++)
			if (tmp[i].getAttribute('is')||(tmp[i].tagName&&regex.test(tmp[i].tagName)))
				elArray.push(tmp[i]);
		return elArray;
	};
	var indexOf = Array.prototype.indexOf?function(a,obj,start){
		return a.indexOf(obj,start);
	}:function(a,obj, start){
		var j = a.length;
		for (var i = (start?start:0), j; i < j; i++)
			if(a[i]===obj)
				return i;
		return -1;
	};
	return function(){
		var els = w.getElementsByXDom();
		var src = [];
		for(var k in els){
			var is = els[k].getAttribute('is');
			if(!is)
				is = els[k].tagName;
			var u = 'is.'+is.toLowerCase();
			if(indexOf(src,u)<0)
				src.push(u);
		}
		$js(src);
	};
})(document,window);
$is();