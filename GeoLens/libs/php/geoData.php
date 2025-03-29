<?php

$apiKeyOpenCage = "54767d55936a42a1930db339aba42c05";
$apiKeyOpenWeather = "a08d37000146d9ff770973d1d04db806";
$apiKeyOpenExchange = "a31a9f9bd8414556bbad8d8cfa370dca";
$apiKeyNewsData = "pub_66815b375f95f93dc84c77ebd25c007227d0e";

$jsonFilePath = __DIR__ . '/../../json/countryBorders.geo.json';

function fetchData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $urlOpenCage = "https://api.opencagedata.com/geocode/v1/json?q={$lat},{$lng}&key={$apiKeyOpenCage}";
    $responseOpenCage = fetchData($urlOpenCage);
    $dataOpenCage = json_decode($responseOpenCage, true);

    if (isset($dataOpenCage['results']) && count($dataOpenCage['results']) > 0) {
        $countryName = $dataOpenCage['results'][0]['components']['country'];
        echo json_encode(['countryName' => $countryName]);
    } else {
        echo json_encode(['error' => 'Unable to determine user\'s country.']);
    }
    exit;
}

if (isset($_POST['countryBorders'])) {
    $jsonContent = file_get_contents($jsonFilePath);
    $countryBordersData = json_decode($jsonContent, true);
    if ($countryBordersData) {
        echo json_encode(['countryBorders' => $countryBordersData]);
    } else {
        echo json_encode(['error' => 'Unable to fetch country borders data.']);
    }
    exit;
}

$countryName = isset($_POST['countryName']) ? $_POST['countryName'] : '';

