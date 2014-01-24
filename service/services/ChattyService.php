<?
require_once '../../include/Global.php';

class ChattyService
{
   public function getStories()
   {
      #return StoriesFeedParser()->getStories();
      return array(
         array(
            'title' => 'Latest Chatty',
            'story_id' => 0
         )
      );
   }

   public function getStory($storyID, $page)
   {
      #return ChattyParser()->getStory($storyID, $page);
      $pg = nsc_connectToDatabase();
      return nsc_v1_getStory($pg, $page);
   }
   
   public function getThreadBodies($threadID)
   {
      #return ThreadParser()->getThreadBodies($threadID);
      $pg = nsc_connectToDatabase();
      return nsc_v1_getThreadBodies($pg, $threadID);
   }
   
   public function getThreadTree($threadID)
   {
      #return ThreadParser()->getThreadTree($threadID);
      $pg = nsc_connectToDatabase();
      return nsc_v1_getThreadTree($pg, $threadID);
   }
   
   public function getThread($threadID)
   {
      #return ThreadParser()->getThread($threadID);
      $pg = nsc_connectToDatabase();
      return nsc_v1_getThreadTree($pg, $threadID);
   }
   
   public function post($username, $password, $parentID, $storyID, $body)
   {
      if ($parentID == 0)
         $parentID = '';
   
      $ret = ChattyParser()->post($username, $password, $parentID, $storyID, $body);

      if (strstr($ret, 'You must be logged in to post') !== false)
         throw new Exception('Invalid username or password.');
      else if (strstr($ret, 'Please wait a few minutes before trying to post again.') !== false)
         throw new Exception("* P R L ' D *");
      else if (strstr($ret, 'fixup_postbox_parent_for_remove(') !== false)
      {
         sleep(5);
         return true;
      }
      else
         throw new Exception($ret);
   }
   
   public function search($terms, $author, $parentAuthor, $category, $page)
   {
      //return SearchEngine()->memorySearch($terms, $author, $parentAuthor, $category, $page * 50, 50);
      return SearchParser()->search($terms, $author, $parentAuthor, $category, $page);
   }
   
   public function locatePost($postID, $storyID)
   {
      return ChattyParser()->locatePost($postID, $storyID);
   }

   public function getNewestEventId()
   {
      $filePath = '/mnt/ssd/ChattyIndex/LastEventID';
      $eventId = intval(file_get_contents($filePath));
      return $eventId;
   }

   public function waitForEventId($lastId)
   {
      $filePath = '/mnt/ssd/ChattyIndex/LastEventID';
      while (intval(file_get_contents($filePath)) <= $lastId)
      {
         sleep(1);
         # I know, right?  Gets the job done though.  Programming is hard.
      }

      return intval(file_get_contents($filePath));
   }

   public function verifyCredentials($username, $password)
   {
      try
      {
         MessageParser()->getUserID($username, $password);
         return "valid";
      }
      catch (Exception $e)
      {
         $message = $e->getMessage();

         if (trim(strtolower($message)) == 'unable to log into user account.')
            return "invalid";

         throw $e;
      }
   }
}