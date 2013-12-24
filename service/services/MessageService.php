<?
require_once '../../include/Global.php';

class MessageService
{
   public function getMessages($folder, $username, $password, $page, $messagesPerPage)
   {
      return MessageParser()->getMessages($folder, $username, $password, $page, $messagesPerPage);
   }
   
   public function sendMessage($username, $password, $recipient, $subject, $body)
   {
      return MessageParser()->sendMessage($username, $password, $recipient, $subject, $body);
   }
   
   public function deleteMessage($username, $password, $id)
   {
      return MessageParser()->deleteMessage($username, $password, $id);
   }

   public function archiveMessage($username, $password, $id)
   {
      return MessageParser()->archiveMessage($username, $password, $id);
   }
   
   public function getMessageCount($username, $password)
   {
      return MessageParser()->getMessageCount($username, $password);
   }
   
   public function markMessageAsRead($username, $password, $id)
   {
      return MessageParser()->markMessageAsRead($username, $password, $id);
   }
}
