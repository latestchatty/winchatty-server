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
         'id'            => false,
         'thread_id'     => false);

      $p->seek(1, '<div id="main">');

      $story['name'] = $p->clip(array('<h1>', '>'), '<');
      $story['date'] = nsc_v1_date(strtotime($p->clip(array('<span class="author">', 'By ', ', ', ' '), '</')));
      $body = $p->clip(array('>'), '<p><a href="#comments">');
      $story['body'] = str_replace('src="//', 'src="http://', $body);
      $story['preview'] = nsc_previewFromBody($story['body']);
      $story['comment_count'] = 0;

      $p->seek(1, '<div class="threads">');
      $story['thread_id'] = intval($p->clip(array('<div id="root_', '_'), '"'));

      $p->seek(1, '<input type="hidden" name="content_type_id" id="content_type_id" value="2" />');
      $story['id'] = intval($p->clip(array('<input type="hidden" value="', 'value', '"'), '"'));

      return $story;
   }
}

function ArticleParser()
{
   return new ArticleParser();
}
