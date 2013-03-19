## WARNING
this is a work in progress, it lacks documentation and is currently
not meant for use in production.

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
1. Create database.
2. Run installer script.
3. Setup cronjobs
4. Install Stomp ( https://github.com/ppetermann/pecl-tools-stomp git clone that, phpize && make && make install ) / Alternatively you can throw youself at pecl
5. Contact Squizz / Karbo / PeterPowers to get access to Stomp (For live mails) / Alternatively you can also

## Cronjobs
* * * * * path/util/doJob.sh minutely
* * * * * path/util/doJob.sh fetchApis
* * * * * path/util/doJob.sh parseKills
* * * * * path/util/doJob.sh doPopulateCharactersTable
* * * * * path/util/doJob.sh updateCharacters
* * * * * path/util/doJob.sh updateCorporations
1 * * * * path/util/doJob.sh hourly
*/15 * * * * path/util/doJob.sh fightFinder
9 */3 * * * path/util/doJob.sh populateAllianceList
0 */6 * * * php5 path/util/item_update.php
