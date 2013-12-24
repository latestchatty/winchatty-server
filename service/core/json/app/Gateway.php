<?php
define("AMFPHP_BASE", realpath(dirname(dirname(dirname(__FILE__)))) . "/");
require_once(AMFPHP_BASE . "shared/app/BasicGateway.php");
require_once(AMFPHP_BASE . "shared/util/MessageBody.php");
require_once(AMFPHP_BASE . "shared/util/functions.php");
require_once(AMFPHP_BASE . "json/app/Actions.php");


class Gateway extends BasicGateway
{
	function createBody()
	{
		$GLOBALS['amfphp']['encoding'] = 'json';
		$body = & new MessageBody();
		
		$uri = $_SERVER['PHP_SELF']; #__setUri();
		$elements = explode('/json.php', $uri);

      $rawArgs = array();

      # Parameters can be passed via numbered POST arguments. (BRL 4/3/11)
      #   <input name="arg0" value="ChattyService.getStory">
      #   <input name="arg1" value="0">
      #   <input name="arg2" value="1">
      if (isset($_POST['arg0']))
      {
         for ($i = 0; isset($_POST["arg$i"]); $i++)
            $rawArgs[] = $_POST["arg$i"];
      }
      else
      {
         if(strlen($elements[1]) == 0)
         {
            echo("The JSON gateway is installed correctly.");
            exit();
         }
         $args = substr($elements[1], 1);
         $rawArgs = explode('/', $args);
         
         if(isset($GLOBALS['HTTP_RAW_POST_DATA']))
         {
            $rawArgs[] = $GLOBALS['HTTP_RAW_POST_DATA'];
         }
      }

		$body->setValue($rawArgs);
		return $body;
	}
	
	/**
	 * Create the chain of actions
	 */
	function registerActionChain()
	{
		$this->actions['deserialization'] = 'deserializationAction';
		$this->actions['classLoader'] = 'classLoaderAction';
		$this->actions['security'] = 'securityAction';
		$this->actions['exec'] = 'executionAction';
		$this->actions['serialization'] = 'serializationAction';
	}
}
?>