<?
# WinChatty Server
# Copyright (C) 2013 Brian Luft
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

class MessageParser extends Parser
{
   public function getMessages($folder, $username, $password, $page, $unused)
   {
      if ($folder == 'outbox')
         $folder = 'sent';
      else if ($folder == 'archive')
         throw new Exception("Archive no longer exists on Shacknews.");
   
      if ($page < 1)
         throw new Exception('Invalid page.');
      if ($folder != 'inbox' && $folder != 'sent')
         throw new Exception('Invalid folder name.');
         
      $messagesPerPage = 50; # Now hard-coded on Shacknews.
      
      $this->init($this->userDownload("http://www.shacknews.com/messages/$folder?page=$page", $username, $password));

      $o = array(
         'current_page' => $page,
         'last_page'    => 0,
         'total'        => 0,
         'unread'       => 0,
         'messages'     => array());

      # Skip down to the start of the Message Center
      $this->seek(1, '<div class="large-label-black">Message Center</div>');

      if ($this->peek(1, '<div class="showing-column">') === false)
      {
         $o['total'] = 0;
         $o['last_page'] = 1;
      }
      else
      {
         # <div class="showing-column">showing 1-50 of 1717</div> 
         $o['total'] = intval($this->clip(
            array('<div class="showing-column">', '>', 'of', ' '),
            '</div>'));
            
         # Compute the last_page based on the total.
         $o['last_page'] = ceil($o['total'] / $messagesPerPage);

         # Read the individual messages
         $this->seek(1, '<ul id="messages">');
      }
      
      while ($this->peek(1, '<li class="message') !== false)
      {
         $m = array(
            'id'      => false,
            'from'    => false,
            'to'      => false,
            'subject' => false,
            'date'    => false,
            'body'    => false,
            'unread'  => false);         
         
         # <li class="message read"> ... </li>
         # <li class="message"> ... </li>
         $liClasses = $this->clip(
            array('<li class="message', '"'),
            '"');
         
         if (strpos($liClasses, 'read') === false)
         {
            $m['unread'] = true;
            $o['unread']++;
         }
         
         # <input type="checkbox" class="mid" name="messages[]" value="1459438">
         $m['id'] = $this->clip(
            array('<input type="checkbox" class="mid" name="messages[]"', 'value=', '"'),
            '">');

         # <div>To:&nbsp;<span class="message-username">greg-m</span></div> 
         $otherUser = $this->clip(
            array('<span class="message-username"', '>'),
            '</span>');
         
         if ($folder == 'inbox')
         {
            $m['from'] = $otherUser;
            $m['to'] = $username;
         }
         else # outbox
         {
            $m['from'] = $username;
            $m['to'] = $otherUser;
         }
         
         # <div>Subject:&nbsp;<span class="message-subject">Re: New Chatty API</span></div>
         $m['subject'] = $this->clip(
            array('<span class="message-subject"', '>'),
            '</span>');
         
         # <div>Date:&nbsp;<span class="message-date">February 15, 2011, 1:02 pm</span></div> 
         $m['date'] = $this->clip(
            array('<span class="message-date"', '>'),
            '</span>');
         
         # <div class="message-body">Sounds great.  I just shot you an email.</div> 
         $m['body'] = $this->clip(
            array('<div class="message-body"', '>'),
            '</div>');
            
         $o['messages'][] = $m;
      }
      
      return $o;
   }

   public function getUserID($username, $password)
   {
      $this->init($this->userDownload("http://www.shacknews.com/messages", $username, $password));
      
      # <input type="hidden" name="uid" value="172215"> 
      return $this->clip(
         array('<input type="hidden" name="uid"', 'value=', '"'),
         '">');
   }

   public function sendMessage($username, $password, $recipient, $subject, $body)
   {
      $body = str_replace("\r", "", $body);
      $url = 'http://www.shacknews.com/messages/send';
      $postArgs =
         'uid='      . urlencode($this->getUserID($username, $password)) .
         '&to='      . urlencode($recipient) .
         '&subject=' . urlencode($subject) .
         '&message=' . urlencode($body);

      $this->userDownload($url, $username, $password, $postArgs);
      return true;
   }
   
   public function deleteMessage($username, $password, $id)
   {
      $url = 'http://www.shacknews.com/messages/delete';

      # Since we don't know whether it's in the inbox or the outbox, and
      # I don't want to change the interface to provide that information,
      # we will just try both.  Deal with it.
      $postArgs =
         'mid='   . urlencode($id) .
         '&type=' . 'inbox';
      $this->userDownload($url, $username, $password, $postArgs);

      $postArgs =
         'mid='   . urlencode($id) .
         '&type=' . 'sent';
      $this->userDownload($url, $username, $password, $postArgs);

      return true;
   }

   public function deleteMessageInFolder($username, $password, $id, $folder)
   {
      if ($folder != 'inbox' && $folder != 'sent')
         throw new Exception('Folder must be "inbox" or "sent".');

      $url = 'http://www.shacknews.com/messages/delete';

      $postArgs =
         'mid='   . urlencode($id) .
         '&type=' . $folder;
      $this->userDownload($url, $username, $password, $postArgs);

      return true;
   }

   public function archiveMessage($username, $password, $id)
   {
      throw new Exception("Archive no longer exists on Shacknews.");
   }
   
   public function markMessageAsRead($username, $password, $id)
   {
      $url = 'http://www.shacknews.com/messages/read';
      $postArgs = 
         'mid=' . urlencode($id);

      $this->userDownload($url, $username, $password, $postArgs);
      return true;
   }
   
   public function getMessageCount($username, $password)
   {
      $inbox = $this->getMessages('inbox', $username, $password, 1, 25);

      return array(
         'unread' => $inbox['unread'],
         'total'  => $inbox['total']);
   }
}

function MessageParser()
{
   return new MessageParser();
}