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

class ArticleParser extends Parser
{
   public function getArticle($storyID)
   {
      $storyID = intval($storyID);
      $this->init($this->download("http://www.shacknews.com/article/$storyID"));
      return $this->parseArticle($this, $storyID);
   }

   public function parseArticle(&$p, $storyID)
   {
      $story = array(
         'preview'       => false,
         'name'          => false,
         'body'          => false,
         'date'          => false,
         'comment_count' => 0, // comment count no longer shown on this page
         'id'            => intval($storyID),
         'thread_id'     => false);

      $p->seek(1, '<div id="main-content">');
      $story['name'] = trim(html_entity_decode(strval(
         $p->clip(array('<h1 class="title">', '>'), '</h1>'))));
      $p->seek(1, '<div class="date');
      $day = intval($p->clip(array('<div class="day">', '>'), '</div>'));
      $month = trim(strval($p->clip(array('<li>', '>'), '</li>')));
      $year = intval($p->clip(array('<li>', '>'), '</li>'));
      $time = trim(strval($p->clip(array('<li class="time">', '>'), '</li>')));
      $story['date'] = nsc_v1_date(strtotime("$month $day $year $time PST"));
      $story['thread_id'] = intval($p->clip(array('<a class="chatty" href="/chatty?id=', 'id=', '='), '"'));
      $story['preview'] = trim(html_entity_decode(strval(
         $p->clip(array('<p class="blurb">', '>'), '</p>'))));
      $story['body'] = trim(html_entity_decode(strval(
         $p->clip(array('<article class="post">', '>'), '</article>'))));
   
      return $story;
   }
}

function ArticleParser()
{
   return new ArticleParser();
}
