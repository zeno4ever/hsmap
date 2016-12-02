<?php

// Enable Error Reporting and Display:
error_reporting(~0);
ini_set('display_errors', 1);
//system settings
set_time_limit(300);// in secs, 0 for infinite
date_default_timezone_set('Europe/Amsterdam');

$wait_time = 2*60; //ddos protection, wait at least x seconds before next update
$array_geo = array ("type"=> "FeatureCollection");
$messages = '';

// ********************* SETTINGS ***********************
//spaces with no (working) space api, add static to $array_geo
addspace( $array_geo, 'TDVenlo', 6.1690324, 51.3708052, 'Grote Kerkstraat 31', '5911 GC Venlo', 'http://tdvenlo.nl/' );
addspace( $array_geo, 'Madspace', 5.4745668, 51.434641, 'Don Boscostraat 4',  '5611 KW Eindhoven', 'http://madspace.nl' );

//Spaces with errors, for now overrule this manual
addspace( $array_geo, 'Bhack', 6.0896818, 52.5112671, 'Sassenstraat 26',  '8011 PC Zwolle', 'http://bhack.nl' );
addspace( $array_geo, 'Awsomespace', 5.0909871, 52.0724773, 'Amerikalaan 109',  '3526 VG Utrecht', 'http://awesomespace.nl' );
addspace( $array_geo, 'LAG', 4.8498906, 52.3539472, 'Eerste Schinkelstraat 16',  '1075 TX Amsterdam', 'http://awesomespace.nl' );
addspace( $array_geo, 'Pixelbar', 4.433988, 51.910111, 'Vierhavensstraat 56',  '3029 BG Rotterdam', 'http://pixelbar.nl' );

//spaces with spaceaoi, get open / closed status.
//Directory at http://spaceapi.net/directory.json
$hs_array = [
    "ACKspace"=>"https://ackspace.nl/spaceAPI/",
    //"Bhack"=> "http://api.bhack.nl/SpaceApi",
    "Bitlair" => "https://bitlair.nl/statejson.php",
    "Frack" => "http://frack.nl/spacestate/?api",
    "Hack42"=>"http://hack42.nl/spacestate/json.php",
    //"LAG" => "http://state.laglab.org/spaceapi.json",
    //"Pixelbar"=>"https://www.pixelbar.nl/backend/spaceapi.json.php", oud
    //"Pixelbar"=>"https://spaceapi.pixelbar.nl/", //nieuw
    "NURDSpace"=>"http://space.nurdspace.nl/spaceapi/status.json",
    "TkkrLab"=>"https://tkkrlab.nl/statejson.php",
    "TechnInc"=>"http://techinc.nl/space/spacestate.json",
    "Randomdata"=>"http://randomdata.nl/hsapi.php",
    "RevSpace"=>"http://revspace.nl/status/status.php",
    "Sk1llz" => "http://sk1llz.nl/spaceapi.php",
    //"Awesome Space"=>"http://awesomespace.nl/state/?show=api",
    "VoidWarranties" => "http://spaceapi.voidwarranties.be"
];

// ********************* END SETTINGS ***********************

if (PHP_SAPI === 'cli' ) {
    if(empty($argv[1]) ) {
        echo "Set var1 for webroot\n";
        exit(1);
    } else {
        $_SERVER['DOCUMENT_ROOT'] = $argv[1];
    };
};

$json_filename = $_SERVER['DOCUMENT_ROOT'].'/hsmap/hsnl.geojson';

$messages .= "Last update on : ".date ("F d Y H:i:s.", filemtime($json_filename))." | ";
echo "Last update on : ".date ("F d Y H:i:s.", filemtime($json_filename))."<br>\n";

if (time()-filemtime($json_filename) > $wait_time ) {

    echo "Update json file\n";

    //setup for json later
    $json_geo ='';

    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);


    //SSL
    //curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, true);
    //curl_setopt($curlSession, CURLOPT_SSLVERSION,2);
    //curl_setopt($curlSession, CURLOPT_CAINFO, $_SERVER['DOCUMENT_ROOT'].'/hsmap/cacert.pem');

    //no SSL checks
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curlSession, CURLOPT_TIMEOUT,120); //timeout in secs

    //loop hackerspaces
    foreach ($hs_array as $space => $url) {

        curl_setopt($curlSession, CURLOPT_URL, $url);
        $space_api_json = curl_exec($curlSession);
        $curl_error = curl_errno($curlSession);
        $curl_info = curl_getinfo($curlSession,CURLINFO_HTTP_CODE);

        //$curl_ssl = curl_getinfo($curlSession,CURLINFO_SSL_VERIFYRESULT);
        //echo 'SSL: '.$curl_ssl.'\n';

        if ( $curl_error == 0 && $curl_info == 200 ) {

            //make array from space api json
            $array_json = json_decode($space_api_json, true);

            if ($array_json['api'] > '0.12') {
                $lon = $array_json['location']['lon'];
                $lat = $array_json['location']['lat'];
            } else {
                $lon = $array_json['lon'];
                $lat = $array_json['lat'];
            };

            if (isset($array_json['state']['open'])) {
                if ($array_json['state']['open']) {
                    $icon = '/hsmap/hs_open.png';
                } else {
                    $icon = '/hsmap/hs_closed.png';
                };
            } else {
                $icon = '/hsmap/hs.png';
            };

            //translate spaceapi array to geojson array
            $full_address = explode(',',(isset($array_json['location']['address'] )) ? $array_json['location']['address'] : '' );

            $address = (isset($full_address[0])) ? $full_address[0] : '';
            $zip = (isset($full_address[1] )) ? $full_address[1] : '' ;
            $city = (isset($full_address[2])) ? $full_address[2] : '' ;

            if (trim($city) == 'The Netherlands') $city = '';

            $email = (isset($array_json['contact']['email'] )) ? $array_json['contact']['email'] : '' ;
            $phone = (isset($array_json['contact']['phone'] )) ? $array_json['contact']['phone'] : '' ;

            $array_geo['features'][] = array(
                "type"=> "Feature",
                "geometry" => array (
                    "type" => "Point",
                    "coordinates" => Array(
                        $lon,
                        $lat
                    ),
                ),
                "properties" => Array(
                    "marker-symbol" => $icon,
                    "name" => $array_json['space'],
                    "url" => $array_json['url'],
                    "address" => $address,
                    "zip" => $zip,
                    "city" => $city,
                    "email" => $email,
                    "phone" => $phone,
                    "description" => ''
                )
            );
        } else {
            echo "ERROR ".$space."  >  ".$url." -> Curl Error : ".$curl_error ." HTTP Info : ".$curl_info."<br>\n";
            $messages .= "ERROR ".$space."  >  ".$url." -> Curl Error : ".$curl_error ." HTTP Info : ".$curl_info." | ";
        };
    };

    curl_close($curlSession);

    if (isset($messages)) $array_geo["type"] = array("HS_MSG"=>$messages);

    $json_geo = json_encode($array_geo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

    $fp = fopen($json_filename, 'w');
    fwrite($fp,$json_geo);
    fclose($fp);
};

function addspace(&$array_geo, $name, $lat, $lon, $address, $zip, $url ) {
    $array_geo['features'][] = array(
        "type"=> "Feature",
        "geometry" => array (
            "type" => "Point",
            "coordinates" => Array(
                $lat,
                $lon
            ),
        ),
        "properties" => Array(
            "marker-symbol" => '/hsmap/hs.png',
            "name" => $name,
            "url" => $url,
            "address" => $address,
            "zip" => $zip,
            "city" => '',
            "email" => '',
            "phone" => '',
            "description" => ''
        )
    );
};

?>
