## WARNING
This is BETA, which means it is a work in progress.  It lacks documentation and is currently
not meant for use in production.

## IRC
We reside on Coldfront.
irc.coldfront.net
channel #esc

## LICENSE
see LICENSE.md File

## REQUIREMENTS
- PHP 5.3+
- APACHE + mod_rewrite / lighttpd
- LINUX / MAC OS X
- MySQL / MariaDB
- Pheal
- Composer: http://getcomposer.org/

## INSTALLATION
Installation is current command line only on linux consoles.  Any other support methods are
not currently supported.

1. Move to the location of your zkillboard install
2. Get composer: curl -s https://getcomposer.org/installer | php
3. Install vendor files with composer: php composer.phar install
3. Move to the install directory
4. Execute the installation script: php5 install.php
5. Follow the instructions and fill in the prompts
6. Setup stomp
7. Setup cronjobs

## Basic cronjobs
- * * * * * flock -w 63 /tmp/lock.stomp php5 /path/to/zKillboard/util/stomp.php
- * * * * * flock -w 63 /tmp/lock.parseKills php5 /path/to/zKillboard/util/doJob.php parseKills
- * * * * * flock -w 63 /tmp/lock.doPopulateCharactersTable php5 /path/to/zKillboard/util/doJob.php doPopulateCharactersTable

## Stomp
This stomp service is read only.
- Stomp server: tcp://82.221.99.197:61613
- Stomp user: guest
- Stomp pass: guest
