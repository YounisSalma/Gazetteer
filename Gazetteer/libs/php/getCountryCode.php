<?php

$username = "ysalm";

$lat = isset($_POST['lat']) ? $_POST['lat'] : '';
$lng = isset($_POST['lng']) ? $_POST['lng'] : '';

if ($lat && $lng) {
    $url = "http://api.geonames.org/countryCodeJSON?lat={$lat}&lng={$lng}&username={$username}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['countryCode'])) {
        echo json_encode(['countryCode' => $data['countryCode']]);
    } else {
        echo json_encode(['error' => 'Unable to determine country code.']);
    }
} else {
    echo json_encode(['error' => 'Invalid coordinates.']);
}

?>
