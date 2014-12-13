//ISO 639-1 (alpha2)
var local_names = {"aa":"Afaraf","ab":"Аҧсуа","ae":"Avesta","af":"Afrikaans","ak":"Akan","am":"አማርኛ","an":"Aragonés","ar":"‫العربية","as":"অসমীয়া","av":"авар мацӀ","ay":"Aymar aru","az":"Azərbaycan dili","ba":"башҡорт теле","be":"Беларуская","bg":"български език","bh":"भोजपुरी","bi":"Bislama","bm":"Bamanankan","bn":"বাংলা","bo":"བོད་ཡིག","br":"Brezhoneg","bs":"Bosanski jezik","ca":"Català","ce":"нохчийн мотт","ch":"Chamoru","co":"Corsu","cr":"ᓀᐦᐃᔭᐍᐏᐣ","cs":"Česky","cu":"Словѣньскъ","cv":"чӑваш чӗлхи","cy":"Cymraeg","da":"Dansk","de":"Deutsch","dv":"‫ދިވެހި","dz":"རྫོང་ཁ","ee":"Ɛʋɛgbɛ","el":"Ελληνικά","en":"English","eo":"Esperanto","es":"Español","et":"Eesti keel","eu":"Euskara","fa":"‫فارسی","ff":"Fulfulde","fi":"Suomen kieli","fj":"Vosa Vakaviti","fo":"Føroyskt","fr":"Français","fy":"Frysk","ga":"Gaeilge","gd":"Gàidhlig","gl":"Galego","gn":"Avañe'ẽ","gu":"ગુજરાતી","gv":"Ghaelg","ha":"‫هَوُسَ","he":"‫עברית","hi":"हिन्दी","ho":"Hiri Motu","hr":"Hrvatski","ht":"Kreyòl ayisyen","hu":"magyar","hy":"Հայերեն","hz":"Otjiherero","ia":"Interlingua","id":"Bahasa Indonesia","ie":"Interlingue","ig":"Igbo","ii":"ꆇꉙ","ik":"Iñupiaq","io":"Ido","is":"Íslenska","it":"Italiano","iu":"ᐃᓄᒃᑎᑐᑦ","ja":"日本語 (にほんご)","jv":"Basa Jawa","ka":"ქართული","kg":"KiKongo","ki":"Gĩkũyũ","kj":"Kuanyama","kk":"Қазақ тілі","kl":"Kalaallisut","km":"ភាសាខ្មែរ","kn":"ಕನ್ನಡ","ko":"한국어 (韓國語)","kr":"Kanuri","ks":"कश्मीरी","ku":"Kurdî","kv":"коми кыв","kw":"Kernewek","ky":"кыргыз тили","la":"Latine","lb":"Lëtzebuergesch","lg":"Luganda","li":"Limburgs","ln":"Lingála","lo":"ພາສາລາວ","lt":"Lietuvių kalba","lu":"kiluba","lv":"Latviešu valoda","mg":"Fiteny malagasy","mh":"Kajin M̧ajeļ","mi":"Te reo Māori","mk":"македонски јазик","ml":"മലയാളം","mn":"Монгол","mo":"лимба молдовеняскэ","mr":"मराठी","ms":"Bahasa Melayu","mt":"Malti","my":"ဗမာစာ","na":"Ekakairũ Naoero","nb":"Norsk bokmål","nd":"isiNdebele","ne":"नेपाली","ng":"Owambo","nl":"Nederlands","nn":"Norsk nynorsk","no":"Norsk","nr":"Ndébélé","nv":"Diné bizaad","ny":"ChiCheŵa","oc":"Occitan","oj":"ᐊᓂᔑᓈᐯᒧᐎᓐ","om":"Afaan Oromoo","or":"ଓଡ଼ିଆ","os":"Ирон æвзаг","pa":"ਪੰਜਾਬੀ","pi":"पािऴ","pl":"Polski","ps":"‫پښتو","pt":"Português","qu":"Runa Simi","rm":"Rumantsch grischun","rn":"kiRundi","ro":"Română","ru":"русский язык","rw":"Kinyarwanda","sa":"संस्कृतम्","sc":"sardu","sd":"सिन्धी","se":"Davvisámegiella","sg":"Yângâ tî sängö","si":"සිංහල","sk":"Slovenčina","sl":"Slovenščina","sm":"Gagana fa'a Samoa","sn":"chiShona","so":"Soomaaliga","sq":"Shqip","sr":"српски језик","ss":"SiSwati","st":"seSotho","su":"Basa Sunda","sv":"Svenska","sw":"Kiswahili","ta":"தமிழ்","te":"తెలుగు","tg":"тоҷикӣ","th":"ไทย","ti":"ትግርኛ","tk":"Türkmen","tl":"Tagalog","tn":"seTswana","to":"faka Tonga","tr":"Türkçe","ts":"xiTsonga","tt":"татарча","tw":"Twi","ty":"Reo Mā`ohi","ug":"Uyƣurqə","uk":"українська мова","ur":"‫اردو","uz":"O'zbek","ve":"tshiVenḓa","vi":"Tiếng Việt","vo":"Volapük","wa":"Walon","wo":"Wollof","xh":"isiXhosa","yi":"‫ייִדיש","yo":"Yorùbá","za":"Saɯ cueŋƅ","zh":"中文, 汉语, 漢語","zu":"isiZulu"};
//ISO 639-1 (alpha2) in english
//var local_names = {"aa":"Afar","ab":"Abkhazian","ae":"Avestan","af":"Afrikaans","ak":"Akan","am":"Amharic","an":"Aragonese","ar":"Arabic","as":"Assamese","av":"Avaric","ay":"Aymara","az":"Azerbaijani","ba":"Bashkir","be":"Belarusian","bg":"Bulgarian","bh":"Bihari","bi":"Bislama","bm":"Bambara","bn":"Bengali","bo":"Tibetan","br":"Breton","bs":"Bosnian","ca":"Catalan","ce":"Chechen","ch":"Chamorro","co":"Corsican","cr":"Cree","cs":"Czech","cu":"Old Church Slavonic","cv":"Chuvash","cy":"Welsh","da":"Danish","de":"German","dv":"Divehi","dz":"Dzongkha","ee":"Ewe","el":"Greek","en":"English","eo":"Esperanto","es":"Spanish","et":"Estonian","eu":"Basque","fa":"Persian","ff":"Fulah","fi":"Finnish","fj":"Fijian","fo":"Faroese","fr":"French","fy":"Western Frisian","ga":"Irish","gd":"Scottish Gaelic","gl":"Galician","gn":"Guarani","gu":"Gujarati","gv":"Manx","ha":"Hausa","he":"Hebrew","hi":"Hindi","ho":"Hiri Motu","hr":"Croatian","ht":"Haitian","hu":"Hungarian","hy":"Armenian","hz":"Herero","ia":"Interlingua","id":"Indonesian","ie":"Interlingue","ig":"Igbo","ii":"Sichuan Yi","ik":"Inupiaq","io":"Ido","is":"Icelandic","it":"Italian","iu":"Inuktitut","ja":"Japanese","jv":"Javanese","ka":"Georgian","kg":"Kongo","ki":"Kikuyu","kj":"Kwanyama","kk":"Kazakh","kl":"Kalaallisut","km":"Khmer","kn":"Kannada","ko":"Korean","kr":"Kanuri","ks":"Kashmiri","ku":"Kurdish","kv":"Komi","kw":"Cornish","ky":"Kirghiz","la":"Latin","lb":"Luxembourgish","lg":"Ganda","li":"Limburgish","ln":"Lingala","lo":"Lao","lt":"Lithuanian","lu":"Luba-Katanga","lv":"Latvian","mg":"Malagasy","mh":"Marshallese","mi":"Māori","mk":"Macedonian","ml":"Malayalam","mn":"Mongolian","mo":"Moldavian","mr":"Marathi","ms":"Malay","mt":"Maltese","my":"Burmese","na":"Nauru","nb":"Norwegian Bokmål","nd":"North Ndebele","ne":"Nepali","ng":"Ndonga","nl":"Dutch","nn":"Norwegian Nynorsk","no":"Norwegian","nr":"South Ndebele","nv":"Navajo","ny":"Chichewa","oc":"Occitan","oj":"Ojibwa","om":"Oromo","or":"Oriya","os":"Ossetian","pa":"Panjabi","pi":"Pāli","pl":"Polish","ps":"Pashto","pt":"Portuguese","qu":"Quechua","rm":"Romansh","rn":"Kirundi","ro":"Romanian","ru":"Russian","rw":"Kinyarwanda","sa":"Sanskrit","sc":"Sardinian","sd":"Sindhi","se":"Northern Sami","sg":"Sango","si":"Sinhalese","sk":"Slovak","sl":"Slovene","sm":"Samoan","sn":"Shona","so":"Somali","sq":"Albanian","sr":"Serbian","ss":"Swati","st":"Sotho","su":"Sundanese","sv":"Swedish","sw":"Swahili","ta":"Tamil","te":"Telugu","tg":"Tajik","th":"Thai","ti":"Tigrinya","tk":"Turkmen","tl":"Tagalog","tn":"Tswana","to":"Tonga","tr":"Turkish","ts":"Tsonga","tt":"Tatar","tw":"Twi","ty":"Tahitian","ug":"Uighur","uk":"Ukrainian","ur":"Urdu","uz":"Uzbek","ve":"Venda","vi":"Viêt Namese","vo":"Volapük","wa":"Walloon","wo":"Wolof","xh":"Xhosa","yi":"Yiddish","yo":"Yoruba","za":"Zhuang","zh":"Chinese","zu":"Zulu"};

