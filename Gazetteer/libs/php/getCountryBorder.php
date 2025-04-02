<?php

$selectedCountry = isset($_POST['countryName']) ? $_POST['countryName'] : '';
$geoJsonData = file_get_contents('../../json/countryBorders.geo.json');
$geoJson = json_decode($geoJsonData, true);

$bordersArray = [];

foreach ($geoJson['features'] as $feature) {
    if ($feature['properties']['name'] === $selectedCountry) {
        $bordersArray[] = $feature;
        break;
    }
}

echo json_encode(array('bordersArray' => $bordersArray));

?>