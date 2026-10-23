<?php
declare(strict_types=1);

/** Routing and distance services (OSRM): FR-23 to FR-25, later the 25 km check (FR-47). */
final class RouteService
{
    /** Salgas Junction, Mattegoda: start point and centre of the 25 km radius (FR-19, BR-01) */
    public const SALGAS_LAT = 6.8201772;
    public const SALGAS_LNG = 79.9691257;

    private const TIMEOUT_SECONDS = 10;   // FR-25: give up after 10 seconds
    public const MAX_RADIUS_KM   = 25;   // FR-47, BR-01: places must be within 25 km by road

    /** FR-47: road distance (km) and driving time (min) from Salgas Junction; null when OSRM fails (logged). */
    public static function fromStart(float $lat, float $lng): ?array
    {
        $route = self::route([[self::SALGAS_LAT, self::SALGAS_LNG], [$lat, $lng]], false);
        if ($route === null) {
            return null;
        }
        return [
            'distanceKm' => round($route['distance'] / 1000, 2),
            'travelMin'  => (int) round($route['duration'] / 60),
        ];
    }

    /**
     * FR-23, FR-24: the road route through the points, in the order given.
     *
     * @param array<int, array{0: float, 1: float}> $points  [lat, lng] pairs, start point first
     * @return array|null  distance in metres, duration in seconds, legs, geometry;
     *                     null when OSRM fails (the failure is logged)
     */
    public static function route(array $points, bool $withGeometry = true): ?array
    {
        if (count($points) < 2) {
            throw new InvalidArgumentException('A route needs at least two points.');
        }
        if (!function_exists('curl_init')) {
            log_error('OSRM: the PHP curl extension is not enabled.');
            return null;
        }

        // OSRM wants LONGITUDE first: "lng,lat;lng,lat"
        $coords = implode(';', array_map(
            fn(array $p): string => sprintf('%.6F,%.6F', $p[1], $p[0]),
            $points
        ));
        // The map needs the road shape; the 25 km check (FR-47) only needs the distance, which is a much smaller answer
        $overview = $withGeometry ? 'overview=full&geometries=geojson' : 'overview=false';
        $url = OSRM_URL . '/route/v1/driving/' . $coords . '?' . $overview;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,                    // give the body back as a string
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECONDS,   // whole request, FR-25
            CURLOPT_USERAGENT      => APP_NAME . ' (ITE2953 student project)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($ch);

        // NFR-24: every failed request to the routing service is logged
        if ($body === false) {
            log_error('OSRM request failed: ' . $error);
            return null;
        }
        $data = json_decode($body, true);
        if ($status !== 200 || !is_array($data) || ($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0])) {
            log_error(sprintf('OSRM returned HTTP %d, code "%s"', $status,
                is_array($data) ? ($data['code'] ?? 'none') : 'not JSON'));
            return null;
        }

        $route = $data['routes'][0];
        return [
            'distance' => (float) $route['distance'],
            'duration' => (float) $route['duration'],
            'legs'     => array_map(fn(array $leg): array => [
                'distance' => (float) $leg['distance'],
                'duration' => (float) $leg['duration'],
            ], $route['legs']),
            // GeoJSON is [lng, lat]; Leaflet wants [lat, lng]
            'geometry' => $withGeometry
                ? array_map(fn(array $c): array => [(float) $c[1], (float) $c[0]], $route['geometry']['coordinates'])
                : [],
        ];
    }
}