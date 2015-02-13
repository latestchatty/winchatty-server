#!/bin/sh

LOG_FILE=/tmp/winchatty-indexer.log

while true
do
   echo Started at `date` >> $LOG_FILE
   php5 nusearch_index.php | tee -a $LOG_FILE
   sleep 5
done
