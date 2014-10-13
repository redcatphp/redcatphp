$css('/x-dom/geo-completer');

$css('jquery-ui/core');
$css('jquery-ui/menu');
$css('jquery-ui/autocomplete');

$js(true,[
	'jquery',

	'jquery-ui/core',
	'jquery-ui/widget',
	'jquery-ui/menu',
	'jquery-ui/position',
	'jquery-ui/autocomplete',
	
	'string',
	'geocoding',
	
	'simulate'
],function(){
	var geocallbacks = [];
	$('geo-completer').each(function(){
		var THIS = $(this);
		var geolocal = $('.remodal',THIS);
		var inputLat = $('input.in-latitude',THIS);
		var inputLng = $('input.in-longitude',THIS);
		var inputRadius = $('input.in-radius',THIS);
		var inputGG = $('input.gg-maps',THIS);
			inputGG.after('<div class="map-wrapper"><div class="map-canvas"></div></div>');
		var confirmGG = $('.remodal-confirm',THIS);
		var theMAP = $('.map-canvas',THIS);
		var inputGeoname = $('input.geoname',THIS);

		var modal = $('[data-remodal-id=map]',THIS);
		var inputRadiusH = $('<input type="hidden">');
		var inputLatH = $('<input type="hidden">');
		var inputLngH = $('<input type="hidden">');
		inputLatH.appendTo(modal);
		inputLngH.appendTo(modal);
		inputRadiusH.appendTo(modal);
		
		inputGeoname.wrap('<div>');
		inputGeoname.autocomplete({
			selectFirst:true,
			autoFill:true,
			minLength: 0,
			source:function(request,response){
				$.ajax({
					type:'GET',
					dataType:'json',
					url:inputGeoname.attr('data-url'),
					data:{'term':request.term},
					success:function(j){
						var suggesting = [];
						for(var k in j){
							suggesting.push({
								label:j[k].name,
								value:j[k].name,
								lat:j[k].latitude,
								lon:j[k].longitude,
								radius:j[k].radius
							});
						}
						response(suggesting);
					},
					error:function(){
						response([]);
					}
				});
			},
			select:function(e,ui){
				inputLat.val(ui.item.lat);
				inputLng.val(ui.item.lon);
				inputRadius.val(ui.item.radius);
			},
			appendTo: inputGeoname.parent(),
			position: {
				my: 'left top-3',
				at: 'left bottom',
				collision: 'none'
			}
		});
		inputGeoname.on('focus',function(){
			inputGeoname.autocomplete('search',inputGeoname.val());
		});
		geocallbacks.push(function(){

			$(document).on("confirm","[data-remodal-id=map]",function(){
				inputGeoname.val(inputGG.val());
				inputLat.val(inputLatH.val());
				inputLng.val(inputLngH.val());
				inputRadius.val(inputRadiusH.val());
			});
			
			var geocoder = new google.maps.Geocoder();
			var autocompleteService = new google.maps.places.AutocompleteService();
			
			var params = <!--#include virtual="/service/autocomplete/geoinit" -->; //Pyrénées-Orientales Square
			//$.getJSON('service/autocomplete/geoinit',function(params){
			var bounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(params.southWestLatMainBound,params.southWestLngMainBound),
				new google.maps.LatLng(params.northEastLatMainBound,params.northEastLngMainBound)
			);
			var map = new google.maps.Map(theMAP.get(0),{
				zoom: 8,
				mapTypeId: google.maps.MapTypeId.HYBRID,
				center:new google.maps.LatLng(params.centerLatMainBound,params.centerLngMainBound)
			});
			//map.fitBounds(bounds);
			map.controls[google.maps.ControlPosition.TOP_LEFT].push(inputGG.get(0));
			var autocomplete = new google.maps.places.Autocomplete(inputGG.get(0),{
				bounds:bounds,
				region:params.region,
				componentRestrictions:{
					country:params.country
				},
				types: ['geocode']
			});
			var updatingGeocode = function(val){
				if(val){
					autocompleteService.getQueryPredictions({input:val,types:['geocode']},function(predictions, status){
						if(status==google.maps.places.PlacesServiceStatus.OK&&predictions.length){
							geocoder.geocode({address:predictions[0].description,bounds:bounds},function(results,status){
								if(status===google.maps.places.PlacesServiceStatus.OK){
									inputGG.val(results[0].formatted_address);
									theMAP.updatePlace(results[0],true);
								}
							});
						}
					});
				}
				else{
					map.fitBounds(bounds);
					inputLatH.val('');
					inputLngH.val('');
				}
			};
			var defaultMapZoom = 17;
			var updateAdresse = function(latLng,updateMark){
				geocoder.geocode({'latLng':latLng},function(results,status){
					if(status==google.maps.places.PlacesServiceStatus.OK){
						inputGG.val(results[0].formatted_address);
						theMAP.updatePlace(results[0],updateMark);
					}				
				});
			};				
			var updatePosition = function(){
				updateAdresse(new google.maps.LatLng(floatFromStr(inputLat.val()), floatFromStr(inputLng.val())),true);
			};
			var updateMarker = function(place){
				marker.setVisible(false);
				marker.setIcon({
				  url: typeof(place.icon)!='undefined'?place.icon:'img/geocode.png',
				  size: new google.maps.Size(71, 71),
				  origin: new google.maps.Point(0, 0),
				  anchor: new google.maps.Point(17, 34),
				  scaledSize: new google.maps.Size(35, 35)
				});
				marker.setPosition(place.geometry.location);
				marker.setVisible(true);
			};
			inputGG.keypress(function(e){
				if(e.which==13){
					e.preventDefault();
					inputGG.trigger('focus');
					inputGG.simulate('keydown',{keyCode:$.ui.keyCode.DOWN}).simulate('keydown',{keyCode:$.ui.keyCode.ENTER});
					return false;
				}
			});
			
			//autocomplete.bindTo('bounds', map);
			//var infowindow = new google.maps.InfoWindow();
			var marker = new google.maps.Marker({map: map,draggable:true});
			var circle = new google.maps.Circle({
			  //visible: false,
			  map: map,
			  fillColor: '#AA0000',
			  fillOpacity: 0.5,
			  strokeOpacity:1,
			  strokeWeight:1,
			  strokeColor:'#000',
			  editable:true
			});
			var setRadius = function(place){
				if(place&&place.geometry&&place.geometry.viewport&&(place.types[0]=="locality"||place.types[0]=="administrative_area_level_2")){
					var center = place.geometry.viewport.getCenter();
					var northEast = place.geometry.viewport.getNorthEast();
					var southWest = place.geometry.viewport.getSouthWest();
					//var lat = center.lat();
					//var lng = center.lng();
					//var r = (window.geocoding.getDistance(lat,lng,northEast.lat(),northEast.lng())+distance(lat,lng,southWest.lat(),southWest.lng()))/2.0;
					var r = (window.geocoding.getDistance(northEast.lat(),northEast.lng(),southWest.lat(),southWest.lng()))/2.0;
					inputRadiusH.val(r);
					circle.setRadius(r*1000.0);
					circle.bindTo('center', marker, 'position');
					circle.setVisible(true);
				}
				else{
					circle.setVisible(false);
					inputRadiusH.val('');
				}
			};
			theMAP.updatePlace = function(place,updateMark){
				setRadius(place);
				if(typeof(place)!='object'||!place.geometry)
					return;
				if(place.geometry.viewport){
					map.fitBounds(place.geometry.viewport);
				}
				else{
					map.setCenter(place.geometry.location);
					map.setZoom(defaultMapZoom);
				}
				if(updateMark)
					updateMarker(place);
				
				if(inputLatH.val()!=place.geometry.location.lat())
					inputLatH.val(place.geometry.location.lat());
				if(inputLngH.val()!=place.geometry.location.lng())
					inputLngH.val(place.geometry.location.lng());
			};
			
			google.maps.event.addListener(circle, 'radius_changed', function(){
				var val = circle.getRadius()/1000.0;
				if(val!=floatFromStr(inputRadiusH.val()))
					inputRadiusH.val(val);
			});
			google.maps.event.addListener(marker, 'dragstart', function(e){
				circle.setVisible(false);
			});
			google.maps.event.addListener(marker, 'dragend', function(e){
				var latLng = e.latLng;
				inputLatH.val(latLng.lat());
				inputLngH.val(latLng.lng());
				updateAdresse(latLng);
			});
			google.maps.event.addListener(map, 'dragend', function(){
				var center = map.getCenter();
				marker.setPosition(center);
				inputRadiusH.val('');
				circle.setVisible(false);
				inputLatH.val(center.lat());
				inputLngH.val(center.lng());
				geocoder.geocode({'latLng':center},function(results,status){
					if(status==google.maps.places.PlacesServiceStatus.OK){
						inputGG.val(results[0].formatted_address);
					}
				});
			});
			google.maps.event.addListener(autocomplete, 'place_changed', function(){
				var place = autocomplete.getPlace();
				if(typeof(place)=='object'&&place.geometry){
					if(place.geometry.viewport){
						var center = place.geometry.viewport.getCenter();
						inputLatH.val(center.lat());
						inputLngH.val(center.lng());
					}
					else{
						inputLatH.val(place.geometry.location.lat());
						inputLngH.val(place.geometry.location.lng());
					}
				}
				theMAP.updatePlace(place,true);
				
			});
			updatingGeocode(inputGeoname.val());
			$(document).on("open","[data-remodal-id=map]",function(){
				updatingGeocode(inputGeoname.val());
			});
			
		});
	});
	window.geocompleter = function(){
		for(var i in geocallbacks)
			geocallbacks[i]();
	};
	$(document).one("open","[data-remodal-id=map]",function(){
		$js('http://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&callback=geocompleter');
	});
	$js('remodal');
});