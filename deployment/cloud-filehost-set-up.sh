#!/bin/bash
# This is intended to be a Linode StackScript but can be used manually by exporting these variables:
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
#<UDF name="pgsqlbackupname" label="Name of the pgsql fs-level backup in the S3 bucket">
# PGSQLBACKUPNAME=
#
#<UDF name="httppass" label="Password for the HTTPd">
# HTTPPASS=

if (( EUID != 0 )); then echo "Must be root."; exit 1; fi
if [ -z "$ACCESSKEY" ]; then echo "Missing ACCESSKEY."; exit 1; fi
if [ -z "$SECRETKEY" ]; then echo "Missing SECRETKEY."; exit 1; fi
if [ -z "$BUCKET" ]; then echo "Missing BUCKET."; exit 1; fi
if [ -z "$PGSQLBACKUPNAME" ]; then echo "Missing PGSQLBACKUPNAME."; exit 1; fi

set -x

apt-get -y install apache2 libdigest-hmac-perl unzip apache2-utils s3cmd
cd /tmp

wget https://github.com/rtdp/s3curl/archive/master.zip
unzip master.zip
cp s3curl-master/s3curl.pl /usr/bin/
chmod +x /usr/bin/s3curl.pl

wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/s3cmd/s3cfg
cat s3cfg | sed "s/ACCESSKEY/$ACCESSKEY/" | sed "s/SECRETKEY/$SECRETKEY/" > /root/.s3cfg
echo "%awsSecretAccessKeys = ( personal => { id => 'ACCESSKEY', key => 'SECRETKEY' } );" | sed "s/ACCESSKEY/$ACCESSKEY/" | sed "s/SECRETKEY/$SECRETKEY/" | sed "s/\\\//g" > /root/.s3curl
chmod 600 /root/.s3curl

wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/filehost/apache-default
mv apache-default /etc/apache2/sites-available/000-default.conf
apache2ctl restart

mkdir /home/secure/
htpasswd -bc /home/secure/passwords "winchatty" "$HTTPPASS"
chown www-data:www-data /home/secure/passwords

wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/filehost/htaccess
chown www-data:www-data htaccess
mv htaccess /var/www/html/.htaccess

s3curl.pl --id=personal -- http://s3.amazonaws.com/$BUCKET/$PGSQLBACKUPNAME > /var/www/html/$PGSQLBACKUPNAME

export IP=`ifconfig eth0 2>/dev/null|awk '/inet addr:/ {print $2}'|sed 's/addr://'`
echo "http://$IP/$PGSQLBACKUPNAME" > winchatty_filehost_url
s3cmd put winchatty_filehost_url s3://$BUCKET/winchatty_filehost_url
