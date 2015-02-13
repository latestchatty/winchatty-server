#!/bin/sh

# Requires Ubuntu.  Tested on Ubuntu Server 14.04 LTS 64-bit.
# Recommended at least 64GB of SSD storage.  A t2.micro instance can handle it, but restoring the database will take
# a couple hours.  Use a faster instance type if you can.  The real winchatty.com site runs on a Linode instance with
# 4GB RAM and 100GB SSD.
#
# You must have a Shacknews account dedicated to this.  You must set up the account so all the filters are enabled.
# Don't run this on a server that you're already using for something else.
#
# Instructions (as root):
#   USER=<name of the new unix user that will own all site files and processes>
#   SHACK_USERNAME=<shacknews username>
#   SHACK_PASSWORD=<shacknews password>
#   curl https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/set-up-server.sh | sh
#   passwd $USER
#   reboot
#
# To use your newly provisioned server, on your local computer edit the hosts file (/etc/hosts or
# C:\windows\system32\drivers\etc\hosts) to point winchatty.com at your server's IP address.  Now winchatty.com will
# resolve to your server and all applications (Lamp) and sites (the NiXXeD frontend) will use it.  This will let you
# easily flip back and forth between the real winchatty.com and your instance just by editing the hosts file.

apt-get update
apt-get -y upgrade
apt-get -y install apache2 postgresql pgbouncer php5 php5-pgsql php5-cli php-apc php-pear libapache2-mod-php5 nodejs nodejs-legacy npm build-essential zip unzip git s3cmd htop

echo "America/Chicago" > /etc/timezone
dpkg-reconfigure --frontend noninteractive tzdata

useradd -g www-data -m $USER

mkdir /home/chatty
chown $USER:www-data /home/chatty

pushd /home/chatty
sudo -H -u $USER git clone --recursive https://github.com/electroly/winchatty-server.git backend
sudo -u $USER mkdir backend-data
sudo -H -u $USER git clone --recursive https://github.com/NiXXeD/chatty.git frontend
chmod 744 backend/*.sh
popd

pushd /home/chatty/backend/include
sudo -u $USER echo "<?" > ConfigUserPass.php
sudo -u $USER echo "define('WINCHATTY_USERNAME', '$SHACK_USERNAME');" >> ConfigUserPass.php
sudo -u $USER echo "define('WINCHATTY_PASSWORD', '$SHACK_PASSWORD');" >> ConfigUserPass.php
popd

pushd /home/chatty/backend/push-server
sudo -H -u $USER npm install
popd

mkdir /mnt/websites
chown $USER:www-data /mnt/websites
sudo -u $USER ln -s /home/chatty/backend /mnt/websites/winchatty.com

mkdir /mnt/ssd
chown $USER:www-data /mnt/ssd
sudo -u $USER ln -s /home/chatty/backend-data /mnt/ssd/ChattyIndex

pushd /home/chatty/backend/deployment
cp -f pgbouncer/pgbouncer.ini /etc/pgbouncer/
cp -f pgbouncer/userlist.txt /etc/pgbouncer/
cp -f apache/default /etc/apache2/sites-available/
cp -f apache/apache2.conf /etc/apache2/
cp -f apache/ports.conf /etc/apache2/
cp -f php/php-apache.ini /etc/php5/apache2/php.ini
cp -f php/php-cli.ini /etc/php5/cli/php.ini
sed "s/USERNAME/$USER/g" upstart/winchatty-indexer.conf > /etc/init/winchatty-indexer.conf
sed "s/USERNAME/$USER/g" upstart/winchatty-push-server.conf > /etc/init/winchatty-push-server.conf
popd

sudo -u postgres psql --command "CREATE USER nusearch WITH PASSWORD 'nusearch';"
sudo -u postgres createdb -E UTF8 -O nusearch chatty
curl http://s3.amazonaws.com/winchatty/chatty-2015-02-12.sql.gz | gunzip -c | sudo -u postgres psql chatty
