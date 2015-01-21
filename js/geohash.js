str_pad = function(input, pad_length, pad_string, pad_type) {
  var half = '',
    pad_to_go;
  var str_pad_repeater = function(s, len) {
    var collect = '',
      i;
    while (collect.length < len) {
      collect += s;
    }
    collect = collect.substr(0, len);
    return collect;
  };
  input += '';
  pad_string = pad_string !== undefined ? pad_string : ' ';
  if (pad_type !== 'STR_PAD_LEFT' && pad_type !== 'STR_PAD_RIGHT' && pad_type !== 'STR_PAD_BOTH') {
    pad_type = 'STR_PAD_RIGHT';
  }
  if ((pad_to_go = pad_length - input.length) > 0) {
    if (pad_type === 'STR_PAD_LEFT') {
      input = str_pad_repeater(pad_string, pad_to_go) + input;
    } else if (pad_type === 'STR_PAD_RIGHT') {
      input = input + str_pad_repeater(pad_string, pad_to_go);
    } else if (pad_type === 'STR_PAD_BOTH') {
      half = str_pad_repeater(pad_string, Math.ceil(pad_to_go / 2));
      input = half + input + half;
      input = input.substr(0, pad_length);
    }
  }
  return input;
};
decbin = function(number) {
  if (number < 0) {
    number = 0xFFFFFFFF + number + 1;
  }
  return parseInt(number, 10)
    .toString(2);
};
bindec = function(binary_string) {
  binary_string = (binary_string + '')
    .replace(/[^01]/gi, '');
  return parseInt(binary_string, 2);
};

geohash = (function(){
	var THIS = this;
	THIS.coding="0123456789bcdefghjkmnpqrstuvwxyz";
	THIS.codingMap={};
	for(var i=0; i<32; i++){
		THIS.codingMap[THIS.coding.substr(i,1)]=str_pad(decbin(i), 5, "0", 'STR_PAD_LEFT');
	}
	THIS.decode = function(hash){
		var binary="";
		var hl=strlen(hash);
		for(var i=0; i<hl; i++){
			binary+=THIS.codingMap[hash.substr(i,1)];
		}
		var bl=binary.length;
		var blat="";
		var blong="";
		for(var i=0; i<bl; i++){
			if (i%2)
				blat=blat+binary.substr(i,1);
			else
				blong=blong+binary.substr(i,1);
		}
		var lat=THIS.binDecode(blat,-90,90);
		var long=THIS.binDecode(blong,-180,180);
		var latErr=THIS.calcError(blat.length,-90,90);
		var longErr=THIS.calcError(blong.length,-180,180);
		var latPlaces=Math.max(1, -Math.round(Math.log10(latErr))) - 1;
		var longPlaces=Math.max(1, -Math.round(Math.log10(longErr))) - 1;
		lat=Math.round(lat, latPlaces);
		long=Math.round(long, longPlaces);
		return [lat,long];
	};
	THIS.encode = function(lat,long){
		if(typeof(lat)=='string'){
			lat = parseFloat(lat.replace(',','.'));
		}
		if(typeof(long)=='string'){
			long = parseFloat(long.replace(',','.'));
		}
		var plat = THIS.precision(lat);
		var latbits=1;
		var err=45;
		while(err>plat){
			latbits++;
			err/=2;
		}
		var plong=THIS.precision(long);
		var longbits=1;
		err=90;
		while(err>plong){
			longbits++;
			err/=2;
		}
		var bits=Math.max(latbits,longbits);
		longbits=bits;
		latbits=bits;
		var addlong=1;
		while((longbits+latbits)%5 != 0){
			longbits+=addlong;
			latbits+=!addlong;
			addlong=!addlong;
		}
		var blat=THIS.binEncode(lat,-90,90,latbits);
		var blong=THIS.binEncode(long,-180,180,longbits);
		var binary="";
		var uselong=1;
		while(blat.length+blong.length){
			if (uselong){
				binary=binary+blong.substr(0,1);
				blong=blong.substr(1);
			}
			else{
				binary=binary+blat.substr(0,1);
				blat=blat.substr(1);
			}
			uselong=!uselong;
		}
		var hash="",n;
		for (var i=0; i<binary.length; i+=5){
			n=bindec(binary.substr(i,5));
			hash=hash+THIS.coding[n];
		}
		return hash;
	};
	THIS.calcError = function(bits,min,max){
		var err=(max-min)/2;
		while (bits--)
			err/=2;
		return err;
	};
	THIS.precision = function(number){
		number = number.toString();
		var precision=0;
		var pt=number.indexOf('.');
		if(pt!==-1){
			precision = -(number.length-pt-1);
		}
		return Math.pow(10,precision)/2;
	};
	THIS.binEncode = function(number, min, max, bitcount){
		if(bitcount==0)
			return "";
		var mid=(min+max)/2;
		if (number>mid)
			return "1"+THIS.binEncode(number, mid, max,bitcount-1);
		else
			return "0"+THIS.binEncode(number, min, mid,bitcount-1);
	};
	THIS.binDecode = function(binary, min, max){
		var mid=(min+max)/2;
		if (binary.length==0)
			return mid;
		var bit=binary.substr(0,1);
		var binary=binary.substr(1);
		if (bit==1)
			return THIS.binDecode(binary, mid, max);
		else
			return THIS.binDecode(binary, min, mid);
	};
	return THIS;
})();