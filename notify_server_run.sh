#!/bin/bash

clear
echo ================================================================================

while true
do
   time php5 notify_server.php
   echo
   echo -n "Finished at "
   date
   echo ================================================================================
   sleep 5
done
