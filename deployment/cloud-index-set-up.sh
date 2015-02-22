#!/bin/bash
# This block defines the variables the user of the script needs to input
# when deploying using this script.
#
#<UDF name="start_id" label="First ID to index">
# STARTID=
#
#<UDF name="end_id" label="Last ID to index">
# ENDID=
#
#<UDF name="s3_accesskey" label="S3 access key">
# ACCESSKEY=
#
#<UDF name="s3_secretkey" label="S3 secret key">
# SECRETKEY=
#
#<UDF name="s3_bucket" label="S3 bucket">
# BUCKET=

cd /tmp
export OWNER=me
export SHACK_USERNAME=unused
export SHACK_PASSWORD=unused
wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/set-up-server.sh
bash set-up-server.sh

sudo -H -u postgres psql --command "DROP DATABASE chatty"
/etc/init.d/postgresql stop
rm -rf /var/lib/postgresql/9.3/main/
pushd /var/lib/postgresql/9.3/
curl http://s3.amazonaws.com/winchatty/chatty-fsbackup-2015-02-22.tar.gz | tar zx
popd
/etc/init.d/postgresql start
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

wget https://raw.githubusercontent.com/electroly/winchatty-server/master/deployment/s3cmd/s3cfg
cat s3cfg | sed "s/ACCESSKEY/$ACCESSKEY/g" | sed "s/SECRETKEY/$SECRETKEY/g" > ~/.s3cfg
s3cmd put /tmp/dts-index-chunk* s3://$BUCKET/dts-index-chunk-$STARTID.tar.gz
