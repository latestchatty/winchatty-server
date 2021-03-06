#!/bin/bash
# This is intended to be a Linode StackScript but can be used manually by following the directions below.
#
#<UDF name="shack_username" label="Shacknews username (dedicated to this server)">
# SHACK_USERNAME=
#
#<UDF name="shack_password" label="Shacknews password (stick to alphanumeric)">
# SHACK_PASSWORD=

#######################################################################################################################
#
# This automated installation script is designed for Ubuntu.  Tested on Ubuntu Server 14.04 LTS 64-bit.
# The following TCP ports should be open: 22, 80, 443.
#
# You must have a Shacknews account dedicated to this.  You must set up the account so all the filters are enabled.
#
# Manual installation instructions (as root):
#   export SHACK_USERNAME=(shacknews username)
#   export SHACK_PASSWORD=(shacknews password)
#   wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/set-up-server.sh
#   (inspect the script to make sure you know what it's going to do)
#   bash set-up-server.sh
#   (will reboot automatically)
#
# To use your newly provisioned server, edit the hosts file (/etc/hosts or C:\windows\system32\drivers\etc\hosts) on
# your local computer to point winchatty.com and www.winchatty.com to your server's IP address.
# Now winchatty.com will resolve to your server and all applications (e.g. Lamp) and sites (e.g. the NiXXeD frontend)
# will use it.  This will let you easily flip back and forth between the real winchatty.com and your instance just by
# editing the hosts file.
#
# Test the frontend at: http://winchatty.com/frontend/  (after editing your hosts file)
#
# Custom upstart services:
#   winchatty-indexer       (Synchronizes the database with the Shack, does not listen on any ports)
#   winchatty-push-server   (Main web server, running on port 80)
#   winchatty-notify-server (Push notification producer, does not listen on any ports)
#   winchatty-search        (Full text indexer, running on port 8081)
#
# Standard services:
#   apache2                 (Web server for WinChatty API, running on port 81)
#   postgresql              (Database server, running on port 5432)
#   pgbouncer               (Database connection pool, running on port 6432)
#
# You can monitor various log files using these commands: (in /usr/bin)
#   log-indexer             (Output of the winchatty-indexer service)
#   log-push-server         (Output of the winchatty-push-server service)
#   log-notify-server       (Output of the winchatty-notify-server service)
#   log-search              (Output of the winchatty-search service)
#   log-apache              (apache2 access log)
#   log-apache-error        (apache2 error log)
#   log-all                 (The last 10 lines from each log file)
#
#######################################################################################################################

export OWNER=me
export DUMP_FILE=chatty-blank.sql.gz

if (( EUID != 0 )); then echo "Must be root."; exit 1; fi
if [ -z "$OWNER" ]; then echo "Missing OWNER."; exit 1; fi
if [ -z "$SHACK_USERNAME" ]; then echo "Missing SHACK_USERNAME."; exit 1; fi
if [ -z "$SHACK_PASSWORD" ]; then echo "Missing SHACK_PASSWORD."; exit 1; fi
if [ -z "$DUMP_FILE" ]; then echo "Missing DUMP_FILE."; exit 1; fi

set -x

cd /tmp

echo "America/Chicago" > /etc/timezone
dpkg-reconfigure --frontend noninteractive tzdata

echo "deb http://apt.linode.com/ stable main" > /etc/apt/sources.list.d/linode.list
wget -O- https://apt.linode.com/linode.gpg | apt-key add -

apt-get update
apt-get -y upgrade
apt-get -y install apache2 postgresql pgbouncer php5 php5-pgsql php5-curl php5-cli php5-tidy php-apc php-pear \
   libapache2-mod-php5 nodejs nodejs-legacy npm build-essential zip unzip git s3cmd htop pv mc openssl linode-cli \
   libdigest-hmac-perl
npm install -g bower gulp

echo 127.0.0.1 winchatty.com >> /etc/hosts
echo 127.0.0.1 www.winchatty.com >> /etc/hosts

useradd -g www-data -m $OWNER

mkdir /home/chatty
pushd /home/chatty
chown $OWNER:www-data .
sudo -H -u $OWNER git clone --recursive https://github.com/electroly/winchatty-server.git backend
sudo -u $OWNER mkdir backend-data
sudo -H -u $OWNER git clone --recursive https://github.com/electroly/duct-tape-search.git search
sudo -u $OWNER mkdir search-data
sudo -H -u $OWNER git clone --recursive https://github.com/NiXXeD/chatty.git frontend
sudo -H -u $OWNER git clone --recursive https://github.com/rtdp/s3curl.git s3curl
cp s3curl/s3curl.pl /usr/bin/
chmod +x /usr/bin/s3curl.pl
popd

