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

      $p->seek(1, '<div class="posts">');

      $retList = array();
      while ($p->peek(1, '<article class="post') !== false)
      {
         $p->seek(1, '<article class="post');
         
         /*
         <article class="post ">
         <div class="meta clear-fix">
         <div class="author">By Daniel Perez&nbsp;&nbsp;&nbsp;//&nbsp;&nbsp;&nbsp;November 8, 2014 7:55 AM</div>
         </div>
         <div class="post-content clear-fix">
         <a class="post-pic" href="/article/87071/ubisoft-fall-pc-lineup-returns-to-steam" 
            style="background: url('https://s3-us-west-1.amazonaws.com/.../54437_400x225.jpg') no-repeat;">
         <div class="play-button">
         <div class="comment-count">22</div>
         </div>
         </a>
         <div class="content">
         <h2><a href="/article/87071/...-to-steam">Ubisoft fall PC lineup returns to Steam</a></h2>
         <div class="blurb"><p>It appears Ubigate is now over as Ubisoft's fall PC lineup of games has returned to 
         Steam. Let's just hope they stick around this time around.</p></div>
         </div>
         </div>
         </article>
         */
         
         $endPos = $p->peek(1, '</article>');
         
         $bylineAndDate = $p->clip(array('<div class="author">', '>'), '</div>');
         $bylineAndDateParts = explode('&nbsp;&nbsp;&nbsp;//&nbsp;&nbsp;&nbsp;', $bylineAndDate);
         $byline = $bylineAndDateParts[0];
         $originalDate = $bylineAndDateParts[1];
         $time = strtotime($originalDate . ' PST');
         
         $p->seek(1, '<div class="content">');
         
         $relativeUrl = $p->clip(array('<h2><a href="', '"'), '"');
         $title = strval($p->clip(array('>'), '</a></h2>'));
         $summary = html_entity_decode(strip_tags(trim(strval(
            $p->clip(array('<div class="blurb">', '>'), '</div>')))));
         
         $relativeUrlParts = explode('/', str_replace('/article/', '', $relativeUrl));
         $id = intval($relativeUrlParts[0]);
         
         $commentCount = 0;
         $commentCountPos = $p->peek(1, '<div class="comment-count">');
         if ($commentCountPos !== false && $commentCountPos < $endPos)
            $commentCount = intval($p->clip(array('<div class="comment-count">', '>'), '</div>'));
                     
         $retList[] = array(
            'body' => $summary,
            'comment_count' => $commentCount,
            'date' => nsc_v1_date($time),
            'id' => $id,
            'name' => $title,
            'preview' => $summary,
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
