(function(w,d){	
	var indexOf = Array.prototype.indexOf?function(a,obj, start){
		return a.indexOf(obj,start);
	}:function(a,obj, start){
		var j = a.length;
		for (var i = (start?start:0), j; i < j; i++)
			if(a[i]===obj)
				return i;
		return -1;
	};
	var ts = (new Date().getTime()).toString();
	var cacheFix = function(fileName,dev,min,ext){
		if(dev){
			if(fileName.indexOf('://')<0&&fileName.indexOf('_t=')<0)
				fileName += (fileName.indexOf('?')<0?'?':'&')+'_t='+ts;
		}
		else if(min){
			if(fileName.indexOf('://')<0&&fileName.indexOf('.min.'+ext)<0&&fileName.indexOf('.'+ext)){
				var p = fileName.lastIndexOf('.'+ext);
				fileName = fileName.substr(0,p)+'.min'+fileName.substr(p);
			}
		}
		return fileName;
	};
	var scripts = [{},{}];
	var required = [];
	var handled = [];
	var requiring = {};
	var obj = {
		dev:false,
		async:true,
		preloaders:[],
		path:'js/',
		pathDetection:true,
		pathSuffix:'.js',
		min:true,
		config:function(k,v){
			if(arguments.length==2){
				obj[k] = v;
			}
			else if(arguments.length){
				if(typeof(k)=='object'){
					if(k instanceof Array)
						for(var i=0;i<k.length;i++)
							obj.config(k[i]);
					else
						for(var key in k)
							obj.config(key,k[key]);
				}
				else
					return obj[k];
			}
			else
				return obj;
		},
		start: function(){
			
		}
	};
	var wait = function(u){
		if(indexOf(handled,u)>-1)
			handle(u);
		else
			setTimeout(function(){
				wait(u);
			},100);
	};
	var handle = function(u){
		if(requiring[u])
			while(requiring[u].length)
				(requiring[u].shift())();
	};
	var getSrc = function(u){
		return u?(obj.path&&u.indexOf('://')<0&&u.indexOf('/')!==0&&(!obj.pathDetection||u.indexOf(obj.path)!=0)?(obj.path+u):u)+(obj.pathSuffix&&u.indexOf('://')<0&&(!obj.pathDetection||u.substr(u.length-obj.pathSuffix.length)!=obj.pathSuffix)?obj.pathSuffix:''):u;
	};
	var createScript = function(u){
		var s = d.createElement('script');
		d.type = 'text/javascript';
		d.body.appendChild(s);
		var callback = function(){
			required.push(u);
			handle(u);
			handled.push(u);
		};
		s.onload = callback;
		s.onreadystatechange = function(){if(callback&&(this.readyState=='complete'||this.readyState==='loaded')){callback();callback=null;}}; //old browsers
		s.setAttribute('async','async');
		s.src = cacheFix(u,obj.dev,obj.min,'js');
	};
	var x = function(u,c){
		if(!u){
			if(typeof(c)=='function')
				c();
			return;
		}
		u = getSrc(u);
		if(!requiring[u]){
			requiring[u] = [];
			createScript(u);
		}
		if(typeof(c)=='function')
			requiring[u].push(c);
		if(indexOf(handled,u)>-1)
			handle(u);
		else if(indexOf(required,u)>-1)
			wait(u);
	};
	var requiredGroups = [];
	var asyncArrayCall = function(uo,s,c,i){
		var u = [];
		for(var k in uo)
			u.push(getSrc(uo[k]));
		u = u.sort().toString();
		$js(s,function(){
			requiredGroups[i].push(getSrc(s));
			if(requiredGroups[i].sort().toString()==u)
				c();
		},0);
	};
	var asyncJsArray = function(u,c){
		if(c){
			requiredGroups.push([]);
			for(var i in u)
				asyncArrayCall(u,u[i],c,requiredGroups.length-1);
		}
		else{
			for(var i in u)
				$js(u[i],0);
		}
	};
	var syncJsArray = function(u,c){		
		var ev = '';
		for(var i in u.reverse())
			ev = '$js("'+u[i]+'"'+(ev?',function(){'+ev+'}':(c?',c':''))+',1);';
		eval(ev);
	};
	var asyncJsObject = function(u,c){
		if(c){
			requiredGroups.push([]);
			for(var k in u)
				asyncArrayCall(u,u[k],c,requiredGroups.length-1);
		}
		else{
			for(var k in u)
				$js(k,u[k],0);
		}
	};
	var syncJsObject = function(u,c){
		if(c){
			var a = [];
			for(var k in u)
				a.push(k);
			$js(a,function(){
				for(var k in u)
					if(u[k])
						u[k]();
				c();
			},1);
		}
		else{
			var ev = '';
			for(var k in u.reverse())
				ev = '$js("'+k+'"'+(ev?',function(){'+ev+'}':(u[k]?',u["'+k+'"]':''))+',1);';
			eval(ev);
		}
	};
	var apt = function(u,c,m){
		m = m?0:1;
		u = getSrc(u);
		if(!scripts[m][u])
			scripts[m][u] = [];
		if(typeof(c)=='function')
			scripts[m][u].push(c);
	};
	$js = function(){
		if(!arguments.length)
			return obj;
		var u,
			c,
			sync = !obj.async;
		for(var i in arguments){
			switch(typeof(arguments[i])){
				case 'boolean':
					sync = arguments[i];
				break;
				case 'function':
					c = arguments[i];
				break;
				case 'string':
				case 'object':
					u = arguments[i];
				break;
			}
		}
		if(typeof(u)=='object'){
			if(u instanceof Array){
				if(sync)
					syncJsArray(u,c);
				else
					asyncJsArray(u,c);
			}
			else{
				if(sync)
					syncJsObject(u,c);
				else
					asyncJsObject(u,c);
			}
		}
		else{
			if(typeof(u)=='function'){
				c = u;
				u = 0;
			}
			apt(u,c,!sync);
		}
		return function(){
			var a = arguments;
			return $js(u,function(){
				$js.apply(null,a);
			});
		};
	};
	var y = {};
	var loader = function(m,k){
		var s = scripts[m][k];
		if(!m){
			if(k){
				x(k,function(){
					for(var i in s)
						if(s[i])
							s[i]();
				});
			}
			else{
				for(var i in s)
					if(s[i])
						s[i]();
			}
		}
		else{
			if(!y[k])
				y[k] = [];
			y[k] = s;
		}
	};
	var keysOf = function(o){
		var a = [];
		for(var k in o)
			a.push(k);
		return a;
	};


	var css = {
		dev:false,
		path:'css/',
		pathDetection:true,
		pathSuffix:'.css',
		min:true,
		config:function(k,v){
			if(arguments.length==2){
				obj[k] = v;
			}
			else if(arguments.length){
				if(typeof(k)=='object'){
					if(k instanceof Array)
						for(var i=0;i<k.length;i++)
							obj.config(k[i]);
					else
						for(var key in k)
							obj.config(key,k[key]);
				}
				else
					return obj[k];
			}
			else
				return obj;
		}
	};
	var getHref = function(u){
		return u?(css.path&&u.indexOf('://')<0&&u.indexOf('/')!==0&&(!css.pathDetection||u.indexOf(css.path)!=0)?(css.path+u):u)+(css.pathSuffix&&u.indexOf('://')<0&&(!css.pathDetection||u.substr(u.length-css.pathSuffix.length)!=css.pathSuffix)?css.pathSuffix:''):u;
	};
	var loadedCSS = [];
	$css = function(fileName, media){
		if(typeof(fileName)=='undefined')
			return css;
		var test = fileName;
		fileName = getHref(fileName);
		if(indexOf(loadedCSS,fileName)<0){
			loadedCSS.push(fileName);
			var links = d.getElementsByTagName('link'), i = links.length, style;
			var exist = false;
			while(i--){ // check if not already loaded fixed in head
				if (links[i].href.indexOf(fileName) > -1)
					exist = true;
			}
			if(!exist){
				style = d.createElement('link');
				style.type = 'text/css';
				style.rel = 'stylesheet';
				if(media)
					style.media =  media;
				style.href = cacheFix(fileName,css.dev,css.min,'css');
				d.getElementsByTagName('head')[0].appendChild(style);
			}
		}
		return css;
	};

	w.getElementsByXDom = function(){
		var elArray = [];
		var tmp = d.getElementsByTagName('*');
		var regex  = new RegExp('(?:[a-z][a-z]+)-(?:[a-z][a-z]+)',['i']);
		for(var i=0;i<tmp.length;i++)
			if (tmp[i].getAttribute('is')||(tmp[i].tagName&&regex.test(tmp[i].tagName)))
				elArray.push(tmp[i]);
		return elArray;
	};
	
	var load = function(){			
		apt = x;
		for(var i=0;i<obj.preloaders.length;i++){
			obj.preloaders[i]();
		}
		for(var k in scripts[0])
			loader(0,k);
		for(var k in scripts[1])
			loader(1,k);

		var ev = '';
		var keys = keysOf(y).reverse();
		for(var u in keys){
			u = keys[u];
			var keys2 = keysOf(y[u]).reverse();
			var ev2 = '';
			for(var i in keys2)
				if(y[u]&&y[u][i])
					ev2 += 'y["'+u+'"]["'+i+'"]();';
			ev = 'x("'+u+'"'+(ev? ',function(){'+ev2+ev+'}' :'')+');';
		}
		if(ev)
			eval(ev);
	};
	
	if(w.addEventListener)
		w.addEventListener('load',load,false);
	else if(w.attachEvent)
		w.attachEvent('onload',load);
	else
		w.onload=load;
})(window,document);
