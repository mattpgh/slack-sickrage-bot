<?php

function sender($http, $config, $channel_id, $text, $attachments)
{
  if ($attachments != '') {
    try {
      $res = $http->request('GET', "/api/chat.postMessage", [
        'query' => [
          'token' => $config['slack_token'],
          'channel' => $channel_id,
          'unfurl_links' => 'true',
          'as_user' => 'true',
          'attachments' => "[$attachments]"
        ]
      ]);
      $body = $res->getBody();
      echo (json_decode($res->getBody()->getContents(), true)['ok'] == true) ? "Answered: $text\n" : "Failed sending: $text\n$body\n";
    } catch (GuzzleHttp\Exception\ClientException $e) {
      echo 'Oh snap! ' . $e->getMessage();
      return;
    }
  }else {
    try {
      $res = $http->request('GET', "/api/chat.postMessage", [
        'query' => [
          'token' => $config['slack_token'],
          'channel' => $channel_id,
          'unfurl_links' => 'true',
          'as_user' => 'true',
          'text' => $text
        ]
      ]);
      $body = $res->getBody();
      echo (json_decode($res->getBody()->getContents(), true)['ok'] == true) ? "Answered: $text\n" : "Failed sending: $text\n$body\n";
    } catch (GuzzleHttp\Exception\ClientException $e) {
      echo 'Oh snap! ' . $e->getMessage();
      return;
    }
  }
}


function authTest($http, $slack_token)
{
  try {
    $res = $http->request('GET', "/api/auth.test", [
      'query' => [
        'token' => "$slack_token",
      ]
    ]);
    $body = json_decode($res->getBody()->getContents(), true);
    if ($body['ok'] == true) {
      echo "Successfully authenticated\n";
      return $body;
    }else {
      throw new Exception("Current authentication is invalid");
    }
  } catch (GuzzleHttp\Exception\ClientException $e) {
    throw new Exception('Oh snap! could not authenticate ' . $e->getMessage());
  }
}

function searchShow($http, $sickrage, $config, $channel_id, $wanted_show)
{
  try {
    $res = $sickrage->sbSearchTvdb($wanted_show);
    echo "$res\n";
  } catch (Kryptonit3\SickRage\Exceptions\InvalidException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    sender($http, $config, $channel_id, "Problam comunicating with SickRage");
    return;
  }
  $search_results = json_decode($res, true);
  if ($search_results['result'] == 'success') { // check that request was successful
    if (count($search_results['data']['results']) > 0) { // check that there are results
      if (count($search_results['data']['results']) >= 3) {
        echo "found more than 3 options\n";
        $options = array($search_results['data']['results'][0]['tvdbid'], $search_results['data']['results'][1]['tvdbid'], $search_results['data']['results'][2]['tvdbid']);
        file_put_contents('options.json',json_encode($options));
        $options_count = 3;
      }elseif (count($search_results['data']['results']) == 2) {
        echo "found 2 options\n";
        $options = array($search_results['data']['results'][0]['tvdbid'], $search_results['data']['results'][1]['tvdbid']);
        file_put_contents('options.json', json_encode($options));
        $options_count = 2;
      }elseif (count($search_results['data']['results']) == 1) {
        echo "found 1 option\n";
        $options = array($search_results['data']['results'][0]['tvdbid']);
        file_put_contents('options.json', json_encode($options));
        $options_count = 1;
      }else {
        throw new Exception("WTF?!");
      }
      $a = 1;
      while ($a <= $options_count) {
        $current_option = $search_results['data']['results'][$a-1];
        $rand_color = dechex(rand(0x000000, 0xFFFFFF));
        $title = $current_option['name'];
        echo "option title is- $title\n";
        $tvdb_id = $current_option['tvdbid'];
        $year = explode("-", $current_option['first_aired'])[0];
        echo "option year is- $year\n";
        $show_data = getShowData($http, $tvdb_id)['data'];
        $banner = $show_data['banner'];
        $imdb_id = $show_data['imdbId'];
        $rating = $show_data['siteRating'];
        $status = $show_data['status'];
        $network = $show_data['network'];
        $attachments = json_encode(array(
          'fallback' => $title,
          'color' => $rand_color,
          'pretext' => "Download option #$a",
          'title' => $title,
          'title_link' => "http://imdb.com/title/$imdb_id",
          'image_url' => "http://thetvdb.com/banners/_cache/$banner",
          'fields' => array(
            0 => array(
              'title' => 'Year',
              'value' => $year,
              'short' => true
            ),
            1 => array(
              'title' => 'Rating',
              'value' => $rating,
              'short' => true
            ),
            2 => array(
              'title' => 'Status',
              'value' => $status,
              'short' => true
            ),
            3 => array(
              'title' => 'Network',
              'value' => $network,
              'short' => true
            )
          )
        ));
        sender($http, $config, $channel_id, $text, $attachments);
        $a = $a+1;
      }
      sender($http, $config, $channel_id, 'you can choose an option by replying with the wanted option number', '');
    }else {
      echo "request is valid but could not find results\n";
      sender($http, $config, $channel_id, "I couldn't find any shows :hankey:");
    }
  }else {
    echo "request was unsuccessful\n";
    sender($http, $config, $channel_id, "Oh snap! something went wrong");
  }
}

