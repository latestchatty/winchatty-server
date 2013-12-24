<?
require_once '../../include/Global.php';

class BookmarkService
{
   public function getBookmarks($username)
   {
      $data = get_bookmark_data($username);
      return $data['bookmarks'];
   }
   
   public function addBookmark($username, $note, $storyID, $postID, $preview, $author, $flag)
   {
      $data = get_bookmark_data($username);
      $data = add_bookmark($data, $storyID, $postID, $note, $preview, $author, $flag);
      write_bookmark_data($username, $data);
      return true;
   }
	
   public function deleteBookmark($username, $postID)
   {
      $data = get_bookmark_data($username);
      $data = delete_bookmark($data, $postID);
      write_bookmark_data($username, $data);
      return true;
   }   
}