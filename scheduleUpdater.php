<?php
date_default_timezone_set('Asia/Jerusalem');
require 'vendor/autoload.php';
require 'utils.php';
use Kryptonit3\SickRage\SickRage;
$http = new \GuzzleHttp\Client(['base_uri'=>'https://slack.com']);
$config = json_decode(file_get_contents('config.json'), true);
if (isset($config['sickrage_username']) and isset($config['sickrage_password'])) {
  $sickrage = new SickRage($config['sickrage_url'], $config['sickrage_token'], $config['sickrage_username'], $config['sickrage_password']);
}else {
  $sickrage = new SickRage($config['sickrage_url'], $config['sickrage_token']);
}
while (true == true) {
  echo "schedule checker started on ".date("d.m.y")." timestamp- ".time()."\n";
  checkSchedule($http, $sickrage, $config, $config['default_channel']);
  echo "next run will be on ".date("d.m.y", strtotime("+1 week"))."\n";
  time_sleep_until(strtotime("+1 week"));
}
?>
