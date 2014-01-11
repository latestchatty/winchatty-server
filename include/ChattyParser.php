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

class ChattyParser extends Parser
{
   public function getStory($storyID, $page)
   {
      $url = false;
   
      if ($storyID < 0)
      {
         # Hack time.
         $threadID = $storyID * (-1);
         
         $thread = ThreadParser()->getThreadTree($threadID);
         
         return array(
         'threads'      => array($thread),
         'story_id'     => $storyID,
         'story_name'   => 'Search Result',
         'story_text'   => '',
         'story_author' => '',
         'story_date'   => '',
         'current_page' => 1,
         'last_page'    => 1);
      }
      else if ($storyID == 0)
      {
         $url = "http://www.shacknews.com/chatty?page=$page";
      }
      else
      {
         throw new Exception("Invalid storyID");
      }
   
      return $this->parseStory($this->download($url));
   }
   
   public function post($username, $password, $parentID, $storyID, $body)
   {
      $postURL = 'http://www.shacknews.com/post_chatty.x';
      
      if ($parentID == 0)
         $parentID = '';

      $thread = ThreadParser()->getThreadTree($parentID);
      $rootID = intval($thread['id']);
      $rootAuthor = strval($thread['author']);
      if ($rootAuthor == 'Shacknews')
         $contentID = 2;
      else if ($rootID >= 25350136)
         $contentID = 17;
      else
         throw new Exception('Cannot post in ancient (pre-NuShack) threads.');
      
      $postArgs = array(
         'parent_id' => $parentID,
         'content_type_id' => strval($contentID),
         'content_id' => strval($contentID), 
         'page' => '', 
         'parent_url' => '/chatty',
         'body' => $body);

      $retVal = $this->userDownload($postURL, $username, $password, $postArgs);
      
      # We'll just chill for a few seconds to let Shacknews create and cache
      # this post, because the new site seems to require it.  This ensures
      # that the client will see the new post when it refreshes.
      sleep(1);
      
      return $retVal;
   }
   
   public function parseStory($html)
   {
      $this->init($html);

      ThreadParser()->checkContentId($html);

      $o = array(
         'threads'      => array(),
         'story_id'     => false,
         'story_name'   => false,
         'story_text'   => false,
         'story_author' => false,
         'story_date'   => false,
         'current_page' => false,
         'last_page'    => false);
      
      #
      # Story text
      #
      # <div id="main"> 
      # <div class="article-title">Working for the weekend</div> 
      # <div class="article-author">by Xav de Matos,</div> 
      # <div class="article-post-date">Feb 25, 2011 5:00pm PST</div> 
      # <div class="article-body">...</div> 
      # </div>
      $o['story_id'] = 0;
      $o['story_name'] = $this->clip(
         array('<div class="article-title">', '>'), 
         '</div>');
      $o['story_author'] = $this->clip( 
         array('<div class="article-author">by ', '>', ' '),
         ',</div>');   
      $o['story_date'] = $this->clip(
         array('<div class="article-post-date">', '>'),
         '</div>');
      $o['story_text'] = trim($this->clip(
         array('<div class="article-body">', '>'),
         '    </div>'));
      
      #
      # Page navigation (current page)
      #
      #										<div class="pagenavigation"> 
      #<a rel="nofollow" href="/chatty?page=3" class="nextprev">&laquo; Previous</a> 
      #<a rel="nofollow" href="/chatty?page=1">1</a> 
      #<a rel="nofollow" href="/chatty?page=2">2</a> 
      #<span>...</span> 
      #<a rel="nofollow" href="/chatty?page=2">2</a> 
      #<a rel="nofollow" href="/chatty?page=3">3</a> 
      #<a rel="nofollow" class="selected_page" href="/chatty?page=4">4</a> 
      #<a rel="nofollow" href="/chatty?page=5">5</a> 
      #<span>...</span> 
      #<a rel="nofollow" href="/chatty?page=8">8</a> 
      #<a rel="nofollow" href="/chatty?page=9">9</a> 
      #<a rel="nofollow" href="/chatty?page=5" class="nextprev">Next &raquo;</a></div> <!-- class="pagenavigation" --> 
      #
      $this->seek(1, array('<div class="pagenavigation">', '>'));

      # May not be present if there's only 1 page.
      if ($this->peek(1, '<a rel="nofollow" class="selected_page"') === false)
      {
         $o['current_page'] = 1;
      }
      else
      {
         $o['current_page'] = $this->clip(
            array('<a rel="nofollow" class="selected_page"', '>'),
            '</a>');
      }

      #
      # Number of threads (last_page)
      #
		# <div id="chatty_settings" class="">	
		# <a href="/chatty">268 Threads*</a><span class="pipe"> |</span> 
		# <a href="/chatty">4,438 Comments</a> 
      #
      $this->seek(1, array('<div id="chatty_settings" class="">', '>'));
      
      $numThreads = $this->clip(
         array('<a href="/chatty">', '>'),
         ' Threads');
      $o['last_page'] = max(ceil($numThreads / 40), 1);

      #
      # Threads
      #
      while ($this->peek(1, '<div class="fullpost') !== false)
      {
         $thread = ThreadParser()->parseThreadTree($this);
         $o['threads'][] = $thread;
         
         if (count($o['threads']) > 50)
            throw new Exception('Too many threads.  Something is wrong.' . print_r($o, true));
      }   
      
      return $o;
   }
   
   public function locatePost($post_id, $unused)
   {
      $post_id = intval($post_id);
   
      # Locate the thread to find the root thread ID.
      $thread = ThreadParser()->getThreadTree($post_id);
 
      return array(
         'story' => (-1) * intval($thread['id']),
         'page' => 1,
         'thread' => $thread['id']);
   }

   public function isModerator($username, $password)
   {
      $html = $this->userDownload('http://www.shacknews.com/moderators', $username, $password);
      return strpos($html, '<div id="mod_board_head">') !== false;
   }
}

function ChattyParser()
{
   return new ChattyParser();
}