pushd /home/chatty/backend/include
echo "<?" > ConfigUserPass.php
echo "define('WINCHATTY_USERNAME', '$SHACK_USERNAME');" >> ConfigUserPass.php
echo "define('WINCHATTY_PASSWORD', '$SHACK_PASSWORD');" >> ConfigUserPass.php
chown $OWNER:www-data ConfigUserPass.php
sed "s/html_scraping_indexer/solo_indexer/g" Config.php | sed "s/pgsql-search/duct-tape/g" > Config.php.new
mv -f Config.php.new Config.php
chown $OWNER:www-data Config.php
popd

pushd /home/chatty/backend/push-server
sudo -H -u $OWNER npm install
popd

pushd /home/chatty/frontend
sudo -H -u $OWNER npm install
sudo -H -u $OWNER bower install --config.interactive=false
sudo -H -u $OWNER gulp build
sudo -H -u $OWNER mv build/* /home/chatty/backend/frontend/
popd

mkdir /home/chatty/sslcert
pushd /home/chatty/sslcert
chown $OWNER:www-data .
sudo -H -u $OWNER openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout winchatty.key -out winchatty_com.crt \
   -subj "/C=US/ST=WI/L=Point Place/O=WinChatty/OU=/CN=winchatty.com"
popd

mkdir /mnt/websites
chown $OWNER:www-data /mnt/websites
sudo -u $OWNER ln -s /home/chatty/backend /mnt/websites/winchatty.com

mkdir /mnt/websites/_private
chown $OWNER:www-data /mnt/websites/_private
sudo -u $OWNER ln -s /home/chatty/sslcert /mnt/websites/_private/winchatty_ssl_certificate
mkdir /mnt/ssd
chown $OWNER:www-data /mnt/ssd
sudo -u $OWNER ln -s /home/chatty/backend-data /mnt/ssd/ChattyIndex
sudo -u $OWNER touch /mnt/ssd/ChattyIndex/ForceReadNewPosts
chmod a+rw /mnt/ssd/ChattyIndex/ForceReadNewPosts
sudo -u $OWNER echo 0 > /mnt/ssd/ChattyIndex/LastEventID

pushd /home/chatty/search
make install
popd

pushd /home/chatty/backend/deployment
sed "s/'UTC'/'America\/Chicago'/g" /etc/postgresql/*/main/postgresql.conf > /tmp/postgresql.conf.new
mv -f /tmp/postgresql.conf.new /etc/postgresql/*/main/postgresql.conf
cp -f pgbouncer/pgbouncer.ini /etc/pgbouncer/
cp -f pgbouncer/userlist.txt /etc/pgbouncer/
rm -f /var/log/postgresql/pgbouncer.log
sudo -u postgres ln -s /dev/null /var/log/postgresql/pgbouncer.log
echo START=1 > /etc/default/pgbouncer
cp -f apache/default /etc/apache2/sites-available/000-default.conf
cp -f apache/apache2.conf /etc/apache2/
cp -f apache/ports.conf /etc/apache2/
cp -f apache/negotiation.conf /etc/apache2/mods-enabled/
a2enmod rewrite
cp -f php/php-apache.ini /etc/php5/apache2/php.ini
cp -f php/php-cli.ini /etc/php5/cli/php.ini
sed "s/USERNAME/$OWNER/g" upstart/winchatty-indexer.conf > /etc/init/winchatty-indexer.conf
sed "s/USERNAME/$OWNER/g" upstart/winchatty-push-server.conf > /etc/init/winchatty-push-server.conf
sed "s/USERNAME/$OWNER/g" upstart/winchatty-notify-server.conf > /etc/init/winchatty-notify-server.conf
sed "s/USERNAME/$OWNER/g" upstart/winchatty-search.conf > /etc/init/winchatty-search.conf
cp -f bin/log-apache /usr/bin/
cp -f bin/log-apache-error /usr/bin/
cp -f bin/log-indexer /usr/bin/
cp -f bin/log-search /usr/bin/
cp -f bin/log-push-server /usr/bin/
cp -f bin/log-notify-server /usr/bin/
cp -f bin/log-all /usr/bin/
popd

/etc/init.d/postgresql restart
sudo -H -u postgres psql --command "CREATE USER nusearch WITH PASSWORD 'nusearch';"
sudo -H -u postgres createdb -E UTF8 -O nusearch chatty
curl http://s3.amazonaws.com/winchatty/$DUMP_FILE | gunzip -c | sudo -H -u postgres psql chatty
sudo -H -u postgres psql -f /home/chatty/backend/deployment/upgrade-db.sql chatty

reboot