//ISO 639-1 (alpha2) in french
//var local_names = {"aa":"Afar","ab":"Abkhaze","ae":"Avestique","af":"Afrikaans","ak":"Akan","am":"Amharique","an":"Aragonais","ar":"Arabe","as":"Assamais","av":"Avar","ay":"Aymara","az":"Azéri","ba":"Bachkir","be":"Biélorusse","bg":"Bulgare","bh":"Bihari","bi":"Bichelamar","bm":"Bambara","bn":"Bengalî","bo":"Tibétain","br":"Breton","bs":"Bosnien","ca":"Catalan","ce":"Tchétchène","ch":"Chamorro","co":"Corse","cr":"Cri","cs":"Tchèque","cu":"Vieux slave","cv":"Tchouvache","cy":"Gallois","da":"Danois","de":"Allemand","dv":"Dhivehi","dz":"Dzongkha","ee":"Ewe","el":"Grec moderne","en":"Anglais","eo":"Espéranto","es":"Espagnol","et":"Estonien","eu":"Basque","fa":"Persan","ff":"Peul","fi":"Finnois","fj":"Fidjien","fo":"Féringien","fr":"Français","fy":"Frison","ga":"Irlandais","gd":"Écossais","gl":"Galicien","gn":"Guarani","gu":"Gujarâtî","gv":"Mannois","ha":"Haoussa","he":"Hébreu","hi":"Hindî","ho":"Hiri motu","hr":"Croate","ht":"Créole haïtien","hu":"Hongrois","hy":"Arménien","hz":"Héréro","ia":"Interlingua","id":"Indonésien","ie":"Occidental","ig":"Igbo","ii":"Yi","ik":"Inupiaq","io":"Ido","is":"Islandais","it":"Italien","iu":"Inuktitut","ja":"Japonais","jv":"Javanais","ka":"Géorgien","kg":"Kikongo","ki":"Kikuyu","kj":"Kuanyama","kk":"Kazakh","kl":"Kalaallisut","km":"Khmer","kn":"Kannara","ko":"Coréen","kr":"Kanouri","ks":"Kashmiri","ku":"Kurde","kv":"Komi","kw":"Cornique","ky":"Kirghiz","la":"Latin","lb":"Luxembourgeois","lg":"Ganda","li":"Limbourgeois","ln":"Lingala","lo":"Lao","lt":"Lituanien","lu":"Luba-katanga","lv":"Letton","mg":"Malgache","mh":"Marshallais","mi":"Maori de Nouvelle-Zélande","mk":"Macédonien","ml":"Malayalam","mn":"Mongol","mo":"Moldave","mr":"Marâthî","ms":"Malais","mt":"Maltais","my":"Birman","na":"Nauruan","nb":"Norvégien Bokmål","nd":"Ndébélé du Nord","ne":"Népalais","ng":"Ndonga","nl":"Néerlandais","nn":"Norvégien Nynorsk","no":"Norvégien","nr":"Ndébélé du Sud","nv":"Navajo","ny":"Chichewa","oc":"Occitan","oj":"Ojibwé","om":"Oromo","or":"Oriya","os":"Ossète","pa":"Panjâbî","pi":"Pâli","pl":"Polonais","ps":"Pachto","pt":"Portugais","qu":"Quechua","rm":"Romanche","rn":"Kirundi","ro":"Roumain","ru":"Russe","rw":"Kinyarwanda","sa":"Sanskrit","sc":"Sarde","sd":"Sindhi","se":"Same du Nord","sg":"Sango","si":"Cingalais","sk":"Slovaque","sl":"Slovène","sm":"Samoan","sn":"Shona","so":"Somali","sq":"Albanais","sr":"Serbe","ss":"Siswati","st":"Sotho du Sud","su":"Soundanais","sv":"Suédois","sw":"Swahili","ta":"Tamoul","te":"Télougou","tg":"Tadjik","th":"Thaï","ti":"Tigrinya","tk":"Turkmène","tl":"Tagalog","tn":"Tswana","to":"Tongien","tr":"Turc","ts":"Tsonga","tt":"Tatar","tw":"Twi","ty":"Tahitien","ug":"Ouïghour","uk":"Ukrainien","ur":"Ourdou","uz":"Ouzbek","ve":"Venda","vi":"Vietnamien","vo":"Volapük","wa":"Wallon","wo":"Wolof","xh":"Xhosa","yi":"Yiddish","yo":"Yoruba","za":"Zhuang","zh":"Chinois","zu":"Zoulou"};


//console.log(Object.keys(local_names).length);

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
	var flags = $('.flags');
	for(k in local_names){
		//flags.append('<a class="flag '+k+'">'+local_names[k]+'</a>');
		flags.append('<a href="?lg='+k+'"><img width="16" height="16" src="../../img/langs/'+k+'.png" /> '+local_names[k]+'</a>');
	}
	
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