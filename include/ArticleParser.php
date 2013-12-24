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
      $this->init($this->download("http://www.shacknews.com/onearticle.x/$storyID"));
      return $this->parseArticle($this);
   }

   public function parseArticle(&$p)
   {
      $story = array(
         'preview'       => false,
         'name'          => false,
         'body'          => false,
         'date'          => false,
         'comment_count' => false,
         'id'            => false);
   
      $p->seek(1, '<div class="story">');
      
      $story['id'] = $p->clip(
         array('<a href="http://www.shacknews.com/onearticle.x/', 'onearticle.x/', '/'),
         '">');

      $story['name'] = $p->clip(
         '>',
         '</a>');

      $story['date'] = $p->clip(
         array('<span class="date">', '>'),
         '</span>');
         
      $story['body'] = trim($p->clip(
         array('<div class="body">', '>'),
         '<div class="comments">'));
      
      # Trim the extra </div> from the end of the body.
      $story['body'] = substr($story['body'], 0, -6);
      
      $story['preview'] = substr(strip_tags($story['body']), 0, 500);
      
      $story['comment_count'] = $p->clip(
         array('<span class="commentcount">', '>'),
         ' ');
      
      if ($story['comment_count'] == 'No')
         $story['comment_count'] = '0';
      else
         $story['comment_count'] = intval($story['comment_count']);
      
      return $story;
   }
}

function ArticleParser()
{
   return new ArticleParser();
}