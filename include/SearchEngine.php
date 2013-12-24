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

# Format of the .story file:
#  {
#     last_reply_id: <Number>,
#     story_name: <Number>,
#     story_id: <Number>,
#     comment_count: <Number>,
#     threads:
#     [
#        '<Thread ID>' => <Standard thread object, with body fields filled in>,
#        ...
#     ]
#  }
class SearchEngine
{
   private function stripNewlines($str)
   {
      while (strpos($str, "\n") !== false)
         $str = str_replace("\n", " ", $str);
      while (strpos($str, "\r") !== false)
         $str = str_replace("\r", " ", $str);
      return $str;
   }
   
   public function replaceAmpersands($str)
   {
      while (strpos($str, "&") !== false)
         $str = str_replace('&', '|amp;', $str);
      return $str;
   }
   
   public function memorySearch($terms = '', $author = '', $parentAuthor = '', $category = '', $offset = 0, $max = 50)
   {
      $terms = $this->replaceAmpersands($terms);
      $author = $this->replaceAmpersands($author);
      $parentAuthor = $this->replaceAmpersands($parentAuthor);
      $category = $this->replaceAmpersands($category);
      $offset = intval($offset);
      $max = intval($max);
      $port = chattysearchd_port;
      $url = "http://localhost:$port/search?$terms&$author&$parentAuthor&$category&$offset&$max&";
      $rawResults = explode("---\n", file_get_contents($url));
      $results = array();
      foreach ($rawResults as $i => $rawResult)
      {
         if ($i == 0)
            continue;
            
         $fields = explode("\n", $rawResult);
         if (count($fields) < 8)
            continue;
         $results[] = array(
            'id' => $fields[0],
            'preview' => $fields[1],
            'author' => $fields[2],
            'parentAuthor' => $fields[3],
            'date' => $fields[4],
            'category' => $fields[5],
            'story_id' => $fields[6],
            'story_name' => $fields[7],
            'thread_id' => 0);
      }
      return $results;
   }
   
   public function exportDatabase()
   {
      # Export this into the raw text format that searchd can understand.
      $filename = tempnam(null, 'WinChattySearch-');
      $fp = fopen($filename, "w");
      
      $stories = $this->getStories();
   
      # Flatten the threads in each story into just a list of posts.
      foreach ($stories as $story)
      {
         $story = $this->getStory($story);
         
         foreach ($story['threads'] as $threadID => $thread)
         {
            foreach ($thread['replies'] as $i => $reply)
            {
               $thisParentAuthor = '';
               $thisDepth = intval($reply['depth']);
               
               # Locate the parent post.
               if ($thisDepth > 0)
               {
                  $j = $i - 1;
                  for (; $j >= 0 && $thread['replies'][$j]['depth'] >= $thisDepth; $j--)
                  {
                     # Nothing.
                  }
                  
                  $thisParentAuthor = $thread['replies'][$j]['author'];
               }
               
               $author = $this->stripNewlines($reply['author']);
               $id = $this->stripNewlines($reply['id']);
               $body = $this->stripNewlines($reply['body']);
               $category = $this->stripNewlines($reply['category']);
               $preview = $this->stripNewlines($reply['preview']);
               $date = $this->stripNewlines($reply['date']);
               $storyID = $this->stripNewlines($story['story_id']);
               $storyName = $this->stripNewlines($story['story_name']);
            
               fprintf($fp, "---\n"."%u\n"."%s\n"."%u\n"."%s\n"."%s\n"."%s\n"."%s\n"."%s\n"."%s\n",
                  $storyID, $storyName, $id, $author, $thisParentAuthor, $body, $category, $preview, $date);
            }
         }
      }
      
      fclose($fp);

      $targetFile = search_data_directory . 'index.dat';
      rename($filename, $targetFile); # I hope this is atomic but I'm not really sure.
      chmod($targetFile, 0666);
   }

