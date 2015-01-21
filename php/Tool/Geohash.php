<?php namespace Surikat\Tool;
class GeoHash
{
    private static $table = "0123456789bcdefghjkmnpqrstuvwxyz";
    private static $bits = [
        0b10000, 0b01000, 0b00100, 0b00010, 0b00001
    ];
	
	static function precision($num){
		return (strlen(substr($num,strpos($num,'.')+1)))-1;
	}
	
    public static function encode($lng, $lat, $prec = null)
    {
		if(!isset($prec))
			$prec = min(self::precision($lat),self::precision($lng));
		$prec = pow(10,-$prec);
			
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;

        $hash = [];
        $error = 180;
        $isEven = true;
        $chr = 0b00000;
        $b = 0;

        while ($error >= $prec) {
            if ($isEven) {
                $next = ($minlng + $maxlng) / 2;
                if ($lng > $next) {
                    $chr |= self::$bits[$b];
                    $minlng = $next;
                } else {
                    $maxlng = $next;
                }
            } else {
                $next = ($minlat + $maxlat) / 2;
                if ($lat > $next) {
                    $chr |= self::$bits[$b];
                    $minlat = $next;
                } else {
                    $maxlat = $next;
                }
            }
            $isEven = !$isEven;

            if ($b < 4) {
                $b++;
            } else {
                $hash[] = self::$table[$chr];
                $error = max($maxlng - $minlng, $maxlat - $minlat);
                $b = 0;
                $chr = 0b00000;
            }
        }

        return join('', $hash);
    }

    public static function expand($hash)
    {
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);
        $dlng = ($maxlng - $minlng) / 2;
        $dlat = ($maxlat - $minlat) / 2;

        return [
            self::encode($minlng - $dlng, $maxlat + $dlat),
            self::encode($minlng + $dlng, $maxlat + $dlat),
            self::encode($maxlng + $dlng, $maxlat + $dlat),
            self::encode($minlng - $dlng, $maxlat - $dlat),
            self::encode($maxlng + $dlng, $maxlat - $dlat),
            self::encode($minlng - $dlng, $minlat - $dlat),
            self::encode($minlng + $dlng, $minlat - $dlat),
            self::encode($maxlng + $dlng, $minlat - $dlat),
        ];
    }

    public static function getRect($hash)
    {
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);

        return [
            [$minlng, $minlat],
            [$minlng, $maxlat],
            [$maxlng, $maxlat],
            [$maxlng, $minlat],
        ];
    }

    /**
     * decode a geohash string to a geographical area
     *
     * @var $hash string geohash
     * @return array [$minlng, $maxlng, $minlat, $maxlat];
     */
    public static function decode($hash,$prec=null)
    {
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;

        for ($i=0,$c=strlen($hash); $i<$c; $i++) {
            $v = strpos(self::$table, $hash[$i]);
            if (1&$i) {
                if (16&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (8&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (4&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (2&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (1&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
            } else {
                if (16&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (8&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (4&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (2&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (1&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
            }
        }
        if($prec){
			$minlng = round($minlng,$prec);
			$maxlng = round($maxlng,$prec);
			$minlat = round($minlat,$prec);
			$maxlat = round($maxlat,$prec);
		}
        return [$minlng, $maxlng, $minlat, $maxlat];
    }
}
