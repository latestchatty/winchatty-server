<?
require_once '../../include/Global.php';

class ChattyService
{
   public function getStories()
   {
      return StoriesFeedParser()->getStories();
   }

   public function getStory($storyID, $page)
   {
      return ChattyParser()->getStory($storyID, $page);
   }
   
   public function getThreadBodies($threadID)
   {
      return ThreadParser()->getThreadBodies($threadID);
   }
   
   public function getThreadTree($threadID)
   {
      return ThreadParser()->getThreadTree($threadID);
   }
   
   public function getThread($threadID)
   {
      return ThreadParser()->getThread($threadID);
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
         return true;
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
}