   public function refreshDatabase()
   {
      ob_start();
      $error = '';
   
      # Timestamp the beginning of the reindexing.
      touch(data_directory . 'ReindexBegin.zzz');
      chmod(data_directory . 'ReindexBegin.zzz', 0666);

      try
      {
         $this->refreshDatabaseInternal();
         
         # Export the database so the chattysearchd can read it.
         $this->exportDatabase();
      }
      catch (Exception $e)
      {
         $error = $e->getMessage();
      }
      
      $log = ob_get_contents();
      ob_end_clean();
      #$log = "OK";
      
      if (!empty($error))
         $log .= "\n\nError: $error";

      # Trigger a reindex.
      if (use_chattysearchd)
      {
         $port = chattysearchd_port;
         file_get_contents("http://localhost:$port/update");
      }

      # Timestamp the finish of the reindexing.
      touch(data_directory . 'ReindexFinish.zzz');
      chmod(data_directory . 'ReindexFinish.zzz', 0666);
      file_put_contents(data_directory . 'ReindexFinish.zzz', $log);
   }

   private function refreshDatabaseInternal()
   {
      $chattyParser    = ChattyParser();
      $threadParser    = ThreadParser();
      $frontPageParser = FrontPageParser();
      $stories         = array();
      
      for ($i = 1; $i <= search_retention; $i++)
         $stories = array_merge($stories, $frontPageParser->getStories($i));

      printf("Refresh started at " . date('r') . "\n");
   
      # Nuke stories that are no longer on the front page.
      foreach ($this->getStories() as $storyID)
      {
         $found = false;
         
         foreach ($stories as $story)
         {
            if (intval($story['id']) == intval($storyID))
            {
               $found = true;
               break;
            }
         }
         
         if ($found == false)
         {
            printf("Deleted $storyID\n");
            $this->nukeStory($storyID);
         }
      }
   
      printf("Done deleting old stories\n");
   
      # Update the chatties.  Download as little as possible.
      foreach ($stories as $story)
      {
         $diskStory = $this->getStory($story['id']);

         if ($diskStory === false)
         {
            $diskStory = array(
               'last_reply_id' => 0,
               'story_name'    => $story['name'],
               'story_id'      => $story['id'],
               'comment_count' => -1,
               'threads'       => array());
         }
         
         if ($story['comment_count'] == $diskStory['comment_count'])
         {
            printf("Unchanged story %u\n", $story['id']);
            continue;
         }
         else
         {
            printf("Story %u changed.  Old: %u, New: %u\n", $story['id'], $diskStory['comment_count'], $story['comment_count']);
         }
         
         $lastReplyID = $diskStory['last_reply_id'];
         $lastPage    = 1;

         $diskStory['comment_count'] = $story['comment_count'];
         
         # Keep scanning chatty pages until we hit a thread that hasn't
         # been bumped since our last refresh.
         for ($page = 1; $page <= $lastPage; $page++)
         {
            printf("Getting page %u of story %u\n", $page, $story['id']);
            $liveStory = $chattyParser->getStory($story['id'], $page);
            $lastPage  = $liveStory['last_page'];

            foreach ($liveStory['threads'] as $liveThread)
            {
               $liveThreadID = $liveThread['id'];
               
               if (isset($diskStory['threads'][$liveThreadID]) &&
                   $liveThread['last_reply_id'] <= $diskStory['threads'][$liveThreadID]['last_reply_id'])
               {
                  # This thread hasn't been bumped since our last refresh.
                  break 2;
               }
               
               # Update this thread with full post bodies.
               printf("Getting thread %u\n", $liveThreadID);
               $diskStory['threads'][$liveThreadID] = $this->fillBodies($liveThread);
            }

            if ($page == 1 && count($liveStory['threads']) > 0)
               $diskStory['last_reply_id'] = $liveStory['threads'][0]['last_reply_id'];
         }
         
         $this->setStory($story['id'], $diskStory);
      }
   }
   
   private function fillBodies(&$tree)
   {
      # From $tree we can grab the real root thread ID.
      $threadID = $tree['replies'][0]['id'];
      $bodies   = ThreadParser()->getThreadBodies($threadID);
      
      # Build a hashtable from the bodies.
      $bodies_table = array();
      foreach ($bodies['replies'] as $body)
         $bodies_table[$body['id']] = array('body' => $body['body'], 'date' => $body['date']);
      
      # Add the bodies to the tree.
      foreach ($tree['replies'] as $i => $reply)
      {
         if (isset($bodies_table[$reply['id']]))
         {
            $reply['body'] = $bodies_table[$reply['id']]['body'];
            $reply['date'] = $bodies_table[$reply['id']]['date'];
            $tree['replies'][$i] = $reply;
         }
      }
      
      return $tree;
   }
   
