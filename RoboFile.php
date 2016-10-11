<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
  function hello($name)
  {
    $this->say("hello $name");
  }

  function startTvBot()
  {
    $config = json_decode(file_get_contents('config.json'), true);
    $pid = $config['pid'];
    if (isset($config['pid']) != false and $pid != '') {
      $this->taskExec("kill -0 $pid > activeTest 2>&1")
        ->run();
      if (file_get_contents("activeTest") != '') {
        $this->say('tv bot had pid but was not running');
        $this->taskExec("nohup php tvBot.php > tvBot.log 2>&1 &")
          ->run();
        $this->say('Started tv bot');
      }else {
        $this->say('tv bot is already running');
      }
    }else {
      $this->say('tv bot did not have any pid');
      $this->taskExec("nohup php tvBot.php > tvBot.log 2>&1 &")
        ->run();
      $this->say('Started tv bot');
    }
  }

  function stopTvBot()
  {
    $config = json_decode(file_get_contents('config.json'), true);
    $pid = $config['pid'];
    if (isset($config['pid']) != false and $pid != '') {
      $this->taskExec("kill -0 $pid > activeTest 2>&1")
        ->run();
      if (file_get_contents("activeTest") != '') {
        $config['pid'] = '';
        file_put_contents('config.json', json_encode($config));
        $this->say('tv bot had pid but was not running, cleared pid');
      }else {
        $this->taskExec("kill -9 $pid")
          ->run();
        $this->say('tv bot had pid and it was active, killed it good');
      }
    }else {
      $this->say('tv bot is already dead');
    }
  }

  function updateTvBot()
  {
    require 'paths.php';
    $this->taskExec("php robo stop:tv-bot")
      ->run();
    $this->taskExec("git pull")
      ->run();
    $this->taskComposerInstall()
      ->run();
    $this->taskExec("php robo start:tv-bot")
      ->run();
  }
}
