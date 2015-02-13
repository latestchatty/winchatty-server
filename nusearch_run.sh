#!/bin/sh

cd /mnt/websites/winchatty.com/
while true
do
   echo Started at `date`
   php5 nusearch_index.php
   sleep 5
done
