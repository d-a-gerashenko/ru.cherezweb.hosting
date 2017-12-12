set -e # Exit on error.

if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi

#podgotovka
apt-get update

#mysql
_mysql_root_password=`date +%s | sha256sum | base64 | head -c 15`
_proftpd_mysql_password=`date +%s | sha256sum | base64 | head -c 15`

debconf-set-selections <<< "mysql-server mysql-server/root_password password ${_mysql_root_password}"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${_mysql_root_password}"
apt-get -y install mysql-server

#apache+php
apt-get -y install libapache2-mod-php5 php5 php5-common php5-dev php5-curl php5-gd php5-intl php-pear php5-imagick php5-mcrypt php5-memcache php5-ming php5-mysql php5-pspell php5-recode php5-sqlite php5-tidy php5-xmlrpc php5-xsl
a2enmod rewrite
php ./installer/php_set_conf.php
apt-get -y install apache2-mpm-itk
service apache2 restart

mkdir /etc/apache2/sites-enabled/allocations
php ./installer/apache_set_conf.php

service apache2 restart

#app key
_app_access_key=`date +%s | sha256sum | base64 | head -c 15`
printf "\n\n------------------------------\n${_app_access_key}\n------------------------------\n\n"
php ./installer/app_save_key.php $_app_access_key
read -n1 -r -p "Copy access kay and press any key to continue..."

#mysql donastroyka
php ./installer/mysql_conf_set.php
service mysql restart
php ./installer/mysql_save_passwords.php $_mysql_root_password $_proftpd_mysql_password

#quota
aptitude -y install quota
php ./installer/enable_quota_in_fstab.php
#touch /aquota.user /aquota.group
#chmod 600 /aquota.*
mount -o remount /
quotacheck -avugm
quotaon -avug
apt-get -y install quotatool

#proftpd
debconf-set-selections <<< "proftpd-basic shared/proftpd/inetd_or_standalone select standalone"
apt-get -y install proftpd-basic proftpd-mod-mysql
echo -e "CREATE DATABASE proftpd;\nGRANT USAGE ON *.* TO 'proftpd'@'localhost' IDENTIFIED BY '${_proftpd_mysql_password}';\nGRANT ALL PRIVILEGES ON proftpd.* TO 'proftpd'@'localhost';\nFLUSH PRIVILEGES;\nexit" | mysql --user=root --password=$_mysql_root_password
mysql --user=root --password=$_mysql_root_password proftpd < ./installer/proftpd.sql
php ./installer/proftpd_set_conf.php
cp /etc/proftpd/sql.conf /etc/proftpd/sql.conf.backup
php ./installer/proftpd_set_sql_conf.php $_proftpd_mysql_password
php ./installer/proftpd_modules_set.php
service proftpd restart

#mysql
chown root:root /var/lib/mysql
chmod 755 /var/lib/mysql

#allocation
mkdir /var/allocations
chmod 755 /var/allocations

#vistavlenie prav
chown root:root -R /etc/ru.cherezweb.hosting.srv/
chmod 700 -R /etc/ru.cherezweb.hosting.srv/
chmod 755 /etc/ru.cherezweb.hosting.srv/
chmod 755 -R /etc/ru.cherezweb.hosting.srv/default_pages/

#prochie utiliti
apt-get -y install unzip
apt-get -y install git
apt-get -y install subversion

#crontab
crontab ./installer/crontab_content

#java
add-apt-repository ppa:webupd8team/java
apt-get update
apt-get -y install oracle-java8-installer

#restart
reboot