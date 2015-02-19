#!/bin/sh

cd /mnt/websites/winchatty.com/indexer-server/


while true
do
   export INDEXER_SCRIPT=`php5 which_indexer_script.php`
   echo
   echo Started $INDEXER_SCRIPT at `date`
   php5 $INDEXER_SCRIPT
   sleep 5
done
