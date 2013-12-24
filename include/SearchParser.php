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

class SearchParser extends Parser
{
   public function search($terms, $author, $parentAuthor, $category, $page)
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
         . '&result_sort=postdate_desc';
      
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