function addShow($http, $sickrage, $config, $channel_id, $tvdb_id)
{
  echo "attempting to add a show with id $tvdb_id\n";
  try {
    $res = json_decode($sickrage->showAddNew($tvdb_id), true);
    if ($res['result'] == 'error') {
      sender($http, $config, $channel_id, "Oh snap! something went wrong", '');
    }else {
      if (strpos($res['message'], 'already exists')) {
        sender($http, $config, $channel_id, "seems like the requested show is already in your library", '');
      }else {
        $message = $res['message'];
        $imdb_id = getShowData($http, $tvdb_id)['data']['imdbId'];
        sender($http, $config, $channel_id, "$message\nhttp://imdb.com/title/$imdb_id", '');
        return $res;
      }
    }
  } catch (Kryptonit3\SickRage\Exceptions\InvalidException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    sender($http, $config, $channel_id, "Problam comunicating with SickRage");
    return;
  }
}


function getShowData($http, $tvdb_id)
{
  $tvdb_token = loginTVDB($http);
  if (!isset($tvdb_token)) return;
  try {
    $res = $http->request('GET', "https://api.thetvdb.com/series/$tvdb_id", [
      'headers' => [
        'Authorization' => $tvdb_token
      ]
    ]);
    return json_decode($res->getBody()->getContents(), true);
  } catch (GuzzleHttp\Exception\ClientException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    return;
  }
}

function loginTVDB($http)
{
  $config = json_decode(file_get_contents('config.json'), true);
  try {
    $res = $http->request('POST', 'https://api.thetvdb.com/login', [
      'json' => [
        'apikey' => $config['tvbd_apikey'],
        'username' => $config['tvdb_username'],
        'userkey' => $config['tvdb_userkey']
      ]
    ]);
    $token = json_decode($res->getBody()->getContents(), true)['token'];
    return "Bearer $token";
  } catch (GuzzleHttp\Exception\ClientException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    return;
  }
}

function listShows($http, $sickrage, $config, $channel_id)
{
  try {
    $shows_array = array_keys(json_decode($sickrage->shows('name'), true)['data']);
    $list = "here is a list of shows-\n";
    foreach ($shows_array as $show) {
      $list = "$list$show\n";
    }
    sender($http, $config, $channel_id, $list, '');
  } catch (Kryptonit3\SickRage\Exceptions\InvalidException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    sender($http, $config, $channel_id, "Problem communicating with SickRage");
    return;
  }
}

function checkSchedule($http, $sickrage, $config, $channel_id)
{
  try {
    $res = $sickrage->future('date', 'today|soon', 0);
  } catch (Kryptonit3\SickRage\Exceptions\InvalidException $e) {
    echo 'Oh snap! ' . $e->getMessage();
    sender($http, $config, $channel_id, "Problem communicating with SickRage", '');
    return;
  }
  $body = json_decode($res, true);
  $text = "";
  if ($body['result'] == 'success') {
    echo "successful request\n";
    if (isset($body['data']['today']) != false) {
      $today_count = count($body['data']['today']);
      echo "found $today_count episodes scheduled for today\n";
      foreach ($body['data']['today'] as $episode) {
        $episode_number = "S".$episode['season']."-E".$episode['episode'];
        $text .= $episode['show_name']." $episode_number (".$episode['ep_name'].") will air on ".$episode['airs']."\n";
        $send = true;
      }

    }else {
      echo "no episodes scheduled for today";
    }
    if (isset($body['data']['soon']) != false) {
      $soon_count = count($body['data']['soon']);
      echo "found $soon_count episodes airing in the near feature\n";
      foreach ($body['data']['soon'] as $episode) {
        $episode_number = "S".$episode['season']."-E".$episode['episode'];
        $text .= $episode['show_name']." $episode_number (".$episode['ep_name'].") will air on ".$episode['airs']."\n";
        $send = true;
      }

    }else {
      echo "no episodes scheduled for the near feature\n";
    }
    if ($send == true) {
      sender($http, $config, $channel_id, "Here is your upcoming schedule:\n$text", '');
    }
  }else {
    echo "oh snap! unsuccessful request\n";
  }
}

function runStayAlive($config)
{
  if (isset($config['stay_alive_pid']) != false and $config['stay_alive_pid'] != '') {
    $pid = $config['stay_alive_pid'];
    exec("kill -0 $pid > activeTest 2>&1");
    if (file_get_contents("activeTest") != '') {
      echo "stay alive had a pid but it was not running\n";
      exec('nohup php stayAlive.php >> tvBot.log 2>&1 &');
      echo "turned on stay alive\n";
    }else {
      echo "stay alive is already on\n";
    }
  }else {
    echo "stay alive does not have a pid\n";
    exec('nohup php stayAlive.php >> tvBot.log 2>&1 &');
    echo "turned on stay alive\n";
  }
}
?>
