<?
require_once '../../include/Global.php';

class ClassicService
{
   public function getStory($storyID, $page)
   {
      return ClassicAdapter::getStory($storyID, $page);
   }

   public function getThread($threadID)
   {
      return ClassicAdapter::getThread($threadID);
   }
   
   public function search($terms, $author, $parentAuthor, $page)
   {
      return ClassicAdapter::search($terms, $author, $parentAuthor, $page);
   }
   
   public function getStories()
   {
      return FrontPageParser()->getStories();
   }
   
   public function getArticle($storyID)
   {
      return ArticleParser()->getArticle($storyID);
   }
   
   public function getMessages($username, $password)
   {
      return ClassicAdapter::getMessages($username, $password);
   }
}
