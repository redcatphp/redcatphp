<?php namespace surikat\control\Geocoder;
use surikat\control\Geocoder\Geocoder;
use surikat\control\Geocoder\HttpAdapter\CurlHttpAdapter;
use surikat\control\Geocoder\Provider\ChainProvider;
use surikat\control\Geocoder\Provider\FreeGeoIpProvider;
use surikat\control\Geocoder\Provider\HostIpProvider;
use surikat\control\Geocoder\Provider\GoogleMapsProvider;
use surikat\control\Geocoder\Provider\NominatimProvider;
use surikat\control\Geocoder\Provider\OpenStreetMapProvider;

class RadiusFinder{
	static function byAddress($val,&$lat=null,&$lon=null){
		$oLat = $lat;
		$oLon = $lon;
		$geocoder = new Geocoder();
		$adapter  = new CurlHttpAdapter();
		$chain    = new ChainProvider(array(
			new NominatimProvider($adapter),
			new OpenStreetMapProvider($adapter),
			new GoogleMapsProvider($adapter), //new GoogleMapsProvider($adapter, 'fr_FR', 'France', true),
			new FreeGeoIpProvider($adapter),
			new HostIpProvider($adapter),
			
		));
		$geocoder->registerProvider($chain);
		$geocode = $geocoder->geocode($val);
		$bounds = $geocode->getBounds();
		$lon = $geocode->getLongitude();
		$lat = $geocode->getLatitude();
		if(!($bounds&&$lon&&$lat)){
			$geocode = $geocoder->geocode(self::geocodeToAddr($geocode));
			$bounds = $geocode->getBounds();
			$lon = $geocode->getLongitude();
			$lat = $geocode->getLatitude();
		}
		if($bounds&&$lon&&$lat){
			return self::byBounds($bounds);
		}
		else{
			$lat = $oLat;
			$lon = $oLon;
			return 0;
		}
	}
	static function byBounds($bounds){
		return call_user_func_array(array('self','distance'),$bounds)/2.0;
	}
	static function distance($lat1, $lon1, $lat2, $lon2){
		$R = 6371.0; // Radius of the earth in km
		$dLat = ($lat2 - $lat1) * pi() / 180.0;  // deg2rad below
		$dLon = ($lon2 - $lon1) * pi() / 180.0;
		$a = 0.5 - cos($dLat)/2.0 + cos($lat1 * pi() / 180.0) * cos($lat2 * pi() / 180.0) * (1 - cos($dLon))/2;
		return $R * 2 * asin(sqrt($a));
	}
	static function geocodeToAddr($geocode,$keys=array(
			'streetNumber',
			'streetName',
			'cityDistrict',
			'city',
			'zipcode',
			'country',
			//'region',
			//'regionCode',
			//'countyCode',
			//'county',
	)){
		$addr = '';
		foreach($keys as $k){
			$m = 'get'.ucfirst($k);
			$t = trim($geocode->$m());
			if($t)
				$addr .= $t.',';
		}
		$addr = rtrim($addr,',');
		return $addr;
	}
}