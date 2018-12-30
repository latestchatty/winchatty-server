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

class ClassicAdapter
{
   public static function getThread($threadID)
   {
      if ($threadID == 0)
         throw new Exception('Thread ID cannot be 0.');

      #$thread = ThreadParser()->getThread($threadID, false);
      $pg = nsc_connectToDatabase();
      $thread = nsc_v1_getThreadTree($pg, $threadID);

      $participants = self::getParticipants($thread['replies']);
      
      # Make the tree hierarchical.
      $stack = array();
      
      # Add an empty "comments" array to each post.
      foreach ($thread['replies'] as $i => $reply)
      {
         $reply['comments'] = array();
         $thread['replies'][$i] = $reply;
      }

      # Make a lookup table.  Child ID => Parent ID
      $parent_of = array();
      array_push($stack, array());
      foreach ($thread['replies'] as $i => $reply)
      {
         $parent_of[$reply['id']] = $stack[$reply['depth']];
         $stack[$reply['depth'] + 1] = $reply['id'];
      }
      
      # Reverse the lookup table.  Parent ID => Child IDs
      $children_of = array();
      foreach ($parent_of as $child => $parent)
      {
         $parent = strval($parent);
         if (isset($children_of[$parent]))
            $children_of[$parent][] = $child;
         else
            $children_of[$parent] = array($child);
      }

      # Strip the 'depth' field.
      foreach ($thread['replies'] as $i => $reply)
      {
         unset($reply['depth']);
         $thread['replies'][$i] = $reply;
      }

      # Make a lookup table.  Post ID => Post content
      $replies = array();
      foreach ($thread['replies'] as $reply)
         $replies[$reply['id']] = $reply;
      
      # Walk the $children_of table to create the hierarchy.
      $root = $thread['replies'][0];
      self::fillChildren($root, $replies, $children_of);

      $root['participants'] = $participants;
      $root['reply_count'] = count($replies);
      $root['last_reply_id'] = 0;

      return array(
         'story_id'   => $thread['story_id'],
         'last_page'  => 1,
         'page'       => 1,
         'story_name' => $thread['story_name'],
         'comments'   => array($root));
   }
   
   private static function fillChildren(&$post, &$replies, &$children_of)
   {
      $tree = array();
      $id = strval($post['id']);
      
      if (!isset($children_of[$id]))
      {
         # Leaf post.
         return;
      }
      
      foreach ($children_of[$id] as $child_id)
      {
         $child = $replies[$child_id];
         self::fillChildren($child, $replies, $children_of);
         $tree[] = $child;
      }
      
      $post['comments'] = $tree;
   }
   
   public static function getStory($story, $page)
   {
      #$chatty = ChattyParser()->getStory($story, $page);
      $pg = nsc_connectToDatabase();
      $chatty = nsc_v1_getStory($pg, $page);
      
      $json = array(
         'comments'   => array(),
         'page'       => $chatty['current_page'],
         'last_page'  => $chatty['last_page'],
         'story_id'   => $chatty['story_id'],
         'story_name' => $chatty['story_name']);

      # Strip out the replies.
      foreach ($chatty['threads'] as $thread)
      {
         $json['comments'][] = array(
            'comments'      => array(),
            'reply_count'   => $thread['reply_count'],
            'body'          => $thread['body'],
            'date'          => $thread['date'],
            'participants'  => self::getParticipants($thread['replies']),
            'category'      => $thread['category'],
            'last_reply_id' => $thread['last_reply_id'],
            'author'        => (string)$thread['author'],
            'preview'       => strip_tags($thread['preview']),
            'id'            => $thread['id']);
      }
      
      return $json;
   }

   public static function getParticipants($replies)
   {
      $participants      = array();
      $json_participants = array();

      foreach ($replies as $reply)
      {
         $author = $reply['author'];
         if (isset($participants[$author]))
            $participants[$author]++;
         else
            $participants[$author] = 1;
      }
      
      foreach ($participants as $participant => $posts)
      {
         $json_participants[] = array(
            'username'   => (string)$participant,
            'post_count' => $posts);
      }
      
      return $json_participants;
   }
   
   public static function search($terms, $author, $parentAuthor, $page = 1)
   {
      $results = SearchParser()->search($terms, $author, $parentAuthor, '', $page);

      $json    = array(
         'terms'         => $terms,
         'author'        => $author,
         'parent_author' => $parentAuthor,
         'comments'      => array(),
         'last_page'     => 1);

      foreach ($results as $result)
      {
         $json['last_page'] = ceil($result['totalResults'] / 15);
         $json['comments'][] = array(
            'comments'      => array(),
            'last_reply_id' => null,
            'author'        => $result['author'],
            'date'          => $result['date'],
            'story_id'      => $result['story_id'],
            'category'      => null,
            'reply_count'   => null,
            'id'            => $result['id'],
            'story_name'    => $result['story_name'],
            'preview'       => $result['preview'],
            'body'          => null);
      }
      
      return $json;
   }
   
   public static function nusearch($terms, $author, $parentAuthor, $page = 1)
   {
      $resultsPerPage = 20;
      $pg = nsc_connectToDatabase();
      $posts = nsc_search($pg, $terms, $author, $parentAuthor, '', 
         ($page - 1) * $resultsPerPage, $resultsPerPage + 1, false);

      $json = array(
         'terms'         => $terms,
         'author'        => $author,
         'parent_author' => $parentAuthor,
         'comments'      => array(),
         'last_page'     => $page + (count($posts) > $resultsPerPage ? 1 : 0)
      );

      $posts = array_slice($posts, 0, $resultsPerPage);

      foreach ($posts as $result)
      {
         $json['comments'][] = array(
            'comments'      => array(),
            'last_reply_id' => null,
            'author'        => $result['author'],
            'date'          => nsc_v1_date(strtotime($result['date'])),
            'story_id'      => 0,
            'category'      => null,
            'reply_count'   => null,
            'id'            => $result['id'],
            'story_name'    => 'Chatty',
            'preview'       => nsc_previewFromBody($result['body']),
            'body'          => null);
      }
      
      return $json;
   }

   public static function getMessages($username, $password)
   {
      $o = MessageParser()->getMessages('inbox', $username, $password, 1, 50);
      
      $messages = array();
      
      foreach ($o['messages'] as $message)
      {
         $message['subject'] = $message['subject'];
         $message['from']    = $message['from'];
         $messages[]         = $message;
      }

      return array(
         'user'     => $username,
         'messages' => $messages);
   }
}
