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

require_once '../../include/Global.php';
$pg = nsc_initJsonPost();
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');
$triggerOnReply = nsc_postArg('triggerOnReply', 'BIT');
$triggerOnMention = nsc_postArg('triggerOnMention', 'BIT');
$triggerKeywords = nsc_postArg('triggerKeywords', 'STR*', array());

nsc_checkLogin($username, $password);
$username = strtolower($username);

nsc_execute($pg, 'BEGIN');
nsc_execute($pg, 'UPDATE notify_user SET match_replies = $1, match_mentions = $2 WHERE username = $3',
   array($triggerOnReply, $triggerOnMention, $username));
nsc_execute($pg, 'DELETE FROM notify_user_keyword WHERE username = $1', array($username));
foreach ($triggerKeywords as $keyword)
{
   nsc_execute($pg, 'INSERT INTO notify_user_keyword (username, keyword) VALUES ($1, $2)',
      array($username, $keyword));
}
nsc_execute($pg, 'COMMIT');

echo json_encode(array('result' => 'success'));
