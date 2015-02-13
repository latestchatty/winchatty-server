#!/bin/sh

LOG_FILE=/tmp/winchatty-push-server.log

while true
do
   echo Started at `date` >> $LOG_FILE
   node push-server/index.js | tee -a $LOG_FILE
   sleep 5
done
