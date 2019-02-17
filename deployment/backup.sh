#!/bin/bash
# Expects these environment variables:
#  BUCKET=<Amazon S3 bucket name, must already exist>

if (( EUID != 0 )); then echo "Must be root."; exit 1; fi
if [ -z "$BUCKET" ]; then echo "Missing BUCKET."; exit 1; fi

if [ ! -f /root/.s3cfg ]; then
   echo "You must run 's3cmd --configure' first."
   exit 1
fi

export TIMESTAMP=`date +%Y-%m-%d_%Hh%M`
export PGSQLBACKUP_FILENAME=chatty_pgsqlbackup_$TIMESTAMP.sql.gz
export FILESBACKUP_FILENAME=chatty_files_$TIMESTAMP.tar.gz

set -x

pushd /home/
tar zcf - chatty/ | pv > /tmp/$FILESBACKUP_FILENAME
popd

pushd /tmp/
sudo -u postgres pg_dump --create chatty | gzip -c | pv > $PGSQLBACKUP_FILENAME
s3cmd put $PGSQLBACKUP_FILENAME s3://$BUCKET/$PGSQLBACKUP_FILENAME
s3cmd put $FILESBACKUP_FILENAME s3://$BUCKET/$FILESBACKUP_FILENAME
rm -f $PGSQLBACKUP_FILENAME
rm -f $FILESBACKUP_FILENAME
popd
