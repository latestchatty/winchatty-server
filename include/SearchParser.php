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

class SearchParser extends Parser
{
   public function search($terms, $author, $parentAuthor, $category, $page, $oldestFirst = false)
   {
      if (empty($category))
         $category = 'all';
   
      $url = 'http://www.shacknews.com/search'
         . '?chatty=1'
         . '&type=4'
         . '&chatty_term=' . urlencode($terms)
         . '&chatty_user=' . urlencode($author)
         . '&chatty_author=' . urlencode($parentAuthor) 
         . '&chatty_filter=' . urlencode($category)
         . '&page=' . urlencode($page)
         . ($oldestFirst ? '&result_sort=postdate_asc' : '&result_sort=postdate_desc');
      
      $this->init($this->download($url));
      
      $results = array();
      
      # <h2 class="search-num-found">149 chatty comments found for "Search Shacknews..."</h2>
      $totalResults = str_replace(',', '', $this->clip(
         array('<h2 class="search-num-found"', '>'),
         ' '));
      
      while ($this->peek(1, '<li class="result') !== false)
      {
         $o = array(
            'id' => false,
            'preview' => false,
            'author' => false,
            'date' => false,
            'totalResults' => $totalResults, 
               # Hack.  This should go into a top-level object returned, but some idiot
               # wrote this function to return an array of objects rather than an object
               # that contains an array.  No way am I releasing a new WinChatty to accept
               # a different return structure, so instead I'll pack the total results
               # into every result as a harmless additional attribute.  Whatever.
            
            # Not provided by the Shacknews search:
            'parentAuthor' => '',
            'category' => 'ontopic',
            'story_id' => 0,
            'story_name' => '',
            'thread_id' => 0);
         
         if ($this->peek(1, '<span class="chatty-author">') === false)
            break;

         # <span class="chatty-author"><a class="more" href="/user/pigvomit/posts">pigvomit:</a></span>
         $o['author'] = $this->clip(
            array('<span class="chatty-author">', '<a class="more"', '>'),
            ':</a></span>');
         
         # <span class="postdate">Posted Sep 23, 2011 8:54am PDT</span>
         $o['date'] = $this->clip(
            array('<span class="postdate"', '>', ' '),
            '</span>');
         
         # <a href="/chatty/26756013">Blah blah blah</a>
         $o['id'] = $this->clip(
            array('<a href="/chatty', 'chatty/', '/'),
            '"');
         $o['preview'] = $this->clip(
            '>',
            '</a>');
            
/*         if ($o['author'] == 'brickmatt')
         {
            $o['preview'] = '*** This brickmatt post has been hidden under the terms of the Brickmatt Opt-Out Accord of 2012. ***';
         }
         else if ($o['author'] == 'g0nk')
         {
            $o['preview'] = '*** This g0nk post has been hidden under the terms of the Brickmatt Opt-Out Accord of 2012. ***';
         }*/
            
         
         $results[] = $o;
      }
      
      return $results;
   }
}

function SearchParser()
{
   return new SearchParser();
}