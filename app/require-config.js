(function(){
	
	const main = ['app/app'];
	
	const dev = APP_DEV_MODE;
	
	//alias map
	const alias = {
		
	};
	
	//combined dependencies
	const combined = {
		
	};
	
	//dependencies map
	const dependencies = {
		
		
		
	};
	
	
	if(!dev){
		
		//don't add min suffix for
		const dontMin = [
			
		];
		
		Object.keys(paths).forEach(function(name){
			if(dontMin.indexOf(name)===-1&&dontMin.indexOf(paths[name])===-1){
				paths[name] += '.min';
			}
		});
		
	}
	
	require.config({
		paths   : alias,
		shim    : dependencies,
		urlArgs : dev?'_='+(new Date()).getTime():'',
	});
	
	Object.keys(combined).forEach(function(name){
		define(name, combined[name], function(){
			return arguments;
		});
	});
	
	require(main);
	
})();
