<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Surikat\Geo\Geocoder\Dumper;

use Surikat\Geo\Geocoder\Result\ResultInterface;

/**
 * @author Jan Sorgalla <jsorgalla@googlemail.com>
 */
class GeoJsonDumper implements DumperInterface
{
    /**
     * @param ResultInterface $result
     *
     * @return string
     */
    public function dump(ResultInterface $result)
    {
        $properties = array_filter($result->toArray(), function ($val) {
            return $val !== null;
        });

        unset($properties['latitude'], $properties['longitude'], $properties['bounds']);

        if (count($properties) === 0) {
            $properties = null;
        }

        $json = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$result->getLongitude(), $result->getLatitude()]
            ],
            'properties' => $properties
        ];

        // Custom bounds property
        $bounds = $result->getBounds();

        if (null !== $bounds) {
            $json['bounds'] = $bounds;
        }

        return json_encode($json);
    }
}
