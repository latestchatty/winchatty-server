<?php

#   header('Content-type: application/json; charset=utf-8');
   header('Content-type: text/plain; charset=utf-8');
   header('Access-Control-Allow-Origin: *');

	
	/**
	 * JSON gateway
	 */
	
	include("globals.php");
	
	include "core/json/app/Gateway.php";
	
	$gateway = new Gateway();
	
	$gateway->setBaseClassPath($servicesPath);
	
	$gateway->service();
?>