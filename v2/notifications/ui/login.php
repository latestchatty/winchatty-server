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

$clientId = '';
if (isset($_SESSION['clientId']))
   $clientId = $_SESSION['clientId'];
if (isset($_REQUEST['clientId']))
{
   $_SESSION['clientId'] = $_REQUEST['clientId'];
   header('Location: https://winchatty.com/v2/notifications/ui/login');
   die();
}

$error = false;

if (isset($_POST['action']))
{
   $username = strtolower($_POST['username']);
   $password = $_POST['password'];

   try
   {
      ChattyParser()->isModerator($username, $password);
   }
   catch (Exception $ex)
   {
      $error = 'Invalid username or password.';
   }
   
   if ($error === false)
   {
      if (!empty($clientId))
      {
         $clientUser = nsc_selectValueOrFalse($pg, 'SELECT username FROM notify_client WHERE id = $1', array($clientId));
         if ($clientUser === false)
            die('Invalid clent ID.');
         if (is_null($isClientUser) || empty($isClientUser))
            nsc_execute($pg, 'UPDATE notify_client SET username = $1 WHERE id = $2', array($username, $clientId));
      }
      
      $_SESSION['username'] = strtolower($username);
      header('Location: https://winchatty.com/v2/notifications/ui/configure');
      die();
   }
}
?>
<!DOCTYPE html>
<html>
   <meta charset="utf-8">
   <meta name="viewport" content="width=420, user-scalable=no">
   <title>WinChatty Notifications</title>
   <link rel="stylesheet" href="style.css">
   <body>
      <h1>WinChatty Notifications</h1>
      <p>
         Please log in using your Shacknews credentials.
      </p>
      <form method="POST" action="login">
         <input type="hidden" name="action" value="login">
         <p>
            Username:<br>
            <input type="text" name="username" class="text">
         </p>
         <p>
            Password:<br>
            <input type="password" name="password" class="text">
         </p>
         <p>
            <input type="submit" value="Log in" class="button">
         </p>
         <? if ($error !== false) { ?>
         <p style="background: red; padding: 10px;">
            <?=$error?>
         </p>
         <? } ?>
      </form>
   </body>
</html>