if ($countryName) {
    $jsonContent = file_get_contents($jsonFilePath);
    $countryBordersData = json_decode($jsonContent, true);
    $countryFeature = null;

    foreach ($countryBordersData['features'] as $feature) {
        if ($feature['properties']['name'] === $countryName) {
            $countryFeature = $feature;
            break;
        }
    }

    if ($countryFeature) {
        $urlOpenCage = "https://api.opencagedata.com/geocode/v1/json?q=" . urlencode($countryName) . "&key=" . $apiKeyOpenCage; // OpenCage API URL
        $urlExchange = "https://openexchangerates.org/api/latest.json?app_id=" . $apiKeyOpenExchange . "&show_alternative=true"; // OpenExchange API URL
        $urlGeoNameWiki = 'http://api.geonames.org/wikipediaSearchJSON?title=' . urlencode($countryName) . '&maxRows=10&username=ysalm'; // GeoName API URL Wikipedia
        $responseGeoNameWiki = fetchData($urlGeoNameWiki);
        $dataGeoNameWiki = json_decode($responseGeoNameWiki, true);

        if (isset($dataGeoNameWiki['geonames']) && count($dataGeoNameWiki['geonames']) > 0) {
            $countrySummary = '';
            $countryWikiURL = '';
            $countryThumbnailImg = '';
            $citySummary = '';
            $cityWikiURL = '';
            $cityThumbnailImg = '';
            if (isset($dataGeoNameWiki['geonames']) && count($dataGeoNameWiki['geonames']) > 0) {
                $countryIndex = null;
                foreach ($dataGeoNameWiki['geonames'] as $index => $geoName) {
                if (isset($geoName['feature']) && $geoName['feature'] === 'country') {
                    $countryIndex = $index;
                    break;
                }
                }
                if ($countryIndex !== null) {
                if (empty($dataGeoNameWiki['geonames'][$countryIndex]['summary'])) {
                    $dataGeoNameWiki['geonames'][$countryIndex]['summary'] = "No summary available.";
                } else {
                    $countrySummary = preg_replace('/\s*\([^)]*\)/', '', $dataGeoNameWiki['geonames'][$countryIndex]['summary']) . '...';
                    $countryWikiURL = $dataGeoNameWiki['geonames'][$countryIndex]['wikipediaUrl'];
                    $countryThumbnailImg = $dataGeoNameWiki['geonames'][$countryIndex]['thumbnailImg'] ?? '';
                }
                } else {
                $countrySummary = preg_replace('/\s*\([^)]*\)/', '', $dataGeoNameWiki['geonames'][0]['summary']) . '...';
                $countryWikiURL = $dataGeoNameWiki['geonames'][0]['wikipediaUrl'];
                $countryThumbnailImg = $dataGeoNameWiki['geonames'][0]['thumbnailImg'] ?? '';
                }
            }

        foreach ($dataGeoNameWiki['geonames'] as $geoName) {
            if (isset($geoName['feature']) && $geoName['feature'] === 'city') {
                $citySummary = preg_replace('/\s*\([^)]*\)/', '', $geoName['summary']) . '...';
                $cityWikiURL = $geoName['wikipediaUrl'];
                $cityThumbnailImg = $geoName['thumbnailImg'] ?? '';
                $cityName = $geoName['title'];
                break;
            } else {
                $citySummary = preg_replace('/\s*\([^)]*\)/', '', $dataGeoNameWiki['geonames'][1]['summary']) . '...';
                $cityWikiURL = $dataGeoNameWiki['geonames'][1]['wikipediaUrl'];
                $cityThumbnailImg = isset($dataGeoNameWiki['geonames'][1]['thumbnailImg']) ? $dataGeoNameWiki['geonames'][1]['thumbnailImg'] : ($dataGeoNameWiki['geonames'][0]['thumbnailImg'] ?? '');
                $cityName = $dataGeoNameWiki['geonames'][1]['title'];
            }
        }

        $responseOpenCage = fetchData($urlOpenCage);
        $dataOpenCage = json_decode($responseOpenCage, true);

        if (isset($dataOpenCage['results']) && count($dataOpenCage['results']) > 0) {
            $coordinatesData = $dataOpenCage['results'][0]['geometry'];
            $annotationsData = $dataOpenCage['results'][0]['annotations'];
            $componentsData = $dataOpenCage['results'][0]['components'];
            $roadSide = ucfirst($annotationsData['roadinfo']['drive_on']);
            $speedType = strtoupper($annotationsData['roadinfo']['speed_in']);
            $currency = $annotationsData['currency']['name'];
            $currentCurrencyCode = $annotationsData['currency']['iso_code'];
            $continent = $componentsData['continent'];
            $countryCode = $componentsData['country_code'];
            $latitude = round($coordinatesData['lat'], 1);
            $longitude = round($coordinatesData['lng'], 1);
        }
        
        $responseExcahnge = fetchData($urlExchange);
        $dataExchange = json_decode($responseExcahnge, true);
        $exchangeRateList = []; 

        foreach ($dataExchange['rates'] as $currencyCode => $rate) {
            $exchangeRateList[] = [$currencyCode, $rate];
        }

        $countryExchangeRate = $dataExchange['rates'][$currentCurrencyCode];

            if (isset($countryCode)) {
                $urlGeoNameInfo = 'http://api.geonames.org/countryInfoJSON?country=' . urlencode($countryCode) . '&username=ySalm'; // GeoName API URL Info
                $urlGeoNameBorders = 'http://api.geonames.org/neighboursJSON?country=' . urlencode($countryCode) . '&username=ysalm'; // GeoName API URL Borders
                $urlnewsData = 'https://newsdata.io/api/1/news?apikey=' . $apiKeyNewsData . '&size=2&country=' . $countryCode . '&q=' . urlencode($countryName); // NewsData API URL
                $urlnewsDataEng = 'https://newsdata.io/api/1/news?apikey=' . $apiKeyNewsData . '&size=2&language=en&country=' . $countryCode . '&q=' . urlencode($countryName); // NewsData API URL
                $urlnewsDataEngNonSpec = 'https://newsdata.io/api/1/news?apikey=' . $apiKeyNewsData . '&size=2&language=en&country=' . $countryCode; // NewsData API URL

                $responseGeoNameInfo = fetchData($urlGeoNameInfo);
                $dataGeoNameInfo = json_decode($responseGeoNameInfo, true);

                if (isset($dataGeoNameInfo['geonames']) && count($dataGeoNameInfo['geonames']) > 0) {
                    $population = $dataGeoNameInfo['geonames'][0]['population'];
                    $landArea = (int) $dataGeoNameInfo['geonames'][0]['areaInSqKm'];
                    $capitalCity = $dataGeoNameInfo['geonames'][0]['capital'];
                }

                $responseGeoNameBorders = fetchData($urlGeoNameBorders);
                $dataGeoNameBorders = json_decode($responseGeoNameBorders, true);

                $borders = [];
                if (isset($dataGeoNameBorders['geonames']) && count($dataGeoNameBorders['geonames']) > 0) {
                    foreach ($dataGeoNameBorders['geonames'] as $border) {
                        $borders[] = $border['name'];
                    }
                    $borders = implode(', ', $borders);
                } else {
                    $borders = "None";
                }

                $responseNewsData = fetchData($urlnewsDataEng);
                $dataNewsData = json_decode($responseNewsData, true);

                $firstNewsDescription = '';
                $firstNewsImgUrl = '';
                $firstNewsPublishedDate = '';
                $firstNewsTitle = '';
                $firstNewsURL = '';
                $firstNewsName = '';
                $secondNewsDescription = '';
                $secondNewsImgUrl = '';
                $secondNewsPublishedDate = '';
                $secondNewsTitle = '';
                $secondNewsURL = '';
                $secondNewsName = '';

                if (isset($dataNewsData['results']) && count($dataNewsData['results']) > 0) {

                    $firstNewsTitle = $dataNewsData['results'][0]['title'];
                    $firstNewsURL = $dataNewsData['results'][0]['link'];
                    $firstNewsDescription = substr($dataNewsData['results'][0]['description'], 0, 30) . '...';
                    $firstNewsPublishedDate = $dataNewsData['results'][0]['pubDate'];
                    $firstNewsImgUrl = $dataNewsData['results'][0]['image_url'];
                    $firstNewsName = $dataNewsData['results'][0]['source_name'];

                    if (isset($dataNewsData['results'][1])) {
                        $secondNewsTitle = $dataNewsData['results'][1]['title'];
                        $secondNewsURL = $dataNewsData['results'][1]['link'];
                        $secondNewsDescription = substr($dataNewsData['results'][1]['description'], 0, 30) . '...';
                        $secondNewsPublishedDate = $dataNewsData['results'][1]['pubDate'];
                        $secondNewsImgUrl = $dataNewsData['results'][1]['image_url'];
                        $secondNewsName = $dataNewsData['results'][1]['source_name'];
                    } else {
                        $secondNewsTitle = '';
                        $secondNewsURL = '';
                        $secondNewsDescription = '';
                        $secondNewsPublishedDate = '';
                        $secondNewsImgUrl = '';
                        $secondNewsName = '';
                    }

                } else {
                    $responseNewsDataNoEng = fetchData($urlnewsData);
                    $dataNewsDataNoLang = json_decode($responseNewsDataNoEng, true);
                    if (isset($dataNewsDataNoLang['results']) && count($dataNewsDataNoLang['results']) > 0) {
                        $firstNewsTitle = $dataNewsDataNoLang['results'][0]['title'];
                        $firstNewsURL = $dataNewsDataNoLang['results'][0]['link'];
                        $firstNewsDescription = substr($dataNewsDataNoLang['results'][0]['description'], 0, 30) . '...';
                        $firstNewsPublishedDate = $dataNewsDataNoLang['results'][0]['pubDate'];
                        $firstNewsImgUrl = $dataNewsDataNoLang['results'][0]['image_url'];
                        $firstNewsName = $dataNewsDataNoLang['results'][0]['source_name'];

                        if (isset($dataNewsDataNoLang['results'][1])) {
                            $secondNewsTitle = $dataNewsDataNoLang['results'][1]['title'];
                            $secondNewsURL = $dataNewsDataNoLang['results'][1]['link'];
                            $secondNewsDescription = substr($dataNewsDataNoLang['results'][1]['description'], 0, 30) . '...';
                            $secondNewsPublishedDate = $dataNewsDataNoLang['results'][1]['pubDate'];
                            $secondNewsImgUrl = $dataNewsDataNoLang['results'][1]['image_url'];
                            $secondNewsName = $dataNewsDataNoLang['results'][1]['source_name'];
                        } else {
                            $secondNewsTitle = '';
                            $secondNewsURL = '';
                            $secondNewsDescription = '';
                            $secondNewsPublishedDate = '';
                            $secondNewsImgUrl = '';
                            $secondNewsName = '';
                        }
                    }
                }

                if ($firstNewsTitle === '') {
                    $responseNonSpecNews= fetchData($urlnewsDataEngNonSpec);
                    $dataNonSpecNewsData = json_decode($responseNonSpecNews, true);

                    if (isset($dataNonSpecNewsData['results']) && count($dataNonSpecNewsData['results']) > 0) {
                        $firstNewsTitle = $dataNonSpecNewsData['results'][0]['title'];
                        $firstNewsURL = $dataNonSpecNewsData['results'][0]['link'];
                        $firstNewsDescription = substr($dataNonSpecNewsData['results'][0]['description'], 0, 30) . '...';
                        $firstNewsPublishedDate = $dataNonSpecNewsData['results'][0]['pubDate'];
                        $firstNewsImgUrl = $dataNonSpecNewsData['results'][0]['image_url'];
                        $firstNewsName = $dataNonSpecNewsData['results'][0]['source_name'];

                        $secondNewsTitle = $dataNonSpecNewsData['results'][1]['title'];
                        $secondNewsURL = $dataNonSpecNewsData['results'][1]['link'];
                        $secondNewsDescription = substr($dataNonSpecNewsData['results'][1]['description'], 0, 30) . '...';
                        $secondNewsPublishedDate = $dataNonSpecNewsData['results'][1]['pubDate'];
                        $secondNewsImgUrl = $dataNonSpecNewsData['results'][1]['image_url'];
                        $secondNewsName = $dataNonSpecNewsData['results'][1]['source_name'];
                    }else{
                        $firstNewsDescription = '';
                        $firstNewsImgUrl = '';
                        $firstNewsPublishedDate = '';
                        $firstNewsTitle = '';
                        $firstNewsURL = '';
                        $firstNewsName = '';
                        $secondNewsDescription = '';
                        $secondNewsImgUrl = '';
                        $secondNewsPublishedDate = '';
                        $secondNewsTitle = '';
                        $secondNewsURL = '';
                        $secondNewsName = '';
                    }
                }

                if ($firstNewsDescription === null) {
                    $firstNewsDescription = "";
                }
                if ($firstNewsImgUrl === null) {
                    $firstNewsImgUrl = '';
                }
                if ($secondNewsDescription === null) {
                    $secondNewsDescription = "";
                }
                if ($secondNewsImgUrl === null) {
                    $secondNewsImgUrl = '';
                }

                $urlGeoNameAirport = 'http://api.geonames.org/searchJSON?formatted=true&q=airport&maxRows=100&lang=en&country=' . $countryCode . '&username=ysalm'; // GeoName API URL Airport
                $responseGeoNameAirPort = fetchData($urlGeoNameAirport);
                $dataGeoNameAirport = json_decode($responseGeoNameAirPort, true);

                if (isset($dataGeoNameAirport) && count($dataGeoNameAirport) > 0) {
                    $iataAirports = [];
                    foreach ($dataGeoNameAirport['geonames'] as $airport) {
                        $iataAirports[] = [
                            'name' => $airport['name'],
                            'latitude' => $airport['lat'],
                            'longitude' => $airport['lng']
                        ];
                    }
                }

                $urlGeoNameTime = 'http://api.geonames.org/timezoneJSON?lat=' . urlencode($latitude) . '&lng=' . urlencode($longitude) . '&username=ysalm'; // GeoName API URL TimeZone
                $responseGeoNameTime = fetchData($urlGeoNameTime);
                $dataGeoNameTime = json_decode($responseGeoNameTime, true);

                if (isset($dataGeoNameTime) && count($dataGeoNameTime) > 0) {
                    $sunrise = date('H:i', strtotime($dataGeoNameTime['sunrise']));
                    $sunset = date('H:i', strtotime($dataGeoNameTime['sunset']));
                    $currentTime = date('H:i', strtotime($dataGeoNameTime['time']));
                }

                $urlOpenWeather = 'https://api.openweathermap.org/data/2.5/forecast?lat=' . urlencode($latitude) . '&lon=' . urlencode($longitude) . '&cnt=40&units=metric&appid=' . $apiKeyOpenWeather; // Open Weather API
                $responseOpenWeather = fetchData($urlOpenWeather);
                $dataOpenWeather = json_decode($responseOpenWeather, true);

                if (isset($dataOpenWeather) && count($dataOpenWeather['list']) > 0) {
                    $weatherDescription = ucwords($dataOpenWeather['list'][0]['weather'][0]['description']);

                    $dailyWeather = [];
                    $currentWeather = [
                        'temp' => (int) $dataOpenWeather['list'][0]['main']['temp'] . '°',
                        'date' => date("D jS", strtotime($dataOpenWeather['list'][0]['dt_txt']))
                    ];
                    $dailyWeather[] = $currentWeather;

                    foreach ($dataOpenWeather['list'] as $weather) {
                        if (strpos($weather['dt_txt'], '12:00:00') !== false) {
                            $dailyWeather[] = [
                                'temp' => round($weather['main']['temp']) . '°c',
                                'date' => date("D jS", strtotime($weather['dt_txt']))
                            ];
                        }
                    }

                    if (count($dailyWeather) >= 5) {
                        $weatherTempOne = $dailyWeather[0]['temp'];
                        $weatherTempTwo = $dailyWeather[1]['temp'];
                        $weatherTempThree = $dailyWeather[2]['temp'];
                        $weatherTempFour = $dailyWeather[3]['temp'];
                        $weatherTempFive = $dailyWeather[4]['temp'];

                        $weatherDateTwo = $dailyWeather[1]['date'];
                        $weatherDateThree = $dailyWeather[2]['date'];
                        $weatherDateFour = $dailyWeather[3]['date'];
                        $weatherDateFive = $dailyWeather[4]['date'];
                    }
                }

                echo json_encode([
                    'latitude' => $latitude ?? null,
                    'longitude' => $longitude ?? null,
                    'currency' => $currency ?? null,
                    'currentCurrencyCode' => $currentCurrencyCode ?? null,
                    'roadSide' => $roadSide ?? null,
                    'speedType' => $speedType ?? null,
                    'continent' => $continent ?? null,
                    'population' => $population ?? null,
                    'landArea' => $landArea ?? null,
                    'countrySummary' => $countrySummary ?? null,
                    'countryWikiURL' => $countryWikiURL ?? null,
                    'countryExchangeRate' => $countryExchangeRate ?? null,
                    'borders' => $borders ?? null,
                    'sunrise' => $sunrise ?? null,
                    'sunset' => $sunset ?? null,
                    'currentTime' => $currentTime ?? null,
                    'capitalCity' => $capitalCity ?? null,
                    'countryCode' => $countryCode ?? null,
                    'countryThumbnailImg' => $countryThumbnailImg ?? '',
                    'citySummary' => $citySummary ?? null,
                    'cityWikiURL' => $cityWikiURL ?? null,
                    'cityThumbnailImg' => $cityThumbnailImg ?? null,
                    'cityName' => $cityName ?? null,
                    'firstNewsTitle' => $firstNewsTitle ?? null,
                    'firstNewsURL' => $firstNewsURL ?? null,
                    'firstNewsDescription' => $firstNewsDescription ?? null,
                    'firstNewsPublishedDate' => $firstNewsPublishedDate ?? null,
                    'firstNewsImgUrl' => $firstNewsImgUrl ?? '',
                    'firstNewsName' => $firstNewsName ?? null,
                    'secondNewsTitle' => $secondNewsTitle ?? null,
                    'secondNewsURL' => $secondNewsURL ?? null,
                    'secondNewsDescription' => $secondNewsDescription ?? null,
                    'secondNewsPublishedDate' => $secondNewsPublishedDate ?? null,
                    'secondNewsImgUrl' => $secondNewsImgUrl ?? '',
                    'secondNewsName' => $secondNewsName ?? null,
                    'iataAirports' => $iataAirports ?? [],
                    'exchangeRateList' => $exchangeRateList ?? [],
                    'weatherTempOne' => $weatherTempOne ?? null,
                    'weatherTempTwo' => $weatherTempTwo ?? null,
                    'weatherTempThree' => $weatherTempThree ?? null,
                    'weatherTempFour' => $weatherTempFour ?? null,
                    'weatherTempFive' => $weatherTempFive ?? null,
                    'weatherDescription' => $weatherDescription ?? null,
                    'weatherDateTwo' => $weatherDateTwo ?? null,
                    'weatherDateThree' => $weatherDateThree ?? null,
                    'weatherDateFour' => $weatherDateFour ?? null,
                    'weatherDateFive' => $weatherDateFive ?? null
                ]);
            }
        }
    }
}
?>