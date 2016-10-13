# slack-sickrage-bot
To the good people of the open-source community! 
This is a sickrage slack bot written in php, just really a mash up between other open source projects.
FYI, i'm a rookie codder so don't learn how to code from my bot :)


What it does-
* adds new shows to your sickrage wanted list
* sends over a list of "added" shows
* periodically (or by request) sends you your upcoming schedule 


how to make it do it-
1. open a slack account
2. create a "bot custom integration" from slacks settings screen, it will give you a bot-token
3. add that bot-token to the config under the "slack_token"
4. enter the ip / dns to your sickrage server under "sickrage_url"
5.  get your sickrage token from sickrages settings screen and add it to "sickrage_token"
6. if your sickrage instance has  username - password protection enter them to the config it not leave empty
7. default_channel is the slack channel that will get the non user initiated messages (like periodic updates)
8. create a tv-db account and add your apikey username and userkey
9. for debug logs set debug to true
10. run "composer update" via your command line interface (aka terminal) 
11. run "php robo start:tv-bot" and you are live!!
