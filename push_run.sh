#!/bin/sh

cd /mnt/websites/winchatty.com/
while true
do
   echo Started at `date`
   node push-server/index.js
   sleep 5
done
