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
      if ($page != 1)
      {
         return array();
      }

      $html = $this->download("https://www.shacknews.com/feed/rss", true);
      return $this->parseStories($html);
   }
   
   private function parseStories($html)
   {
      $articleParser = ArticleParser();
      $stories       = array();
   
      $p = $this;
      $p->init($html);

      $p->seek(1, '<channel>');

      $retList = array();
      while ($p->peek(1, '<item>') !== false)
      {
         $p->seek(1, '<item>');
         $endPos = $p->peek(1, '</item>');
         $title = $p->clip(array('<title><![CDATA[', 'CDATA[', '['), ']]></title>');
         $link = $p->clip(array('<link>', '>'), '</link>');
         $pubDateStr = $p->clip(array('<pubDate>', '>'), '</pubDate>');
         $time = strtotime($pubDateStr);
         $description = $p->clip(array('<description><![CDATA[', 'CDATA[', '['), ']]></description>');
         $linkParts = explode('/', $link);
         $id = intval($linkParts[count($linkParts) - 2]);

         $retList[] = array(
            'body' => $description,
            'date' => nsc_v1_date($time),
            'id' => $id,
            'name' => $title,
            'preview' => $description,
            'url' => $link
         );
      }

      return $retList;
   }
}

function FrontPageParser()
{
   return new FrontPageParser();
}
