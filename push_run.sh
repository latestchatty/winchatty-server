#!/bin/bash

clear
echo ================================================================================

while true
do
   time node push-server/index.js | tee -a push-server/access.log
   echo
   echo -n "Finished at "
   date
   echo ================================================================================
   sleep 5
done
