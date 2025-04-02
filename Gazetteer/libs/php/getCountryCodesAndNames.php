<?php

$geoJsonData = file_get_contents('../../json/countryBorders.geo.json');
$geoJson = json_decode($geoJsonData, true);

$countryList = [];

foreach ($geoJson['features'] as $feature) {
    $countryList[] = [
        'code' => $feature['properties']['iso_a2'],
        'name' => $feature['properties']['name']
    ];
}

usort($countryList, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

echo json_encode($countryList);

?>
