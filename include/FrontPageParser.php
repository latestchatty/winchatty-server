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
      $p->seek(1, '<div class="content">');

      $retList = array();
      while ($p->peek(1, '<div class="article_copy') !== false)
      {
         $p->seek(1, '<div class="article_copy');
         $relativeUrl = $p->clip(array('<a href="', '"'), '"');
         $choppedRelativeUrl = str_replace('/article/', '', $relativeUrl);
         $id = substr($choppedRelativeUrl, 0, strpos($choppedRelativeUrl, '/'));
         $title = $p->clip(array('<h2', '>'), '<');
         if (strpos($title, 'Weekend Confirmed') !== false)
            continue;
         $time = strtotime($p->clip(array('<span class="author">', 'By ',', ', ' '), '</span>'));
         $summary = trim($p->clip(array('<p>', '>'), '</p>'));
         $postCount = 0;

         $commentDivPeek = $p->peek(1, '<div class="comment">');
         $nextStoryPeek = $p->peek(1, '<div class="article_copy');
         if ($commentDivPeek !== false && ($nextStoryPeek === false || $commentDivPeek < $nextStoryPeek))
         {
            $commentDiv = $p->clip(array('<div class="comment">', '>'), '</div>');
            $p->seek(1, '<span class="author">');
            $postCount = $p->clip(array('<a href="', '>', 'see all ', 'all', ' '), ' comments</a>');
         }

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
