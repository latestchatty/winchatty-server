<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

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
            die('Invalid client ID.');
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