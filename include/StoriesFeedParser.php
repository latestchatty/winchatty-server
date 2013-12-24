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

class StoriesFeedParser
{
   function getStories()
   {
      # The chatty no longer has a concept of MD/ER/etc.  We will just show the 
      # one chatty "story".
      $feed = array();
      
      # Add the latest chatty to the top of the list.
      $feed[] = array(
         'title'    => 'Latest Chatty',
         'story_id' => 0);
   
      return $feed;
   }
}

function StoriesFeedParser()
{
   return new StoriesFeedParser();
}