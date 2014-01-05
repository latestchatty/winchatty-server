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

class FrontPageParser extends Parser
{
   public function getStories($page = 1)
   {
      $html = $this->download("http://www.shacknews.com/?page=$page", true);
      return $this->parseStories($html);
   }
   
   private function parseStories($html)
   {
      $articleParser = ArticleParser();
      $stories       = array();
   
      $p = $this;
      $p->init($html);

      $p->seek(1, '<div id="main">');
      $p->seek(1, '<div id="feature-break">');

      $retList = array();
      $i = 0;
      while ($p->peek(1, ' class="story">') !== false)
      {
         $p->seek(1, ' class="story">');
         $relativeUrl = $p->clip(array('<h', '><a href="', '"'), '"');
         $choppedRelativeUrl = str_replace('/article/', '', $relativeUrl);
         $id = substr($choppedRelativeUrl, 0, strpos($choppedRelativeUrl, '/'));
         $title = $p->clip(array('>'), '<');
         if (strpos($title, 'Weekend Confirmed') !== false)
            continue;
         $time = strtotime($p->clip(array('<span class="byline">', 'by ', ',', ' '), '</span>'));
         $summary = trim($p->clip(array('<div class="summary">', '>'), '</div>'));
         $commentDiv = $p->clip(array('<div class="small-bubble', '>'), '</div>');
         $postCount = 0;
         if (strpos($commentDiv, 'Comment on this story') === false)
         {
            $p->seek(1, '<span class="user-credit">');
            $postCount = $p->clip(array('<a href="', '>', 'See', 'all', ' '), ' comments</a>');
         }
         $i++;
         $retList[] = array(
            'body' => strval($summary),
            'comment_count' => intval($postCount),
            'date' => nsc_v1_date($time),
            'id' => intval($id),
            'name' => strval($title),
            'preview' => trim(nsc_previewFromBody($summary)),
            'url' => 'http://www.shacknews.com' . $relativeUrl,
            'thread_id' => ''
         );
      }

      return $retList;
   }
}

function FrontPageParser()
{
   return new FrontPageParser();
}