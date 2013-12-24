<?
require_once '../../include/Global.php';

class NetChattyService
{
   private function isValidUser($username, $password)
   {
      # Validate this username/password by attempting to retrieve the number
      # of unread Shackmessages.
      try 
      {
         MessageParser()->getMessageCount($username, $password);
         return true;
      }
      catch (Exception $e)
      {
         return false;
      }
   }

   public function getUserOptions($username, $password)
   {
      $username = strtolower($username);
   
      if (!$this->isValidUser($username, $password))
         return array('success' => false);
      
      # If the user has never logged in before, then they won't have an 
      # options file.
      $filename = data_directory . 'NetChatty/' . sha1($username);
      if (!file_exists($filename))
         return array('success' => true, 
                      'data'    => array());
      else
         return array('success'  => true, 
                      'data'     => deserialize(file_get_contents($filename)));
   }
   
   public function setUserOptions($username, $password, $optionsJSON)
   {
      $username = strtolower($username);
   
      if ($this->isValidUser($username, $password))
      {
         $filename = data_directory . 'NetChatty/' . sha1($username);
         $options = json_decode($optionsJSON);
         file_put_contents($filename, serialize($options));
         return array('success' => true);
      }
      else
      {
         return array('success' => false);
      }
   }
}
