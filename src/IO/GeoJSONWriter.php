<?php

declare(strict_types = 1);

namespace Brick\Geo\IO;

use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Geometry;
use Brick\Geo\GeometryCollection;

/**
 * Converter class from Geometry to GeoJSON.
 */
class GeoJSONWriter
{
    /**
     * @var bool
     */
    private $prettyPrint;

    /**
     * GeoJSONWriter constructor.
     *
     * @param bool $prettyPrint Whether to pretty-print the JSON output.
     */
    public function __construct(bool $prettyPrint = false)
    {
        $this->prettyPrint = $prettyPrint;
    }

    /**
     * Convert a geometry to RFC 7946 string.
     *
     * @param Geometry $geometry the geometry to export as GeoJSON
     *
     * @throws GeometryIOException if the given geometry cannot be exported as GeoJSON
     */
    public function write(Geometry $geometry): string
    {
        return $this->genGeoJSONString(
            $this->toArray($geometry)
        );
    }

    /**
     * Convert a geometry to RFC 7946 array.
     *
     * @param Geometry $geometry the geometry to export as GeoJSON
     *
     * @throws GeometryIOException if the given geometry cannot be exported as GeoJSON
     */
    public function toArray(Geometry $geometry): array
    {
        return self::isGeometricCollection($geometry)
        ? $this->writeFeatureCollection($geometry)
        : $this->formatGeoJSONGeometry($geometry);
    }

    public static function isGeometricCollection(Geometry $geometry): bool
    {
        return $geometry instanceof GeometryCollection
        // Filter out MultiPoint, MultiLineString and MultiPolygon
        && 'GeometryCollection' === $geometry->geometryType();
    }

    /**
     * @param Geometry $geometry
     *
     * @return array
     *
     * @throws GeometryIOException
     */
    private function formatGeoJSONGeometry(Geometry $geometry) : array
    {
        $geometryType = $geometry->geometryType();
        $validGeometries = [
            'Point',
            'MultiPoint',
            'LineString',
            'MultiLineString',
            'Polygon',
            'MultiPolygon'
        ];

        if (! in_array($geometryType, $validGeometries)) {
            throw GeometryIOException::unsupportedGeometryType($geometry->geometryType());
        }

        return [
            'type' => $geometryType,
            'coordinates' => $geometry->toArray()
        ];
    }

    /**
     * @param GeometryCollection $geometryCollection
     *
     *
     * @throws GeometryIOException
     */
    private function writeFeatureCollection(GeometryCollection $geometryCollection) : array
    {
        $geojsonArray = [
            'type' => 'FeatureCollection',
            'features' => []
        ];

        foreach ($geometryCollection->geometries() as $geometry) {
            $geojsonArray['features'][] = [
                'type' => 'Feature',
                'geometry' => $this->formatGeoJSONGeometry($geometry)
            ];
        }

        return $geojsonArray;
    }

    /**
     * @param array $geojsonArray
     *
     * @return string
     */
    private function genGeoJSONString(array $geojsonArray) : string
    {
        return json_encode($geojsonArray, $this->prettyPrint ? JSON_PRETTY_PRINT : 0);
    }
}
