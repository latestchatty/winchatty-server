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

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, true);
      curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
      curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty API');
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);

      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
      $result = curl_exec($curl);
      
      curl_close($curl);
      
      $expected  = "ok $tag$post_id";
      if (strpos($result, $expected) === false)
         throw new Exception($result);
      else
         return true;
   }
}
