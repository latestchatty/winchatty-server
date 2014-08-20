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

require_once '../../../include/Global.php';
$pg = nsc_connectToDatabase();
session_start();

if (!isset($_SESSION['username']))
{
   header('Location: https://winchatty.com/v2/notifications/ui/login');
   die();
}

$username = strtolower($_SESSION['username']);

if (nsc_selectValueOrFalse($pg, 'SELECT username FROM notify_user WHERE username = $1', array($username)) === false)
{
   nsc_execute($pg, 'INSERT INTO notify_user (username, match_replies, match_mentions) VALUES ($1, false, false)', 
      array($username));
}

if (isset($_POST['action']))
{
   switch ($_POST['action'])
   {
      case 'change_replies':
      {
         nsc_execute($pg, 'UPDATE notify_user SET match_replies = $1 WHERE username = $2', 
            array(isset($_POST['replies']), $username));
         break;
      }
      
      case 'change_mentions':
      {
         nsc_execute($pg, 'UPDATE notify_user SET match_mentions = $1 WHERE username = $2', 
            array(isset($_POST['mentions']), $username));
         break;
      }
      
      case 'add_keyword':
      {
         $keyword = trim(strval($_POST['keyword']));
         if (empty($keyword))
            break;
         try
         {
            nsc_execute($pg, 'INSERT INTO notify_user_keyword (username, keyword) VALUES ($1, $2)',
               array($username, $keyword));
         }
         catch (Exception $ex) {}
         break;
      }
      
      case 'remove_keyword':
      {
         $keyword = strval($_POST['keyword']);
         if (empty($keyword))
            break;
         try
         {
            nsc_execute($pg, 'DELETE FROM notify_user_keyword WHERE username = $1 AND keyword = $2',
               array($username, $keyword));
         }
         catch (Exception $ex) {}
         break;
      }
      
      case 'detach_client':
      {
         $clientId = strval($_POST['clientId']);
         if (empty($clientId))
            break;
         try
         {
            nsc_execute($pg, 'DELETE FROM notify_client WHERE username = $1 AND id = $2', 
               array($username, $clientId));
         }
         catch (Exception $ex) {}
         break;
      }
   }

   header('Location: https://winchatty.com/v2/notifications/ui/configure');
   die();
}

$row = nsc_selectRow($pg, 'SELECT match_replies, match_mentions FROM notify_user WHERE username = $1', 
   array($username));
$notifyReplies = $row[0] == 't';
$notifyMentions = $row[1] == 't';

$keywords = nsc_selectArray($pg, 'SELECT keyword FROM notify_user_keyword WHERE username = $1', 
   array($username));

$rs = nsc_query($pg, 'SELECT id, name FROM notify_client WHERE username = $1', array($username));
$clients = array();
foreach ($rs as $row)
   $clients[] = array('id' => strval($row[0]), 'name' => strval($row[1]));
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=420, user-scalable=no">
      <title>WinChatty Notifier</title>
      <link rel="stylesheet" href="style.css">
      <script type="text/javascript">
         function away() {
            document.getElementById("overlay").style.display = "block";
         }
      </script>
   </head>
   <body>
      <div style="position: relative;">
      <div style="position: absolute; height: 100%; width: 100%; background: #404040; opacity: 0.5; display: none;"
         id="overlay"></div>
      <h1><?=strtoupper($_SESSION['username'])?></h1>
      <h2>Notifications</h2>
      <p>
         <form method="POST" action="configure">
            <input type="hidden" name="action" value="change_replies">
            <input id="replies" type="checkbox" name="replies" onClick="away(),this.form.submit()"
               <?= $notifyReplies ? 'checked' : '' ?>><label for="replies"> Replies to my posts</label>
         </form>
      </p>
      <p>
         <form method="POST" action="configure">
            <input type="hidden" name="action" value="change_mentions">      
            <input id="mentions" type="checkbox" name="mentions" onClick="away(),this.form.submit()"
               <?= $notifyMentions ? 'checked' : '' ?>><label for="mentions"> Posts mentioning my name</label>
         </form>
      </p>
      <h2>Custom Keywords</h2>
      <p>
         <? if (empty($keywords)) { ?>
         You have no custom keywords defined.
         <? } else { ?>
         <form method="POST" action="configure">
            <input type="hidden" name="action" value="remove_keyword">
            <select name="keyword">
               <? foreach (array_reverse($keywords) as $keyword) { ?>
               <option value="<?=$keyword?>"><?=$keyword?></option>
               <? } ?>
            </select>
            <input type="submit" value="Remove" class="button" onClick="away()">
         </form>
         <? } ?>
      </p>
      <p>
         <form method="POST" action="configure">
            <input type="hidden" name="action" value="add_keyword">
            <input type="text" class="text" name="keyword">
            <input type="submit" value="Add" class="button" onClick="away()">
         </form>
      </p>
      <h2>Attached Clients</h2>
      <p>
         <? if (empty($clients)) { ?>
         You have no clients attached.
         <? } else { ?>
         <form method="POST" action="configure">
            <input type="hidden" name="action" value="detach_client">
            <select name="clientId">
               <? foreach ($clients as $client) { ?>
               <option value="<?=$client['id']?>"><?=$client['name']?></option>
               <? } ?>
            </select>
            <input type="submit" value="Detach" class="button" onClick="away()">
         </form>
         <? } ?>
      </p>
      </div>
   </body>
</html>