<?
require_once '../include/Global.php';

$xmlText = file_get_contents('http://www.shacknews.com/rss?recent_articles=1');
$root = new SimpleXMLElement($xmlText);

$list = $root->channel->item;

$retList = array();
foreach ($list as $item)
{
   $url = strval($item->link);

   $lastSlashIndex = strrpos($url, '/');
   $choppedUrl = substr($url, 0, $lastSlashIndex);
   $lastSlashIndex = strrpos($choppedUrl, '/');
   $id = intval(substr($choppedUrl, $lastSlashIndex + 1));

   $retList[] = array(
      'body' => strval($item->description),
      'comment_count' => 0,
      'date' => nsc_v1_date(strtotime(strval($item->pubDate))),
      'id' => $id,
      'name' => strval($item->title),
      'preview' => nsc_previewFromBody(strval($item->description)),
      'url' => $url,
      'thread_id' => ''
   );
}

echo json_encode($retList);
