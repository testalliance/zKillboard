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
Stomp requires a custom php module, which you have to install from the following git repo.
https://github.com/ppetermann/pecl-tools-stomp

Reason is that the stomp library in pecl has a flaw, once that flaw is fixed (like it is in this repo) you can install from there.
Till then, clone this repo, and install it like any other php module

- git clone https://github.com/ppetermann/pecl-tools-stomp.git
- cd pecl-tools-stomp
- phpize
- ./configure
- make
- make install
- add stuff to php so it loads it

The stomp service is read only.
- Stomp server: tcp://82.221.99.197:61613
- Stomp user: guest
- Stomp pass: guest
