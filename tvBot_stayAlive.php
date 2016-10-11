<?php
$pid = getmypid();
$config = json_decode(file_get_contents('config.json'), true);
$config['stay_alive_pid'] = $pid;
file_put_contents('config.json', json_encode($config));
while (true == true) {
  exec('nohup php robo start:tv-bot >> tvBot.log 2>&1 &');
  time_sleep_until(strtotime("+3 hours"));
}
?>
