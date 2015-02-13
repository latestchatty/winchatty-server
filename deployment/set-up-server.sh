#!/bin/bash

# This automated installation script is designed for Ubuntu.  Tested on Ubuntu Server 14.04 LTS 64-bit.
#
# You must have a Shacknews account dedicated to this.  You must set up the account so all the filters are enabled.
#
# You can choose from two different database dumps by setting $DUMP_FILE:
#   chatty-2015-02-12.sql.gz  (a complete database snapshot; requires 30GB of disk space)
#   chatty-sample.sql.gz      (a small subset of posts; requires 1GB of disk space)
#
# Restoring the complete database can take several hours.  The sample database takes only a few minutes.
#
# Installation instructions (as root):
#   export OWNER=(name of the new unix user that will own all site files and processes)
#   export SHACK_USERNAME=(shacknews username)
#   export SHACK_PASSWORD=(shacknews password)
#   export DUMP_FILE=(filename of the sql dump to use, see above)
#   curl https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/set-up-server.sh | bash
#   passwd $OWNER
#   reboot
#
# To use your newly provisioned server, on your local computer edit the hosts file (/etc/hosts or
# C:\windows\system32\drivers\etc\hosts) to point winchatty.com to your server's IP address.  Now winchatty.com will
# resolve to your server and all applications (Lamp) and sites (the NiXXeD frontend) will use it.  This will let you
# easily flip back and forth between the real winchatty.com and your instance just by editing the hosts file.

if (( EUID != 0 )); then echo "Must be root."; exit 1; fi
if [ -z "$OWNER" ]; then echo "Missing USER."; exit 1; fi
if [ -z "$SHACK_USERNAME" ]; then echo "Missing SHACK_USERNAME."; exit 1; fi
if [ -z "$SHACK_PASSWORD" ]; then echo "Missing SHACK_PASSWORD."; exit 1; fi
if [ -z "$DUMP_FILE" ]; then echo "Missing DUMP_FILE."; exit 1; fi

set -x

apt-get update
apt-get -y upgrade
apt-get -y install apache2 postgresql pgbouncer php5 php5-pgsql php5-curl php5-cli php-apc php-pear \
   libapache2-mod-php5 nodejs nodejs-legacy npm build-essential zip unzip git s3cmd htop pv mc

echo "America/Chicago" > /etc/timezone
dpkg-reconfigure --frontend noninteractive tzdata

useradd -g www-data -m $OWNER

mkdir /home/chatty
chown $OWNER:www-data /home/chatty

pushd /home/chatty
sudo -H -u $OWNER git clone --recursive https://github.com/electroly/winchatty-server.git backend
sudo -u $OWNER mkdir backend-data
sudo -H -u $OWNER git clone --recursive https://github.com/NiXXeD/chatty.git frontend
chmod 744 backend/*.sh
popd

pushd /home/chatty/backend/include
echo "<?" > ConfigUserPass.php
echo "define('WINCHATTY_USERNAME', '$SHACK_USERNAME');" >> ConfigUserPass.php
echo "define('WINCHATTY_PASSWORD', '$SHACK_PASSWORD');" >> ConfigUserPass.php
chown $OWNER:www-data ConfigUserPass.php
popd

pushd /home/chatty/backend/push-server
sudo -H -u $OWNER npm install
popd

mkdir /mnt/websites
chown $OWNER:www-data /mnt/websites
sudo -u $OWNER ln -s /home/chatty/backend /mnt/websites/winchatty.com

mkdir /mnt/ssd
chown $OWNER:www-data /mnt/ssd
sudo -u $OWNER ln -s /home/chatty/backend-data /mnt/ssd/ChattyIndex

pushd /home/chatty/backend/deployment
cp -f pgbouncer/pgbouncer.ini /etc/pgbouncer/
cp -f pgbouncer/userlist.txt /etc/pgbouncer/
echo START=1 > /etc/default/pgbouncer
cp -f apache/default /etc/apache2/sites-available/
cp -f apache/apache2.conf /etc/apache2/
cp -f apache/ports.conf /etc/apache2/
cp -f php/php-apache.ini /etc/php5/apache2/php.ini
cp -f php/php-cli.ini /etc/php5/cli/php.ini
sed "s/USERNAME/$OWNER/g" upstart/winchatty-indexer.conf > /etc/init/winchatty-indexer.conf
sed "s/USERNAME/$OWNER/g" upstart/winchatty-push-server.conf > /etc/init/winchatty-push-server.conf
popd

sudo -u postgres psql --command "CREATE USER nusearch WITH PASSWORD 'nusearch';"
sudo -u postgres createdb -E UTF8 -O nusearch chatty
curl http://s3.amazonaws.com/winchatty/$DUMP_FILE | gunzip -c | sudo -u postgres psql chatty
