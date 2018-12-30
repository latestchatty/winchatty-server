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
