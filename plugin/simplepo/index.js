
var local_names = {"aa":"Afar","ab":"Abkhazian","af":"Afrikaans","am":"Amharic","ar":"Arabic","as":"Assamese","ay":"Aymara","az":"Azerbaijani","ba":"Bashkir","be":"Byelorussian","bg":"Bulgarian","bh":"Bihari","bi":"Bislama","bn":"Bengali;","bo":"Tibetan","br":"Breton","ca":"Catalan","co":"Corsican","cs":"Czech","cy":"Welsh","da":"Danish","de":"German","dz":"Bhutani","el":"Greek","en":"English","eo":"Esperanto","es":"Spanish","et":"Estonian","eu":"Basque","fa":"Persian","fi":"Finnish","fj":"Fiji","fo":"Faroese","fr":"French","fy":"Frisian","ga":"Irish","gd":"Scots","gl":"Galician","gn":"Guarani","gu":"Gujarati","ha":"Hausa","he":"Hebrew","hi":"Hindi","hr":"Croatian","hu":"Hungarian","hy":"Armenian","ia":"Interlingua","id":"Indonesian","ie":"Interlingue","ik":"Inupiak","is":"Icelandic","it":"Italian","iu":"Inuktitut","ja":"Japanese","jw":"Javanese","ka":"Georgian","kk":"Kazakh","kl":"Greenlandic","km":"Cambodian","kn":"Kannada","ko":"Korean","ks":"Kashmiri","ku":"Kurdish","ky":"Kirghiz","la":"Latin","ln":"Lingala","lo":"Laothian","lt":"Lithuanian","lv":"Latvian,","mg":"Malagasy","mi":"Maori","mk":"Macedonian","ml":"Malayalam","mn":"Mongolian","mo":"Moldavian","mr":"Marathi","ms":"Malay","mt":"Maltese","my":"Burmese","na":"Nauru","ne":"Nepali","nl":"Dutch","no":"Norwegian","oc":"Occitan","om":"(Afan)","or":"Oriya","pa":"Punjabi","pl":"Polish","ps":"Pashto,","pt":"Portuguese","qu":"Quechua","rm":"Rhaeto-Romance","rn":"Kirundi","ro":"Romanian","ru":"Russian","rw":"Kinyarwanda","sa":"Sanskrit","sd":"Sindhi","sg":"Sangho","sh":"Serbo-Croatian","si":"Sinhalese","sk":"Slovak","sl":"Slovenian","sm":"Samoan","sn":"Shona","so":"Somali","sq":"Albanian","sr":"Serbian","ss":"Siswati","st":"Sesotho","su":"Sundanese","sv":"Swedish","sw":"Swahili","ta":"Tamil","te":"Telugu","tg":"Tajik","th":"Thai","ti":"Tigrinya","tk":"Turkmen","tl":"Tagalog","tn":"Setswana","to":"Tonga","tr":"Turkish","ts":"Tsonga","tt":"Tatar","tw":"Twi","ug":"Uighur","uk":"Ukrainian","ur":"Urdu","uz":"Uzbek","vi":"Vietnamese","vo":"Volapuk","wo":"Wolof","xh":"Xhosa","yi":"Yiddish","yo":"Yoruba","za":"Zhuang","zh":"Chinese","zu":"Zulu"}; /*Technical contents of ISO 639:1988 (E/F)*/

$.messageService = function(method, params, callback, error_handler){
	$.post('RPC',{
	  method:method, params:params, id:Math.random()
	  },function(obj){
	  if(obj.error){
		error_handler ? error_handler(obj.error) : $('#errors').text(obj.error.message);
	  }
	  else{
		callback(obj.result);
	  }
	}, "json");
};

$(function(){
	var $container = $('div.data');
	var init = function(){
		$.post('RPC',{method:'countPotMessages'},function(data){
			if(data.result)
				$('#counter').text('('+(data.result.message_count-1)+' messages)');
		},'json');
		$.messageService("getCatalogues", [], function(data){
		  var table = $('<table><tbody> </tbody></table>');
		  $container.html(table);
		  for (var i = 0; i<data.length; ++i){
			 var name = data[i].name;
			 var x = name.split('_');
			 if(typeof(x[0])!='undefined'&&typeof(local_names[x[0]])!='undefined'){
				name = local_names[x[0]];
				name += ' ( '+data[i].name+' )';
			 }
			 var html = ''
				  + '<tr><td>'
				  + '<a href="./edit?cat_id='+data[i].id+'" title="'+data[i].name+'">' + name + '</a> '
				  + '</td><td>'
				  + '<div class="progressbar"> <div class="inner"> </div> </div>'
				  + '</td><td>'
				  + '<span class="percent"></span> - <span class="translated" ></span> of <span class="total"></span>'
				  + '</td><td>'
				  + '<button class="update_cat" data-id="'+data[i].id+'">update</button>'
				  + '</td><td>'
				  + '<button class="compile_cat" data-id="'+data[i].id+'">compile</button>'
				  + '</td></tr>';
		 
				table.find('tbody').append(html);
				var $row = table.find('tr:last');
				
				var updater;
				updater = function(cid,t){
					$.post('RPC',{method:'importCatalogue',cid:cid,atline:t},function(data){
						if(data.result.timeout){
							updater(cid,data.result.timeout);
							$('.atline').text('parsing line: '+data.result.timeout);
						}
						else{
							$('.atline').remove();
							init();
							$('#cat_box').css('opacity',1);
						}
					},'json');					
				};
				
				$row.find('button.update_cat').click(function(){
					$('#cat_box').css('opacity',0.2);
					$('body').append('<div class="atline" style="z-index:10;position:absolute;top:0;left:0;"></div>');
					updater($(this).attr('data-id'),false);
				});
				$row.find('button.compile_cat').click(function(){
					$('body').css('opacity',0.2);
					$.post('RPC',{method:'exportCatalogue',cid:$(this).attr('data-id')},function(data){
						init();
						$('body').css('opacity',1);
					},'json');
				});
				
				$row.find('.inner')
				  .css( 'width',
					$row.find('.progressbar').width()  * 
					( data[i].translated_count / (data[i].message_count-1))
				  );
				
				$row.find('.translated').text(data[i].translated_count);
				$row.find('.total').text(data[i].message_count-1);
				var percent = (data[i].translated_count!='0'?parseInt(data[i].translated_count / (data[i].message_count-1) *100):'0') + " %";
				$row.find('.percent').text(percent);
			}
			
		});
	};
	init();
	$('#makepot').click(function(e){
		e.preventDefault();
		$('body').css('opacity',0.2);
		$.post('RPC',{method:'makePot'},function(data){
			init();
			$('body').css('opacity',1);
		},'json');
		return false;
	});
	

});