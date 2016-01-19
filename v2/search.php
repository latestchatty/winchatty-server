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
$pg = nsc_initJsonGet();
$terms = nsc_getArg('terms', 'STR?', '');
$author = nsc_getArg('author', 'STR?', '');
$parentAuthor = nsc_getArg('parentAuthor', 'STR?', '');
$category = nsc_getArg('category', 'MOD?', '');
$offset = nsc_getArg('offset', 'INT?', 0);
$limit = nsc_getArg('limit', 'INT?,500', 35);
$oldestFirst = nsc_getArg('oldestFirst', 'BIT?', false);

$posts = nsc_search($pg, $terms, $author, $parentAuthor, $category, $offset, $limit, $oldestFirst);
echo json_encode(array('posts' => $posts));
