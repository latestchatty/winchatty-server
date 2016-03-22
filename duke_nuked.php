<?
if (php_sapi_name() !== 'cli')
   die('Must be run from the command line.');

require_once 'include/Global.php';

$lastMessageIdFilePath = '/mnt/websites/_private/duke_nuked_last_message_id.txt';
$passwordFilePath = '/mnt/websites/_private/duke_nuked_password.txt';
$threadIdFilePath = '/mnt/websites/_private/duke_nuked_thread.txt';
$slackTokenFilePath = '/mnt/websites/_private/slack_token.txt';

function buildQueryString($query)
{
   $x = array();
   foreach ($query as $key => $value)
   {
      $x[] = urlencode($key) . '=' . urlencode($value);
   }
   return implode('&', $x);
}


$lastMessageId = intval(trim(file_get_contents($lastMessageIdFilePath)));
$password = trim(file_get_contents($passwordFilePath));
$threadId = intval(trim(file_get_contents($threadIdFilePath)));
if ($threadId <= 0)
{
   echo "No thread ID set!\n";
   # This requires manual intervention.  Inhibit the cycle from continuing.  Admin has to Ctrl+C and fix it.
   while (true) { sleep(60); }
}

$messagesPage = MessageParser()->getMessages('inbox', 'Duke Nuked', $password, 1, 0);
$messages = $messagesPage['messages'];

$maxId = 0;

foreach ($messages as $message)
{
   $id = intval($message['id']);
   $unread = $message['unread'] == 1;

   if ($id > $maxId)
   {
      $maxId = $id;
      file_put_contents($lastMessageIdFilePath, $maxId);

      if (intval(trim(file_get_contents($lastMessageIdFilePath))) != $maxId)
      {
         echo "Failed to write to file!\n";
         # This requires manual intervention.  Inhibit the cycle from continuing.  Admin has to Ctrl+C and fix it.
         while (true) { sleep(60); }
      }
   }
   
   if ($id > $lastMessageId && $unread)
   {
      MessageParser()->markMessageAsRead('Duke Nuked', $password, $id);
      
      $body =
         'From: y{' . $message['from'] . "}y\r\n" .
         'Subject: ' . $message['subject'] . "\r\n" .
         'Date: ' . date('D M j, Y H:i', strtotime($message['date'])) . "\r\n" .
         "\r\n" .
         strip_tags($message['body']);
      
      echo "$body\n";
      
      ChattyParser()->post('Duke Nuked', $password, $threadId, 0, $body, 99, 99);

      if (file_exists($slackTokenFilePath))
      {
         # Also post on Slack.
         $slackBody =
            'From: ' . $message['from'] . "\r\n" .
            'Subject: ' . $message['subject'] . "\r\n" .
            "\r\n" .
            strip_tags($message['body']);
         $slackQueryString = buildQueryString(array(
            'token' => trim(file_get_contents($slackTokenFilePath)),
            'channel' => '#duke-nuked',
            'text' => htmlspecialchars($slackBody, ENT_NOQUOTES),
            'username' => 'duke nuked',
            'parse' => 'none',
            'link_names' => 0));
         $slackUrl = 'https://slack.com/api/chat.postMessage?' . $slackQueryString;
         $slackResult = file_get_contents($slackUrl);
         print_r($slackResult);
      }
   }
}

