#!/bin/bash

clear
echo ================================================================================

while true
do
   time node push-server/index.js
   echo
   echo -n "Finished at "
   date
   echo ================================================================================
   sleep 5
done
