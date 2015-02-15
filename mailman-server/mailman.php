<?
# WinChatty Server
# Copyright (C) 2015 Brian Luft
# 
# This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public 
# License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later 
# version.
# 
# This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
# warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
# details.
# 
# You should have received a copy of the GNU General Public License along with this program; if not, write to the Free 
# Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

define('DELAY_SEC', 60);
define('LISTS_FILE_PATH', 'lists.json');
define('SUBSCRIBERS_FILE_PATH', 'subscribers.json');

function api_post($url, $args) {
   $curl = curl_init();
   $query = http_build_query($args);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
   curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty Mailman');
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
   $raw_response = curl_exec($curl);
   if (is_null($raw_response) || empty($raw_response) || $raw_response[0] != '{')
      die("Non-JSON response: $raw_response\n");
   $response = json_decode($raw_response, true);
   if (isset($response['error']))
      die($response['code'] . ' - ' . $response['message']);
   else
      return $response;
}

function get_lists() {
   return json_decode(file_get_contents(LISTS_FILE_PATH), true);
}

function get_subscribers($list_name) {
   $file = json_decode(file_get_contents(SUBSCRIBERS_FILE_PATH), true);
   if (isset($file[$list_name]))
      return $file[$list_name];
   else
      return array();
}

function get_messages($username, $password, $page) {
   echo "get_messages($username, ####, $page)\n";
   return api_post('http://winchatty.com/v2/getMessages', array(
      'username' => $username,
      'password' => $password,
      'folder' => 'inbox',
      'page' => $page
   ));
}

function mark_message_read($username, $password, $message_id) {
   echo "mark_message_read($username, ####, $message_id)\n";
   return api_post('http://winchatty.com/v2/markMessageRead', array(
      'username' => $username,
      'password' => $password,
      'messageId' => $message_id
   ));
}

function send_message($username, $password, $recipient, $subject, $body) {
   echo "send_message($username, ####, $recipient, $subject, ####)\n";
   return api_post('http://winchatty.com/v2/sendMessage', array(
      'username' => $username,
      'password' => $password,
      'to' => $recipient,
      'subject' => $subject,
      'body' => $body
   ));
}

function handle_new_message($username, $password, $message) {
   $subscribers = get_subscribers($username);
   
   // make sure this person is actually a subscriber and not some rando
   $is_subscriber = false;
   foreach ($subscribers as $subscriber) {
      if (strtolower($subscriber) == strtolower($message['from'])) {
         $is_subscriber = true;
         break;
      }
   }
   
   if ($is_subscriber) {
      // it's legit; relay this message to all subscribers
      $relayed_body = '*** ' . $message['from'] . " wrote:\r\n" . $message['body'];
      $relayed_subject = trim($message['subject']);
      $bracket_pos = strpos($relayed_subject, '[');
      if ($bracket_pos !== false) {
         $relayed_subject = trim(substr($relayed_subject, 0, $bracket_pos));
      }
      $relayed_subject .= ' [from ' . $message['from'] . ']';
      foreach ($subscribers as $subscriber)
         send_message($username, $password, $subscriber, $relayed_subject, $relayed_body);
   } else {
      // not a subscriber; send them back a message telling them wtf
      send_message($username, $password, $message['from'], 'Re: ' . $message['subject'],
         'You are not a member of this mailing list.');
   }
}

foreach (get_lists() as $list_info) {
   $username = $list_info['username'];
   $password = $list_info['password'];
   $done = false;
   
   for ($page = 1; !$done; $page++) {
      $message_page = get_messages($username, $password, $page);
      if (empty($message_page['messages']))
         break;
      foreach ($message_page['messages'] as $message) {
         if ($message['unread'] === true) {
            mark_message_read($username, $password, $message['id']);
            handle_new_message($username, $password, $message);
         } else {
            $done = true;
            break;
         }
      }
   }
}
