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
      $this->init($this->download("https://www.shacknews.com/article/$storyID"));
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
         'thread_id'     => 0);

      $p->seek(1, '<div class="article-lead-middle">');
      $story['name'] = html_entity_decode($p->clip(array('<h1 class="article-title">', '>'), '</h1>'));
      $story['preview'] = html_entity_decode($p->clip(array('<description>', '<p>', '>'), '</p>'));
      $p->seek(1, '<div class="article-lead-bottom">');
      //$author = html_entity_decode($p->clip(array('<div class="attribution">', '<address', '<a href="https://www.shacknews.com/author', '>'), '</a>'));
      $story['date'] = nsc_v1_date(strtotime($p->clip(array('<time datetime="', '"'), '">')));
      $story['body'] = '<p>' . $p->clip(array('<p>', '>'), '<div class="author-short-bio');
   
      return $story;
   }
}

function ArticleParser()
{
   return new ArticleParser();
}
