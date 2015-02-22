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

#die('<center style="color: red;">NuSearch is offline temporarily while I reindex the database.  My PHP script keeps segfaulting :(  -electroly &hearts;</center>');
require_once 'include/Global.php';

function previewFromBody($body)
{
   $body = str_replace('<br />', ' ', $body);
   $body = strip_tags($body, '<span><b><i><u><strike>');
   return $body;
}

function categoryToHTML($category)
{
   switch ($category)
   {
      case 1: return '<div class="cat_ontopic"></div>'; break;
      case 2: return '<div class="cat_nws"></div>'; break;
      case 3: return '<div class="cat_stupid"></div>'; break;
      case 4: return '<div class="cat_political"></div>'; break;
      case 5: return '<div class="cat_tangent"></div>'; break;
      case 6: return '<div class="cat_informative"></div>'; break;
      default: return '';
   }
}

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$model = array();
$model['offset'] = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$model['terms'] = isset($_GET['q']) ? trim($_GET['q']) : '';
$model['author'] = isset($_GET['a']) ? trim($_GET['a']) : '';
$model['parentAuthor'] = isset($_GET['pa']) ? trim($_GET['pa']) : '';
$model['category'] = isset($_GET['c']) ? $_GET['c'] : '';
$model['home'] = $model['terms'] == '' &&
                 $model['author'] == '' &&
                 $model['parentAuthor'] == '' &&
                 $model['category'] == '';

if ($model['home'])
   die('No search terms.');

# Look for quoted strings.
$termsChars = str_split($model['terms']);
$quotedStrings = array();
$currentQuotedString = '';
$inQuotedString = false;
foreach ($termsChars as $ch)
{
   if ($ch == '"')
   {
      if ($inQuotedString)
      {
         # End of quoted string.
         $quotedStrings[] = $currentQuotedString;
         $currentQuotedString = '';
      }

      $inQuotedString = !$inQuotedString;
   }
   else if ($inQuotedString)
   {
      $currentQuotedString .= $ch;
   }
}

# Remove the quoted strings from the original terms string.
#foreach ($quotedStrings as $quotedString)
#   $model['terms'] = trim(str_replace('"' . $quotedString . '"', '', $model['terms']));

$categoryStr = $model['category'];
$category = false;
switch ($model['category'])
{
   case 'on-topic': $category = 1; break;
   case 'not work safe': $category = 2; break;
   case 'stupid': $category = 3; break;
   case 'political/religious': $category = 4; break;
   case 'tangent': $category = 5; break;
   case 'informative': $category = 6; break;
}
$model['category'] = $category;

$pg = nsc_connectToDatabase();
if ($pg === false)
   die('Failed to connect to chatty database.');
$sql = false;
$args = array();
$results = array();
$none = false;
$limit = 35;

if (V2_SEARCH_ENGINE == 'duct-tape')
{
   $ids = dts_search($model['terms'], $model['author'], $model['parentAuthor'], 
      empty($model['category']) ? 0 : $model['category'], $model['offset'], $limit, true);

   if (empty($ids))
      $none = true;
   else
      $sql = 'SELECT post.id, post.thread_id, post.parent_id, post.author, post.category, post.date, post.body ' .
         'FROM post WHERE post.id IN (' . implode(',', $ids) . ') ORDER BY post.id DESC';
}
else
{
   $offset = $model['offset'];
   $sql = "SELECT post.id, post.thread_id, post.parent_id, post.author, post.category, post.date, post.body FROM post ";
   $where = '';
   $args = array();
   $num = 1;
   $prev = false;
   $join = '';

   if ($model['terms'] != '')
   {
      if ($prev)
         $where .= " AND ";
      $where .= " post_index.body_c_ts @@ plainto_tsquery('english', " . '$' . $num++ . ") ";
      $join .= ' INNER JOIN post_index ON post.id = post_index.id ';
      $args[] = $model['terms'];
      $prev = true;
   }

   if ($model['author'] != '')
   {
      if ($prev)
         $where .= " AND ";
      $where .= ' post.author_c = $' . $num++ . ' ';
      $args[] = strtolower($model['author']);
      $prev = true;
   }

   if ($model['parentAuthor'] != '')
   {
      if ($prev)
         $where .= " AND ";
      $sql .= ' INNER JOIN post AS post2 ON post.parent_id = post2.id ';
      $where .= ' post2.author_c = $' . $num++ . ' ';
      $args[] = strtolower($model['parentAuthor']);
      $prev = true;
   }

   if ($model['category'] !== false)
   {
      if ($prev)
         $where .= " AND ";
      $where .= ' post.category = $' . $num++ . ' ';
      $args[] = intval($model['category']);
      $prev = true;
   }

   foreach ($quotedStrings as $quotedString)
   {
      die('<div style="text-align: center; color: red; font-style: italic;">Quoted string searches are disabled for now. -electroly &hearts;</div>');
      if ($prev)
         $where .= " AND ";
      $where .= ' post.body_c LIKE $' . $num++ . ' ';
      $s = strtolower($quotedString);
      $s = str_replace('_', "\\_", $s); # not used as wildcard here
      $s = str_replace('%', "\\%", $s); # not used as wildcard here
      $args[] = '%' . $s . '%';
      $prev = true;
   }

   $sql .= $join;
   $sql .= ' WHERE ' . $where;
   $sql .= ' ORDER BY post.id DESC LIMIT $' . $num++;
   $sql .= ' OFFSET $' . $num++;
   $args[] = $limit;
   $args[] = $offset;
}

if (!$none)
{
   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      die('Failed to execute SQL query: ' . $sql);

   while (true)
   {
      $row = pg_fetch_row($rs);
      if ($row === false)
         break;

      $results[] = array(
         'id' => $row[0],
         'thread_id' => $row[1],
         'parent_id' => $row[2],
         'author' => $row[3],
         'category' => $row[4],
         'date' => $row[5],
         'body' => $row[6]
      );
   }
}

pg_close($pg);
?>
<table style="margin: 0 auto;">
   <? foreach ($results as $result) 
      { 
         if (intval($result['id']) == 0)
            continue;
   ?>
   <tr>
      <td class="body"><?=categoryToHTML($result['category'])?><a title="<?=htmlspecialchars(strip_tags(str_replace('<br />', "\n", $result['body'])))?>" class="resultLink" href="http://www.shacknews.com/chatty?id=<?=$result['id']?>#item_<?=$result['id']?>"><?=previewFromBody($result['body'])?></a></td>
      <td class="author"><a class="resultLink" href="/nusearch?a=<?=urlencode($result['author'])?>"><?=$result['author']?></a></td>
      <td class="date"><nobr><?=date('M d, Y h:i A', strtotime($result['date']))?></nobr></td>
   </tr>
   <? } ?>
</table>
<br>
<? if (count($results) == $limit) { ?>
<script>
$("#loadMore").show();
</script>
<? } ?>
