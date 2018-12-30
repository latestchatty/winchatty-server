<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

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
