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

require_once 'Global.php';
$pg = nsc_initJsonPost();
$username = nsc_postArg('username', 'STR,50');
$postId = nsc_postArg('postId', 'INT');
$type = nsc_postarg('type', 'MPT');
$shackerId = nsc_getShackerId($pg, $username);

nsc_execute($pg, 'BEGIN', array());

# Clear any existing mark.
nsc_execute($pg, 
   'DELETE FROM shacker_marked_post WHERE shacker_id = $1 AND post_id = $2', 
   array($shackerId, $postId));

# Make sure the post exists.
$existingPostId = nsc_selectValueOrFalse($pg, 'SELECT id FROM post WHERE id = $1', array($postId));
if ($existingPostId === false)
   nsc_die('ERR_POST_DOES_NOT_EXIST', 'The specified post does not exist.');

# Add a new mark, if necessary.
if ($type == 'pinned' || $type == 'collapsed')
{
   nsc_execute($pg,
      'INSERT INTO shacker_marked_post (shacker_id, post_id, mark_type) VALUES ($1, $2, $3)',
      array($shackerId, $postId, nsc_markTypeStringToInt($type)));
}

nsc_execute($pg, 'COMMIT', array());

echo json_encode(array('result' => 'success'));
