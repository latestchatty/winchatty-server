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

function entities( $string ) {
    $stringBuilder = "";
    $offset = 0;

    if ( empty( $string ) ) {
        return "";
    }

    while ( $offset >= 0 ) {
        $decValue = ordutf8( $string, $offset );
        $char = unichr($decValue);

        $htmlEntited = htmlentities( $char );
        if( $char != $htmlEntited ){
            $stringBuilder .= $htmlEntited;
        } elseif( $decValue >= 128 ){
            $stringBuilder .= "&#" . $decValue . ";";
        } else {
            $stringBuilder .= $char;
        }
    }

    return $stringBuilder;
}

// source - http://php.net/manual/en/function.ord.php#109812
function ordutf8($string, &$offset) {
    $code = ord(substr($string, $offset,1));
    if ($code >= 128) {        //otherwise 0xxxxxxx
        if ($code < 224) $bytesnumber = 2;                //110xxxxx
        else if ($code < 240) $bytesnumber = 3;        //1110xxxx
        else if ($code < 248) $bytesnumber = 4;    //11110xxx
        $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
        for ($i = 2; $i <= $bytesnumber; $i++) {
            $offset ++;
            $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
            $codetemp = $codetemp*64 + $code2;
        }
        $code = $codetemp;
    }
    $offset += 1;
    if ($offset >= strlen($string)) $offset = -1;
    return $code;
}

// source - http://php.net/manual/en/function.chr.php#88611
function unichr($u) {
    return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
}

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
         $url = "https://www.shacknews.com/chatty?page=$page";
      }
      else
      {
         throw new Exception("Invalid storyID");
      }
   
      return $this->parseStory($this->download($url));
   }

   public function soloPost($username, $password, $parentID, $body)
   {
      $pg = nsc_connectToDatabase();
      nsc_checkLogin($username, $password);
      nsc_execute($pg, 'INSERT INTO new_post_queue (username, parent_id, body) VALUES ($1, $2, $3)', array(
         strval($username), intval($parentID), strval($body)));
      nsc_disconnectFromDatabase($pg);
   }
   
   public function post($username, $password, $parentID, $storyID, $body, 
      $contentTypeID = -1, $contentID = -1)
   {
      if (V2_INDEXER_SCRIPT == 'solo_indexer.php')
      {
         $this->soloPost($username, $password, $parentID, $body);
         return 'fixup_postbox_parent_for_remove('; # callers check for this string to detect success
      }

      $postURL = 'https://www.shacknews.com/post_chatty.x';

      if ($parentID != 0 && ($contentTypeID == -1 || $contentID == -1))
      {
         $contentURL = "https://www.shacknews.com/chatty?id=$parentID";
         $html = $this->download($contentURL, true);
         $this->init($html);
         $this->seek(1, '<input type="hidden" name="content_type_id"');
         $contentTypeID = intval($this->clip(array('value="', '"'), '"'));
         $contentID = intval($this->clip(array('value="', '"'), '"'));
      }
      
      if ($contentTypeID == -1)
         $contentTypeID = 17;
      if ($contentID == -1)
         $contentID = 17;
      
      # Hack to fix a bizarre issue where a parsing error is returned when the
      # post starts with an '@' symbol.
      if (strlen($body) > 1 && $body[0] == '@')
         $body = ' ' . $body; 

      $postArgs = array(
         'parent_id' => $parentID == 0 ? '' : $parentID,
         'content_type_id' => strval($contentTypeID),
         'content_id' => strval($contentID), 
         'page' => '', 
         'parent_url' => '/chatty',
         'body' => entities($body));

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
      # <div class="content">
      # <div class="content-inner">
      # <div class="main-col">
      # <div class="article">
      # <h1>Working for the weekend<h1> 
      # <span class="author">by Xav de Matos, Jul 09, 2014 11:49pm PDT</span> 
      # <div class="article-body">...</div> 
      # </div>
      $this->seek(1, '<div class="article">');
      $o['story_id'] = 0;
      if ($this->peek(1, '<h1>') === false) {
         $o['story_name'] = 'Latest Chatty';
         $o['story_author'] = 'Shacknews';
         $o['story_date'] = '';
         $o['story_text'] = '';
      } else {
         $o['story_name'] = $this->clip(
            array('<h1>', '>'), 
            '</h1>');
         $o['story_author'] = $this->clip(
            array('<span class="author">', '>By ', ' '),
            ',');
         $o['story_date'] = $this->clip(
            array(', ', ' '),
            '</span>');
         $o['story_text'] = trim($this->clip(
            array('<div class="article-body">', '>'),
            "\t</div>"));
      }
      
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
      if ($this->peek(1, '<div class="pagenavigation">') === false)
      {
         $o['current_page'] = 1;
      }
      else
      {
         $this->seek(1, array('<div class="pagenavigation">', '>'));
      
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
      $html = $this->userDownload('https://www.shacknews.com/moderators', $username, $password);
      return strpos($html, '<div id="mod_board_head">') !== false;
   }

   public function setPostCategory($username, $password, $threadId, $postId, $category)
   {
      $threadId = intval($threadId);
      $postId = intval($postId);

      if ($threadId == 0)
         throw new Exception('Invalid thread ID.');

      # Shacknews uses different number assignments than we do.
      $categoryInt = false;
      switch ($category)
      {
         case 'ontopic':     $categoryInt = 5; break;
         case 'nws':         $categoryInt = 2; break;
         case 'stupid':      $categoryInt = 3; break;
         case 'political':   $categoryInt = 9; break;
         case 'tangent':     $categoryInt = 4; break;
         case 'informative': $categoryInt = 1; break;
         case 'nuked':       $categoryInt = 8; break;
         default: throw new Exception('Unexpected category string.');
      }

      $url = "https://www.shacknews.com/mod_chatty.x?root=$threadId&post_id=$postId&mod_type_id=$categoryInt";

      $html = $this->userDownload($url, $username, $password);
      if (strpos($html, 'Invalid moderation flags') !== false)
         throw new Exception('Possible bug in the API. Server does not understand the moderation flag.');
      if (strpos($html, 'navigate_page_no_history( window, "/frame_chatty.x?root=') === false)
         throw new Exception('Failed to set the post category.  User likely does not have moderator privileges.');
   }
}

function ChattyParser()
{
   return new ChattyParser();
}
