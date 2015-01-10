$js([
	'jquery',
	'local-storage'
],function(){
	var loginBTN = $('a[is=persona]'),
		logoutBTN = $('a.persona.logout');
	$.getJSON('service/persona/email',function(email){
		var currentUser = email;
		var loginCALL = function(e){
			return navigator.id.request({
				siteName: loginBTN.attr('data-sitename'), //Plain text name of your site to show in the login dialog. Unicode and whitespace are allowed, but markup is not.
				backgroundColor:loginBTN.attr('data-bgcolor'),
				//oncancel:function(){}, //invoked if the user refuses to share an identity with the site.
				//privacyPolicy:'/Politique-Confidentialité', //Must be served over SSL. The termsOfService parameter must also be provided. Absolute path or URL to the web site's privacy policy. If provided, then termsOfService must also be provided. When both termsOfService and privacyPolicy are given, the login dialog informs the user that, by continuing, "you confirm that you accept this site's Terms of Use and Privacy Policy." The dialog provides links to the the respective policies.
				//returnTo: window.location, //Absolute path to send new users to after they've completed email verification for the first time. The path must begin with '/'. This parameter only affects users who are certified by Mozilla's fallback Identity Provider. This value passed in should be a valid path which could be used to set window.location too.
				//siteLogo: '/img/logo.png', //Must be served over SSL. Absolute path to an image to show in the login dialog. The path must begin with '/'. Larger images will be scaled down to fit within 100x100 pixels.
				//termsOfService: 'Termes-Utilisation', Optional Must be served over SSL. The privacyPolicy parameter must also be provided. Absolute path or URL to the web site's terms of service. If provided, then privacyPolicy must also be provided. When both termsOfService and privacyPolicy are given, the login dialog informs the user that, by continuing, "you confirm that you accept this site's Terms of Use and Privacy Policy." The dialog provides links to the the respective policies. 
			});
		};
		var logonCALL = function(currentUser){
			localStorage.setItem('email',currentUser);
			$(document).data('persona.email',currentUser);
			$(document).trigger('persona.login');
			loginBTN.data('origin',loginBTN.html());
			loginBTN.html(currentUser);
			loginBTN.show();
			loginBTN.off('click',loginCALL);
			loginBTN.next('ul').removeClass('disabled');
			$js('md5',function(){
				var s = 24;
				loginBTN.append('<img src="http://www.gravatar.com/avatar/'+md5(currentUser)+'?s='+s+'" width="'+s+'" height="'+s+'" />');
			});
		};
		var logoffCALL = function(){
			currentUser = false;
			localStorage.removeItem('email');
			$(document).data('persona.email',false);
			$(document).trigger('persona.logout');
			loginBTN.html(loginBTN.data('origin'));
			loginBTN.next('ul').addClass('disabled');
			loginBTN.on('click',loginCALL);
		};
		var out;
		var init;
		var initPersona = function(launch){
			$js('https://login.persona.org/include.js',function(){
				navigator.id.watch({
					onlogin: function(as){
						if(!out){
							$.post('service/persona/login',{assertion:as},function(login){
								if(login.status==='okay')
									logonCALL(login.email);
							});
						}
					},
					onlogout: function(){
						if(out||!init){
							$.get('service/persona/logout',function(res){
								if(res=='ok'){
									logoffCALL();
									out = false;
								}
							});
						}
					},
					onready: function(){
						loginBTN.on('click',loginCALL);
						loginBTN.show();
						logoutBTN.on('click',function(){
							navigator.id.logout()
						});
						if(out)
							navigator.id.logout();
						if(launch)
							loginBTN.click();
						init = false;
					}
				});
			});
		};
		if(currentUser){
			logonCALL(currentUser);
			logoutBTN.on('click',function(){
				out = true;
				initPersona();
			});
		}
		else{
			init = true;
			loginBTN.on('click',function(){
				initPersona(true);
			}).show();
		}
	});
	$js('md5');
});