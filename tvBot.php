<?php
date_default_timezone_set('Asia/Jerusalem');
require 'vendor/autoload.php';
require 'utils.php';
use Kryptonit3\SickRage\SickRage;
$pid = getmypid();
echo "my process id is- $pid\n";
$http = new \GuzzleHttp\Client(['base_uri'=>'https://slack.com']);
$config = json_decode(file_get_contents('config.json'), true);
$bot_id = authTest($http, $config['slack_token'])['user_id'];
$config['bot_id'] = $bot_id;
$config['pid'] = $pid;
file_put_contents('config.json', json_encode($config));
runStayAlive($config);
if (isset($config['sickrage_username']) and isset($config['sickrage_password'])) {
  $sickrage = new SickRage($config['sickrage_url'], $config['sickrage_token'], $config['sickrage_username'], $config['sickrage_password']);
}else {
  $sickrage = new SickRage($config['sickrage_url'], $config['sickrage_token']);
}
$loop = \React\EventLoop\Factory::create();
$client = new \Slack\RealTimeClient($loop);
$client->setToken($config['slack_token']);
$client->connect()->then(function () {
  echo("Connected\n");
});

$client->on('message', function ($data) use ($client, $sickrage, $http, $config) {
  $channel_id = $data['channel'];
  $text = strtolower($data['text']);
  if ($data['user'] == $config['bot_id']) {
    echo "received a message from my self.. ignoring\n";
  }else {
    if (strpos($data['text'], $config['bot_id']) !== false) { // check that bot was mentiond in the messages text
      echo("Incoming: user ".$data['user']." typed a message in channel: $channel_id, lowercased text is: $text\n");
      if (file_get_contents('options.json') != '') { // check that options file is not empty
        $wanted_option_text = explode(">", $text)[1]; // remove the bots name because it has numbers in it
        if (strpos($wanted_option_text, '1') !== false) { // user wants option 1
          $wanted_show_id = json_decode(file_get_contents('options.json'), true)[0]; // get wanted show id from options file
          if (isset($wanted_show_id)) { // check that the user requested a valid option number
            addShow($http, $sickrage, $config, $channel_id, $wanted_show_id);
            file_put_contents('options.json', '');
          }
        }elseif (strpos($wanted_option_text, '2') !== false) { // user wants option 1
          $wanted_show_id = json_decode(file_get_contents('options.json'), true)[1]; // get wanted show id from options file
          if (isset($wanted_show_id)) { // check that the user requested a valid option number
            addShow($http, $sickrage, $config, $channel_id, $wanted_show_id);
            file_put_contents('options.json', '');
          }
        }elseif (strpos($wanted_option_text, '3') !== false) { // user wants option 1
          $wanted_show_id = json_decode(file_get_contents('options.json'), true)[2]; // get wanted show id from options file
          if (isset($wanted_show_id)) { // check that the user requested a valid option number
            addShow($http, $sickrage, $config, $channel_id, $wanted_show_id);
            file_put_contents('options.json', '');
          }
        }else {
          sender($http, $config, $channel_id, "you didn't choose a show.. canceling", '');
          file_put_contents('options.json', '');
        }
      }else {
        if (strpos($text, 'add') !== false or strpos($text, 'download') !== false) {
          if (strpos($text, 'add') !== false) {
            $wanted_show = trim(explode("add", $text)[1]);
            echo "The requested show is-$wanted_show\n";
            searchShow($http, $sickrage, $config, $channel_id, $wanted_show);
          }elseif (strpos($text, 'download') !== false) {
            $wanted_show = trim(explode("download", $text)[1]);
            echo "The requested show is-$wanted_show\n";
            searchShow($http, $sickrage, $config, $channel_id, $wanted_show);
          }else {
            sender($http, $config, $channel_id, "i couldn't understand what show you want, try again", '');
          }
          echo "waiting for user to choose a show\n";
        }elseif (strpos($text, 'ping') !== false) {
          sender($http, $config, $channel_id, 'pong', '');
        }elseif (strpos($text, 'marco') !== false) {
          sender($http, $config, $channel_id, 'polo', '');
        }elseif (strpos($text, 'list') !== false and strpos($text, 'shows') !== false) {
          listShows($http, $sickrage, $config, $channel_id);
        }elseif (strpos($text, 'self') !== false and strpos($text, 'update') !== false) {
          sender($http, $config, $channel_id, 'excuse me while i update my-self', '');
          exec('php robo update:tv-bot');
        }elseif (strpos($text, 'schedule') !== false ) {
          checkSchedule($http, $sickrage, $config, $channel_id);
        }elseif ((strpos($text, 'shut') !== false and strpos($text, 'down') !== false) or (strpos($text, 'turn') !== false and strpos($text, 'off') !== false)) {
          $attachments = json_encode(array(
            'fallback' => "I'll be back",
            'image_url' => "https://cdn.meme.am/instances/400x/25235723.jpg"
          ));
          sender($http, $config, $channel_id, '', $attachments);
          exec('php robo stop:tv-bot');
        }else {
          sender($http, $config, $channel_id, 'what?', '');
        }
      }
    }
  }
});
$loop->run();
?>
