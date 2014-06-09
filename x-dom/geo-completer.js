//https://developers.google.com/maps/documentation/javascript/reference
$css('/x-dom/geo-completer');
$js([
	'jquery',
	'simulate'
],function(){
	geocallback = function(){
		$('geo-completer').each(function(){
			var geocompleter = $(this);
			//$.getJSON('service/autocomplete/geoinit',function(params){
			var params = <!--#include virtual="/service/autocomplete/geoinit" -->;
			var defaultMapZoom = 17;
			var bounds = new google.maps.LatLngBounds( //Pyrénées-Orientales Square
				new google.maps.LatLng(params.southWestLatMainBound,params.southWestLngMainBound),
				new google.maps.LatLng(params.northEastLatMainBound,params.northEastLngMainBound)
			);
			var geocoder = new google.maps.Geocoder();
			var input_lat = geocompleter.find('input[type=number][step=any]:eq(0)');
			var input_lng = geocompleter.find('input[type=number][step=any]:eq(1)');
			var input_rayon = geocompleter.find('input[type=number][step][step!=any]:eq(0)');
			var input_validate = geocompleter.find('input[type=hidden]:eq(0)');
			var input = geocompleter.find('input[type=text]:eq(0)');
			var div_map = $('<div class="map-canvas"></div>');
			div_map.insertAfter(input);
			var distance = function(lat1, lon1, lat2, lon2){
				var R = 6371; // Radius of the earth in km
				var dLat = (lat2 - lat1) * Math.PI / 180;  // deg2rad below
				var dLon = (lon2 - lon1) * Math.PI / 180;
				var a = 0.5 - Math.cos(dLat)/2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * (1 - Math.cos(dLon))/2;
				return R * 2 * Math.asin(Math.sqrt(a));
			};
			var updateAdresse = function(latLng,updateMark){
				geocoder.geocode({'latLng':latLng},function(results,status){
					if(status=='OK'){
						input.val(results[0].formatted_address);
						updatePlace(results[0],updateMark);
					}				
				});
			};
			var floatFromStr = function(v){
				if(typeof(v)!='undefined')
					return parseFloat(v.replace(',','.'));
			};
			var updatePosition = function(){
				updateAdresse(new google.maps.LatLng(floatFromStr(input_lat.val()), floatFromStr(input_lng.val())),true);
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
			input.keypress(function(e){
				if(e.which==13){
					e.preventDefault();
					input.trigger('focus');
					input.simulate('keydown',{keyCode:$.ui.keyCode.DOWN}).simulate('keydown',{keyCode:$.ui.keyCode.ENTER});
					return false;
				}
			});
			input.on('input',function(){
				input_lat.val('');
				input_lng.val('');
				input_rayon.val('');
				input_validate.val('false');
			});
			input_lat.on('input',updatePosition);
			input_lng.on('input',updatePosition);
			input_rayon.on('input',function(){
				var val = $(this).val();
				if(val){
					circle.setRadius(floatFromStr(val)*1000.0);
					circle.bindTo('center', marker, 'position');
					circle.setVisible(true);
				}
				else{
					circle.setVisible(false);
				}
			});
			var map = new google.maps.Map(div_map.get(0),{ //https://developers.google.com/maps/documentation/javascript/reference?csw=1#MapOptions
				zoom: 8,
				mapTypeId: google.maps.MapTypeId.HYBRID
				/*			
					MapTypeId.ROADMAP displays the default road map view. This is the default map type.
					MapTypeId.SATELLITE displays Google Earth satellite images
					MapTypeId.HYBRID displays a mixture of normal and satellite views
					MapTypeId.TERRAIN displays a physical map based on terrain information. 
				*/,
				center:new google.maps.LatLng(params.centerLatMainBound,params.centerLngMainBound)
			});
			//map.fitBounds(bounds);
			map.controls[google.maps.ControlPosition.TOP_LEFT].push(input.get(0));
			var autocomplete = new google.maps.places.Autocomplete(input.get(0),{
				bounds:bounds,
				region:params.region,
				componentRestrictions:{
					country:params.country
				}
				/* type: //https://developers.google.com/places/documentation/supported_types */
			});
			//autocomplete.bindTo('bounds', map);
			var infowindow = new google.maps.InfoWindow();
			var marker = new google.maps.Marker({map: map,draggable:true});
			var circle = new google.maps.Circle({ //https://developers.google.com/maps/documentation/javascript/reference?csw=1#CircleOptions
			  //visible: false,
			  map: map,
			  fillColor: '#AA0000',
			  fillOpacity: 0.5,
			  strokeOpacity:1,
			  strokeWeight:1,
			  strokeColor:'#000',
			  editable:true
			});
			var setRayon = function(place){
				if(place&&place.geometry&&place.geometry.viewport&&(place.types[0]=="locality"||place.types[0]=="administrative_area_level_2")){
					var center = place.geometry.viewport.getCenter();
					var northEast = place.geometry.viewport.getNorthEast();
					var southWest = place.geometry.viewport.getSouthWest();
					var lat = center.lat();
					var lng = center.lng();
					var r = (distance(lat,lng,northEast.lat(),northEast.lng())+distance(lat,lng,southWest.lat(),southWest.lng()))/2.0;
					input_rayon.val(r);
					circle.setRadius(r*1000.0);
					circle.bindTo('center', marker, 'position');
					circle.setVisible(true);
				}
				else{
					circle.setVisible(false);
					input_rayon.val('');
				}
			};
			var updatePlace = function(place,updateMark){
				setRayon(place);
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
				//updateInfoWindow(place);
				input_validate.val('true');
				input.trigger('change');
			};
			//var autocompleteService = new google.maps.places.AutocompleteService();
			google.maps.event.addListener(circle, 'radius_changed', function(){
				var val = circle.getRadius()/1000.0;
				if(val!=floatFromStr(input_rayon.val()))
					input_rayon.val(val);
			});
			google.maps.event.addListener(marker, 'dragstart', function(e){
				circle.setVisible(false);
				infowindow.close();
			});
			google.maps.event.addListener(marker, 'dragend', function(e){
				var latLng = e.latLng;
				input_lat.val(latLng.lat());
				input_lng.val(latLng.lng());
				updateAdresse(latLng);
			});
			google.maps.event.addListener(map, 'dragend', function(){
				var center = map.getCenter();
				marker.setPosition(center);
				input_rayon.val('');
				circle.setVisible(false);
				input_lat.val(center.lat());
				input_lng.val(center.lng());
				map.setZoom(defaultMapZoom);
				geocoder.geocode({'latLng':center},function(results,status){
					if(status=='OK'){
						input.val(results[0].formatted_address);
						input_validate.val('true');
					}
				});
			});
			google.maps.event.addListener(autocomplete, 'place_changed', function(){
				var place = autocomplete.getPlace();
				if(typeof(place)=='object'&&place.geometry){
					if(place.geometry.viewport){
						var center = place.geometry.viewport.getCenter();
						input_lat.val(center.lat());
						input_lng.val(center.lng());
					}
					else{
						input_lat.val(place.geometry.location.lat());
						input_lng.val(place.geometry.location.lng());
					}
				}
				updatePlace(place,true);
				
			});
			var val = input.val();
			if(val){
				geocoder.geocode({'address':val},function(results,status){
					if(status==='OK'){
						input.val(results[0].formatted_address);
						updatePlace(results[0],true);
					}
				});
			}
		});
	};
	$js('http://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&callback=geocallback');
	/*
	$(window).on('unload',function(){
		//trying to resolve google map bug on unloading page that slow hard navigation
		//$(this).off('unload');
		if (window.google !== undefined && google.maps !== undefined){
			delete google.maps;
			$('script').each(function () {
				if (this.src.indexOf('googleapis.com/maps') >= 0
						|| this.src.indexOf('maps.gstatic.com') >= 0
						|| this.src.indexOf('earthbuilder.googleapis.com') >= 0) {
					// console.log('removed', this.src);
					$(this).remove();
				}
			});
		}
	});
	*/
});
