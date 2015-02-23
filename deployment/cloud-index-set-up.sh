#!/bin/bash
# This is intended to be a Linode StackScript but can be used manually by exporting these variables:
#
#<UDF name="startid" label="First ID to index">
# STARTID=
#
#<UDF name="endid" label="Last ID to index">
# ENDID=
#
#<UDF name="accesskey" label="S3 access key (with / slashes escaped)">
# ACCESSKEY=
#
#<UDF name="secretkey" label="S3 secret key (with / slashes escaped)">
# SECRETKEY=
#
#<UDF name="bucket" label="S3 bucket">
# BUCKET=
#
#<UDF name="pgsqlbackupurl" label="URL of the pgsql fs-level backup">
# PGSQLBACKUPURL=
#
#<UDF name="pgsqlbackuppass" label="HTTP password for downloading the pgsql backup">
# PGSQLBACKUPPASS=

export OWNER=me
export SHACK_USERNAME=unused
export SHACK_PASSWORD=unused
export DUMP_FILE=chatty-blank.sql.gz

if (( EUID != 0 )); then echo "Must be root."; exit 1; fi
if [ -z "$STARTID" ]; then echo "Missing STARTID."; exit 1; fi
if [ -z "$ENDID" ]; then echo "Missing ENDID."; exit 1; fi
if [ -z "$ACCESSKEY" ]; then echo "Missing ACCESSKEY."; exit 1; fi
if [ -z "$SECRETKEY" ]; then echo "Missing SECRETKEY."; exit 1; fi
if [ -z "$BUCKET" ]; then echo "Missing BUCKET."; exit 1; fi
if [ -z "$PGSQLBACKUPURL" ]; then echo "Missing PGSQLBACKUPURL."; exit 1; fi
if [ -z "$PGSQLBACKUPPASS" ]; then echo "Missing PGSQLBACKUPPASS."; exit 1; fi

cd /tmp

apt-get update
apt-get -y upgrade
apt-get -y install apache2 postgresql pgbouncer php5 php5-pgsql php5-curl php5-cli php5-tidy php-apc php-pear \
   libapache2-mod-php5 build-essential zip unzip git s3cmd libdigest-hmac-perl

echo 127.0.0.1 winchatty.com >> /etc/hosts
echo 127.0.0.1 www.winchatty.com >> /etc/hosts

useradd -g www-data -m $OWNER

mkdir /home/chatty
pushd /home/chatty
chown $OWNER:www-data .
sudo -H -u $OWNER git clone --recursive git://github.com/electroly/winchatty-server.git backend
sudo -u $OWNER mkdir backend-data
sudo -H -u $OWNER git clone --recursive git://github.com/electroly/duct-tape-search.git search
sudo -u $OWNER mkdir search-data
sudo -H -u $OWNER git clone --recursive git://github.com/rtdp/s3curl.git s3curl
cp s3curl/s3curl.pl /usr/bin/
chmod +x /usr/bin/s3curl.pl
popd

pushd /home/chatty/backend/include
sed "s/html_scraping_indexer/solo_indexer/g" Config.php | sed "s/pgsql-search/duct-tape/g" > Config.php.new
mv -f Config.php.new Config.php
chown $OWNER:www-data Config.php
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
cp -f php/php-apache.ini /etc/php5/apache2/php.ini
cp -f php/php-cli.ini /etc/php5/cli/php.ini
sed "s/USERNAME/$OWNER/g" upstart/winchatty-search.conf > /etc/init/winchatty-search.conf
popd

cat /home/chatty/backend/deployment/s3cmd/s3cfg | sed "s/ACCESSKEY/$ACCESSKEY/" | sed "s/SECRETKEY/$SECRETKEY/" > /root/.s3cfg
echo "%awsSecretAccessKeys = ( personal => { id => 'ACCESSKEY', key => 'SECRETKEY' } );" | sed "s/ACCESSKEY/$ACCESSKEY/" | sed "s/SECRETKEY/$SECRETKEY/" | sed "s/\\\//g" > /root/.s3curl
chmod 600 /root/.s3curl

/etc/init.d/pgbouncer stop
/etc/init.d/postgresql stop
rm -rf /var/lib/postgresql/9.3/main/
pushd /var/lib/postgresql/9.3/
curl -u "winchatty:$PGSQLBACKUPPASS" "$PGSQLBACKUPURL" | tar zx
popd
/etc/init.d/postgresql start
/etc/init.d/pgbouncer start

start winchatty-search
pushd /home/chatty/backend/
echo $STARTID > /tmp/dts-start
echo $ENDID > /tmp/dts-end
sudo -H -u me php5 dts_reindex.php
popd
stop winchatty-search

pushd /home/chatty/search-data/
tar zcvf /tmp/dts-index-chunk-$STARTID.tar.gz dts-*
popd

s3cmd put /tmp/dts-index-chunk-$STARTID.tar.gz s3://$BUCKET/dts-index-chunk-$STARTID.tar.gz

shutdown -h now
