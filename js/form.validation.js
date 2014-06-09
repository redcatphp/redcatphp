$js('jquery',function(){
	<!--#include virtual="/js/validate.js" -->
	var onSubmit = function(e){
		e.preventDefault();
		var email = $(document).data('persona.email');
		var THIS = $(this);
		var submit = function(){
			THIS.off('submit',onSubmit).submit();
		};
		if(email){
			submit();
		}
		else{
			$(document).one('persona.login',submit);
			$('[is=persona]:first').click();
		}
		return false;
	};
	$('main form[action][id]').on('submit',onSubmit);
});
