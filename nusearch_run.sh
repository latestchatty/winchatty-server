#!/bin/sh

cd /mnt/websites/winchatty.com/
while true
do
   echo Started at `date`
   php5 indexer-server/html_scraping_indexer.php
   sleep 5
done
