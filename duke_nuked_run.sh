#!/bin/bash

clear
echo ================================================================================

while true
do
   time php5 duke_nuked.php
   echo
   echo -n "Finished at "
   date
   echo ================================================================================
   sleep 300
done
