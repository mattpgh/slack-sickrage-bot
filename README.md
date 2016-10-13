# slack-sickrage-bot
To the good people of the open-source community! 
This is a sickrage slack bot written in php, just really a mash up between other open source projects.
FYI, i'm a rookie codder so don't learn how to code from my bot :)


## What it does-
* adds new shows to your sickrage wanted list
* sends over a list of "added" shows
* periodically (or by request) sends you your upcoming schedule 


## How to make it do it-
* open a slack account
* create a "bot custom integration" from slacks settings screen, it will give you a bot-token
* add that bot-token to the config under the "slack_token"
* enter the ip / dns to your sickrage server under "sickrage_url"
* get your sickrage token from sickrages settings screen and add it to "sickrage_token"
* if your sickrage instance has  username - password protection enter them to the config it not leave empty
* default_channel is the slack channel that will get the non user initiated messages (like periodic updates)
* create a tv-db account and add your apikey username and userkey
* for debug logs set debug to true
* run "composer update" via your command line interface (aka terminal) 
* run "php robo start:tv-bot" and you are live!!

## Thanks
kryptonit3/sickrage - for the cool sickrage api wrapper <br />
coderstephen/slack-client - for the easy to use WS slack client <br />
guzzlehttp/guzzle - for the powerfull http client <br />
codegyre/robo - for siplifing CLI commands <br />
Please respect there terms of use, have fun and stay safe ;)
