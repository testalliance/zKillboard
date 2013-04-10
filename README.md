## WARNING
this is a work in progress, it lacks documentation and is currently
not meant for use in production.

All the crap code was written by me - Karbowiak

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
1. Get composer: curl -s https://getcomposer.org/installer | php
2. Install vendor files with composer: php composer.phar install
3. Create database
4. Run installer
5. Setup stomp
6. Setup cronjobs

## Basic cronjobs
* * * * * flock -w 63 /tmp/lock.stomp php5 /path/to/zKillboard/util/stomp.php
* * * * * flock -w 63 /tmp/lock.parseKills php5 /path/to/zKillboard/util/doJob.php parseKills
* * * * * flock -w 63 /tmp/lock.doPopulateCharactersTable php5 /path/to/zKillboard/util/doJob.php doPopulateCharactersTable


## Stomp
You will only be able to listen, and you should NOT share this with others
- Stomp server: tcp://82.221.99.197:61613
- Stomp user: guest
- Stomp pass: guest
