<?php
require('vendor/autoload.php');

if(!file_exists(__DIR__.'/credentials.json')) {
  echo "You need to first add your Foursquare access token in the file credentials.json. See the readme for details\n";
  die();
}

$credentials = json_decode(file_get_contents(__DIR__.'/credentials.json'), true);
if(!$credentials || !isset($credentials['foursquare_access_token'])) {
  echo "There was a problem reading the credentials file. See the readme for details\n";
  die();
}

$access_token = $credentials['foursquare_access_token'];


$continue = true;
$offset = 0;
$limit = 250;

while($continue) {

  $params = [
    'v' => '20170319',
    'oauth_token' => $access_token,
    'limit' => $limit,
    'offset' => $offset,
  ];

  echo "Fetching $limit checkins starting with $offset\n";

  $url = 'https://api.foursquare.com/v2/users/self/checkins?'.http_build_query($params);

  $http = new p3k\HTTP();
  $headers = [
    'User-Agent: https://github.com/aaronpk/Swarm-Checkins-Import'
  ];
  $response = $http->get($url, $headers);
  $info = json_decode($response['body'], true);

  if(isset($info['response']['checkins']['items'])) {

    if(count($info['response']['checkins']['items']) == 0) {
      echo "No more checkins found\n";
      die();
    }

    echo "Found ".count($info['response']['checkins']['items'])." checkins in this batch\n";

    foreach($info['response']['checkins']['items'] as $item) {

      process_checkin($item);

    }

  } else {
    echo "Error fetching checkins\n";
    $continue = false;
  }

  $offset += $limit;
}



function process_checkin($item) {

  echo "\n";
  #print_r($item);

  $date = DateTime::createFromFormat('U', $item['createdAt']);
  $offset = $item['timeZoneOffset'] / 60;
  if($offset >= 0)
    $offset = '+'.$offset;
  $date->setTimeZone(new DateTimeZone($offset));

  echo $date->format('c')."\n";

  $filename = 'checkins/' . $date->format('Y/m/d/His') . '.json';
  $folder = dirname($filename);

  echo $filename."\n";
  echo $item['venue']['name']."\n";

  @mkdir($folder, 0755, true);

  if(file_exists($filename)) {
    echo "Already imported $filename\n";
    return;
  }

  file_put_contents($filename, json_encode($item, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES));
}

