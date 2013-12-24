<?
class LOLService
{
   public function tag($username, $post_id, $tag)
   {
      $username  = urlencode($username);
      $post_id   = intval($post_id);
      $tag       = urlencode($tag);
      $addr      = $_SERVER['REMOTE_ADDR'];
      		
      $args      = "who=$username&what=$post_id&tag=$tag&version=-1&addr=$addr";

      $url       = 'http://lmnopc.com/greasemonkey/shacklol/report.php';
      $result    = file_get_contents($url . '?' . $args);      
      
      $expected  = "ok $tag$post_id";
      if (strpos($result, $expected) === false)
         throw new Exception($result);
      else
         return true;
   }
}
