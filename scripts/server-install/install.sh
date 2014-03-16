aptitude update
aptitude install build-essential dpkg-dev devscripts hardening-wrapper htop g++ php5-cgi mysql-5.5-server php-pear php5-curl php5-suhosin php5-json php5-dev nginx redis-server

mkdir /home/www

wget -P /etc/init.d https://raw.githubusercontent.com/mrXCray/XCrataPult.php/master/scripts/server-install/php-fastcgi
chmod +x/etc/init.d/php-fastcgi
update-rc.d php-fastcgi defaults

pecl install igbinary
echo "extension=igbinary.so" > /etc/php5/cgi/conf.d/igbinary.ini

mkdir /home/devtmp
cd /home/devtmp

wget -O phpredis-master.zip https://github.com/nicolasff/phpredis/archive/master.zip
unzip phpredis-master.zip

cd /home/devtmp/phpredis-master
phpize
./configure --enable-redis-igbinary;
make && make install

echo "extension=redis.so" > /etc/php5/cgi/conf.d/redis.ini

cd /home
rm -rf devtmp

mkdir /home/config
ln -s /etc/php5/cgi/php.ini /home/config/php.ini
ln -s /etc/my.cnf /home/config/mysql.cnf
ln -s /etc/nginx/nginx.conf /home/config/nginx.conf
ln -s /etc/redis/redis.conf /home/config/redis.conf

mkdir /home/logs
mkdir /home/logs/mysql
mkdir /home/logs/nginx
mkdir /home/logs/redis

chown www-data:www-data -R /home/www/
chmod -R 0775 /home/www

chown mysql:mysql -R /home/logs/mysql
chmod -R 0775 /home/logs/mysql

chown www-data:www-data -R /home/logs/php
chmod -R 0775 /home/logs/php

chown www-data:www-data -R /home/logs/nginx
chmod -R 0775 /home/logs/nginx

chown redis:redis -R /home/logs/redis
chmod -R 0775 /home/logs/redis