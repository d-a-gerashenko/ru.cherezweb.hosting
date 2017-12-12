PHP 5.3.10-1ubuntu3.15
Ubuntu 12.04.5 LTS x64

* * * * * php /var/www/ru.cherezweb.hosting.main/app/console swiftmailer:spool:send --message-limit=10 --env=prod
* * * * * php /var/www/ru.cherezweb.hosting.main/app/console cherez_web:hosting:domain_activation
* * * * * php /var/www/ru.cherezweb.hosting.main/app/console cherez_web:hosting:task_processing
*/10 * * * * php /var/www/ru.cherezweb.hosting.main/app/console cherez_web:hosting:prolongation_notification
*/10 * * * * php /var/www/ru.cherezweb.hosting.main/app/console cherez_web:hosting:unpaid_block
*/10 * * * * php /var/www/ru.cherezweb.hosting.main/app/console cherez_web:hosting:unpaid_delete