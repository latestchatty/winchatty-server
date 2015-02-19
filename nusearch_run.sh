#!/bin/sh

cd /mnt/websites/winchatty.com/indexer-server/

export INDEXER_SCRIPT=`php5 which_indexer_script.php`

while true
do
   echo Started $INDEXER_SCRIPT at `date`
   php5 $INDEXER_SCRIPT
   sleep 5
done
