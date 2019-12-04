<?php

namespace Overmob\NovaGmapsWkt;

use Laravel\Nova\Fields\Field;

class NovaGmapsWkt extends Field
{
    public $component = 'nova-gmaps-wkt';

    public function mapId($mapId)
    {
        return $this->withMeta([
            'mapId' => $mapId
        ]);
    }

    public function height($height = '300px')
    {
        return $this->withMeta([
            'height' => $height
        ]);
    }

    public function zoom($zoom = 13)
    {
        return $this->withMeta([
            'zoom' => $zoom
        ]);
    }

    public function latLng($lat, $lng)
    {
        return $this->withMeta([
            'lat' => $lat,
            'lng' => $lng
        ]);
    }

    public function apiKey($apiKey)
    {
        return $this->withMeta([
            'apiKey' => $apiKey
        ]);
    }

    public function style($drawStrokeColor = '#990000', $drawFillColor = '#EEFFCC', $drawFillOpacity = 0.6)
    {
        return $this->withMeta([
            'drawStrokeColor' => $drawStrokeColor,
            'drawFillColor' => $drawFillColor,
            'drawFillOpacity' => $drawFillOpacity
        ]);
    }

    // ['marker', 'circle', 'polygon', 'polyline', 'rectangle']
    public function drawingModes($drawingModes)
    {
        return $this->withMeta([
            'drawingModes' => $drawingModes
        ]);
    }

}