   public function search($terms, $author = '', $parentAuthor = '', $category = '', $resultLimit = 0, $updateDiv = false)
   {
      $author = strtolower($author);
      $parentAuthor = strtolower($parentAuthor);
      $results = array();
   
      foreach ($this->getStories() as $storyNum => $storyID)
      {
         if ($resultLimit > 0 && count($results) > $resultLimit)
            break;
            
         if ($updateDiv && $storyNum % 5 == 0)
         {
            ?><script type="text/javascript">document.getElementById("progressInner").style.width = <?=$storyNum?>;</script><?
            flush();
         }
      
         $serializedStory = $this->getStory($storyID, false);
         
         # If the search text isn't in the serialized blob, then we won't waste time
         # unserializing and processing it.
         $lowerSerializedStory = strtolower($serializedStory);
         if (!empty($terms) && strstr($lowerSerializedStory, strtolower($terms)) === false)
            continue;
         else if (!empty($author) && strstr($lowerSerializedStory, strtolower($author)) === false)
            continue;
         else if (!empty($parentAuthor) && strstr($lowerSerializedStory, strtolower($parentAuthor)) === false)
            continue;
         
         $story = unserialize($serializedStory);
         
         foreach ($story['threads'] as $threadID => $thread)
         {
            foreach ($thread['replies'] as $i => $reply)
            {
               $match = true;
               $thisParentAuthor = '';
               
               $thisDepth = intval($reply['depth']);
               
               # Locate the parent post.
               if ($thisDepth > 0)
               {
                  $j = $i - 1;
                  for (; $j >= 0 && $thread['replies'][$j]['depth'] >= $thisDepth; $j--)
                  {
                     # Nothing.
                  }
                  
                  $thisParentAuthor = $thread['replies'][$j]['author'];
               }
               
               if (!isset($reply['body']) || !isset($reply['author']) || !isset($reply['category']))
                  continue;
               
               if (!empty($terms))
                  $match &= (stripos(strip_tags($reply['body']), $terms) !== false);
               
               if (!empty($author))
                  $match &= ($author == strtolower($reply['author']));
               
               if (!empty($parentAuthor))
                  $match &= ($parentAuthor == strtolower($thisParentAuthor));
                  
               if (!empty($category))
                  $match &= ($category == $reply['category']);
               
               if ($match)
               {
                  $results[$reply['id']] = array(
                     'id'           => $reply['id'],
                     'story_id'     => $story['story_id'],
                     'story_name'   => $story['story_name'],
                     'thread_id'    => $threadID,
                     'author'       => $reply['author'],
                     'parentAuthor' => $thisParentAuthor,
                     'preview'      => $reply['preview'],
                     'category'     => $reply['category'],
                     'date'         => $reply['date']);
               }
            }
         }
      }
      
      ksort($results);
      
      return array_reverse($results);
   }
   
   private function getStories()
   {
      $stories = array();
      
      foreach (glob(search_data_directory . '*.story') as $file)
         $stories[] = substr(basename($file), 0, -6);
      
      return array_reverse($stories);
   }
   
   private function getStory($storyID, $unserialized = true)
   {
      $file = search_data_directory . $storyID . '.story';
      if (file_exists($file))
      {
         if ($unserialized)
            return unserialize(file_get_contents($file));
         else
            return file_get_contents($file);
      }
      else
         return false;
   }
   
   private function setStory($storyID, &$data)
   {
      $serialized_data = serialize($data);
   
      # The authoritative data source is the .story files in the data directory.
      $file = search_data_directory . $storyID . '.story';
      file_put_contents($file, $serialized_data);
      chmod($file, 0666);
   }
   
   private function nukeStory($storyID)
   {
      $file = search_data_directory . $storyID . '.story';
      unlink($file);
   }
}

function SearchEngine()
{
   return new SearchEngine();
}