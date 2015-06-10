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

# Convenience
function nsc_initJsonGet()
{
   nsc_jsonHeader();
   header("Cache-Control: no-cache, must-revalidate");
   header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
   nsc_assertGet();
   return nsc_connectToDatabase();
}

# Convenience
function nsc_initJsonPost()
{
   nsc_jsonHeader();
   header("Cache-Control: no-cache, must-revalidate");
   header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
   nsc_assertPost();
   return nsc_connectToDatabase();
}

function nsc_assert($condition, $message)
{
   if (!$condition)
      nsc_die('ERR_SERVER', $message);
}

function nsc_getArg($parName, $parType, $def = null)
{
   global $_GET;
   return nsc_arg($_GET, $parName, $parType, $def);
}

function nsc_postArg($parName, $parType, $def = null)
{
   global $_POST;
   return nsc_arg($_POST, $parName, $parType, $def);
}

function nsc_arg($args, $parName, $parType, $def = null)
{
   $parType = str_replace('*', '+?', $parType);
   $isMissing = !isset($args[$parName]) || ($args[$parName] === '');

   if (strpos($parType, '?') !== false)
   {
      $parType = str_replace('?', '', $parType);
      if ($isMissing)
         return $def;
   }
   else if ($isMissing)
      nsc_die('ERR_ARGUMENT', "Missing argument '$parName'.");

   $max = 2147483647;
   if (strpos($parType, ',') !== false)
   {
      $parts = explode(',', $parType);
      $max = intval($parts[1]);
      $parType = $parts[0];
   }

   $isList = false;
   if (strpos($parType, '+') !== false)
   {
      $parType = str_replace('+', '', $parType);
      $isList = true;
   }

   $arg = $args[$parName];
   $ok = false;
   $argItems = explode(',', $arg);

   if ($isList && count($argItems) > $max)
      nsc_die('ERR_ARGUMENT', "Too many arguments in the list '$parName'.");

   foreach ($argItems as $argItem)
   {
      switch ($parType)
      {
         case 'INT':
            $pattern = '/^[0-9]+$/';
            $ok = preg_match($pattern, $argItem) === 1;
            if (!$isList && intval($argItem) > $max)
               nsc_die('ERR_ARGUMENT', "Argument '$parName' is out of range.  Maximum: $max.");
            break;
         case 'BIT':
            $ok = ($argItem == 'true' || $argItem == 'false');
            break;
         case 'STR':
            $ok = true;
            break;
         case 'DAT':
            $ok = (strtotime($argItem) !== false);
            break;
         case 'MOD':
            $ok = ($argItem == 'ontopic' || $argItem == 'nws' || $argItem == 'stupid' || $argItem == 'political' || 
               $argItem == 'tangent' || $argItem == 'informative');
            break;
         case 'MODN':
            $ok = ($argItem == 'ontopic' || $argItem == 'nws' || $argItem == 'stupid' || $argItem == 'political' || 
               $argItem == 'tangent' || $argItem == 'informative' || $argItem == 'nuked');
            break;
         case 'MBX':
            $ok = ($argItem == 'inbox' || $argItem == 'sent');
            break;
         case 'PET':
            $ok = ($argItem == 'nuked' || $argItem == 'unnuked' || $argItem == 'flagged');
            break;
         case 'MPT':
            $ok = ($argItem == 'unmarked' || $argItem == 'pinned' || $argItem == 'collapsed');
            break;
         default:
            nsc_die('ERR_ARGUMENT', "Invalid parameter type '$parType'.");
            break;
      }
      if (!$ok)
      {
         nsc_die('ERR_SERVER', "Argument value for parameter '$parName' is invalid. Expected type: $parType.");
      }
   }

   # Enum data types are returned as-is (as strings)
   switch ($parType)
   {
      case 'MOD':
      case 'MODN':
      case 'MBX':
      case 'PET':
      case 'MPT':
         $parType = 'STR';
         break;
   }

   if ($isList && $parType == 'INT')
   {
      $intList = array();
      foreach ($argItems as $str)
      {
         $val = intval($str);
         if ($val > 2147483647)
            nsc_die('ERR_ARGUMENT', "Argument '$parName' is out of range.  Maximum: 2147483647.");
         $intList[] = intval($str);
      }
      return $intList;
   }
   else if (!$isList && $parType == 'INT')
   {
      return intval($arg);
   }
   else if ($isList && $parType == 'DAT')
   {
      $timeList = array();
      foreach ($argItems as $str)
         $timeList[] = strtotime($str);
      return $timeList;
   }
   else if (!$isList && $parType == 'DAT')
   {
      return strtotime($arg);
   }
   else if ($isList && $parType == 'BIT')
   {
      $boolList = array();
      foreach ($argItems as $str)
         $boolList[] = ($str == 'true');
      return $boolList;
   }
   else if (!$isList && $parType == 'BIT')
   {
      return strval($arg) == 'true';
   }
   else if ($isList && $parType == 'STR')
   {
      return $argItems;
   }
   else if (!$isList && $parType == 'STR')
   {
      return strval($arg);
   }
   else
   {
      nsc_die('ERR_ARGUMENT', 'Unrecognized parameter type.');
   }
}

function nsc_jsonHeader()
{
   header('Content-type: application/json');
   header('Access-Control-Allow-Origin: *');
}

function nsc_assertGet()
{
   if ($_SERVER['REQUEST_METHOD'] !== 'GET')
      nsc_die('ERR_ARGUMENT', 'Must be a GET request.');
}

function nsc_assertPost()
{
   if ($_SERVER['REQUEST_METHOD'] !== 'POST')
      nsc_die('ERR_ARGUMENT', 'Must be a POST request.');
}

function nsc_connectToDatabase() # postgresql
{
   $pg = pg_connect(V2_CONNECTION_STRING);
   if ($pg === false)
      nsc_die('ERR_SERVER', "Failed to connect to chatty database.");
   return $pg;
}

function nsc_disconnectFromDatabase($pg) # void
{
   if (!is_null($pg) && $pg !== false)
      pg_close($pg);
}

function nsc_die($code, $message)
{
   if (is_string($code) && is_string($message))
      die(json_encode(array('error' => true, 'code' => $code, 'message' => $message)));
   else
      die(json_encode(array('error' => true, 'code' => 'ERR_SERVER', 'message' => 'Invalid call to nsc_die().')));
}

function nsc_selectValueOrFalse($pg, $sql, $args) # value or false
{
   $row = nsc_selectRowOrFalse($pg, $sql, $args);
   return $row === false ? false : $row[0];
}

function nsc_selectValue($pg, $sql, $args) # value
{
   $ret = nsc_selectValueOrFalse($pg, $sql, $args);
   if ($ret === false)
      nsc_die('ERR_SERVER', "SQL query returned zero rows.");
   else
      return $ret;
}

function nsc_selectRowOrFalse($pg, $sql, $args) # dict or false
{
   $args = nsc_preProcessSqlArgs($args);
   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      nsc_die('ERR_SERVER', "selectValue failed.");
   $row = pg_fetch_row($rs);
   if ($row === false)
      return false;
   else
      return $row;
}

function nsc_selectRow($pg, $sql, $args) # dict
{
   $ret = nsc_selectRowOrFalse($pg, $sql, $args);
   if ($ret === false)
      nsc_die('ERR_SERVER', "SQL query returned zero rows.");
   else
      return $ret;
}

function nsc_selectArray($pg, $sql, $args) # array of scalar values
{
   $args = nsc_preProcessSqlArgs($args);
   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      nsc_die('ERR_SERVER', "selectValue failed.");
   $ret = array();
   while (true)
   {
      $row = pg_fetch_row($rs);
      if ($row === false)
         break;
      else
         $ret[] = $row[0];
   }
   return $ret;
}

function nsc_query($pg, $sql, $args) # array of rows
{
   $args = nsc_preProcessSqlArgs($args);
   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      nsc_die('ERR_SERVER', "selectAll failed.");
   $ret = array();
   while (true)
   {
      $row = pg_fetch_row($rs);
      if ($row === false)
         break;
      else
         $ret[] = $row;
   }
   return $ret;
}

function nsc_execute($pg, $sql, $args) # void
{
   $args = nsc_preProcessSqlArgs($args);
   if (pg_query_params($pg, $sql, $args) === false)
      nsc_die('ERR_SERVER', "SQL execute failed.");
}

function nsc_preProcessSqlArgs($args) # array
{
   $newArgs = array();
   foreach ($args as $arg)
   {
      if ($arg === true || $arg === false)
         $newArgs[] = $arg ? 1 : 0;
      else
         $newArgs[] = $arg;
   }
   return $newArgs;
}

function nsc_previewFromBody($body)
{
   $preview = nsc_removeSpoilers($body, false);
   $preview = str_replace("<br />", " ", $preview);
   $preview = str_replace("<br/>", " ", $preview);
   $preview = str_replace("<br>", " ", $preview);
   $preview = str_replace("\n", " ", $preview);
   $preview = str_replace("\r", " ", $preview);
   $preview = nsc_strReplaceAll("  ", " ", $preview);
   $preview = strip_tags($preview, '<span><b><i><u>');
   return $preview;
}

function nsc_strReplaceAll($needle, $replacement, $haystack)
{
   while (strstr($haystack, $needle))
      $haystack = str_replace($needle, $replacement, $haystack);
   return $haystack;
}

function nsc_removeSpoilers($text)
{
   $spoilerSpan    = 'span class="jt_spoiler"';
   $spoilerSpanLen = strlen($spoilerSpan);
   $span           = 'span ';
   $spanLen        = strlen($span);
   $endSpan        = '/span>';
   $endSpanLen     = strlen($endSpan);
   $replaceStr     = "_______";
   $out            = '';
   $inSpoiler      = false;
   $depth          = 0;
   
   # Split by < to get all the tags separated out.
   foreach (explode('<', $text) as $i => $chunk)
   {
      if ($i == 0)
      {
         # The first chunk does not start with or contain a <, so we can
         # just copy it directly to the output.
         $out .= $chunk;
      }
      else if ($inSpoiler)
      {
         if (strncmp($chunk, $span, $spanLen) == 0)
         {
            # Nested Shacktag.
            $depth++;
         }
         else if (strncmp($chunk, $endSpan, $endSpanLen) == 0)
         {
            # End of a Shacktag.
            $depth--;

            # If the depth has dropped back to zero, then we found the end
            # of the spoilered text.
            if ($depth == 0)
            {
               $out      .= substr($chunk, $endSpanLen);
               $inSpoiler = false;
            }
         }
      }
      else
      {
         if (strncmp($chunk, $spoilerSpan, $spoilerSpanLen) == 0)
         {
            # Beginning of a spoiler.
            $inSpoiler = true;
            $depth     = 1;
            $out      .= $replaceStr;
         }
         else
         {
            $out .= '<' . $chunk;
         }
      }
   }
   
   return $out;
}

function nsc_flagIntToString($num)
{
   $category = 'ontopic';
   switch ($num)
   {
      case 1: $category = 'ontopic'; break;
      case 2: $category = 'nws'; break;
      case 3: $category = 'stupid'; break;
      case 4: $category = 'political'; break;
      case 5: $category = 'tangent'; break;
      case 6: $category = 'informative'; break;
      case 7: $category = 'nuked'; break;
   }
   return $category;
}

function nsc_newPostFromRow($row)
{
   return array(
      'id' => intval($row[0]),
      'threadId' => intval($row[1]),
      'parentId' => intval($row[2]),
      'author' => strval($row[3]),
      'category' => nsc_flagIntToString($row[4]),
      'date' => nsc_date(strtotime($row[5])),
      'body' => strval($row[6])
      //NOTE: nsc_infuseLolCounts() must be used to inject the 'lols' field.
   );
}

function nsc_infuseLolCounts($posts, $lols)
{
   # $posts is a list of [POST] objects without the 'lols' field set.
   # $lols is a list of records (post_id, tag, count) from the post_lols table.
   $lolsLookup = array();
   foreach ($lols as $lol)
   {
      $postId = intval($lol[0]);
      $tag = strval($lol[1]);
      $count = intval($lol[2]);
      $list = array();

      if (isset($lolsLookup[$postId]))
         $list = $lolsLookup[$postId];

      $list[] = array('tag' => $tag, 'count' => $count);
      $lolsLookup[$postId] = $list;
   }
   foreach ($posts as &$post)
   {
      $postId = intval($post['id']);
      if (isset($lolsLookup[$postId]))
         $post['lols'] = $lolsLookup[$postId];
      else
         $post['lols'] = array();
   }
   return $posts;
}

function nsc_getPosts($pg, $idList)
{
   $idListStr = implode(',', $idList);
   $rows = nsc_query($pg, 
      "SELECT id, thread_id, parent_id, author, category, date, body FROM post WHERE id IN ($idListStr)", 
      array());
   $posts = array_map('nsc_newPostFromRow', $rows);
   $lols = nsc_query($pg,
      "SELECT post_id, tag, count FROM post_lols WHERE post_id IN ($idListStr)", array());
   return nsc_infuseLolCounts($posts, $lols);
}

function nsc_getThreadId($pg, $postId)
{
   return intval(nsc_selectValue($pg, 'SELECT thread_id FROM post WHERE id = $1', array(intval($postId))));
}

function nsc_getThread($pg, $id, $possiblyMissing = false, $sort = false)
{
   $id = intval($id);
   $threadId = nsc_selectValueOrFalse($pg, 'SELECT thread_id FROM post WHERE id = $1', array($id));
   if ($threadId === false)
   {
      if ($possiblyMissing)
         return false;
      else
         nsc_die('ERR_SERVER', "The post $id does not exist.");
   }
   $bumpDate = nsc_selectValueOrFalse($pg, 'SELECT bump_date FROM thread WHERE id = $1', array($threadId));
   if ($bumpDate === false)
   {
      if ($possiblyMissing)
         return false;
      else
         nsc_die('ERR_SERVER', "The thread $threadId does not exist.");
   }
   $orderBy = $sort ? ' ORDER BY id ' : '';
   $rows = nsc_query($pg, 
      'SELECT id, thread_id, parent_id, author, category, date, body FROM post WHERE thread_id = $1 ' . $orderBy, 
      array($threadId));
   $posts = array_map('nsc_newPostFromRow', $rows);

   nsc_execute($pg, 'BEGIN', array());
   nsc_execute($pg, 'SET LOCAL ENABLE_MERGEJOIN TO OFF', array());
   nsc_execute($pg, 'SET LOCAL ENABLE_HASHJOIN TO OFF', array());

   $lols = nsc_query($pg,
      'SELECT post_id, tag, count FROM post_lols WHERE post_lols.post_id IN (SELECT id FROM post WHERE thread_id = $1)',
      array($threadId));

   nsc_execute($pg, 'SET LOCAL ENABLE_MERGEJOIN TO ON', array());
   nsc_execute($pg, 'SET LOCAL ENABLE_HASHJOIN TO ON', array());
   nsc_execute($pg, 'ROLLBACK', array());

   return array(
      'threadId' => $threadId,
      'posts' => nsc_infuseLolCounts($posts, $lols)
   );
}

function nsc_getThreadIds($pg, $id, $possiblyMissing = false)
{
   $id = intval($id);
   $threadId = nsc_selectValueOrFalse($pg, 'SELECT thread_id FROM post WHERE id = $1', array($id));
   if ($threadId === false)
   {
      if ($possiblyMissing)
         return false;
      else
         nsc_die('ERR_SERVER', "The post $id does not exist.");
   }
   $bumpDate = nsc_selectValueOrFalse($pg, 'SELECT bump_date FROM thread WHERE id = $1', array($threadId));
   if ($bumpDate === false)
   {
      if ($possiblyMissing)
         return false;
      else
         nsc_die('ERR_SERVER', "The thread $threadId does not exist.");
   }
   $ids = nsc_selectArray($pg, 'SELECT id FROM post WHERE thread_id = $1', array($threadId));
   return array(
      'threadId' => $threadId,
      'postIds' => $ids
   );
}

function nsc_getPostRange($pg, $startId, $count, $reverse = false)
{
   $startId = intval($startId);
   $count = intval($count);
   if ($reverse)
   {
      $rows = nsc_query($pg,
         'SELECT id, thread_id, parent_id, author, category, date, body FROM post WHERE id <= $1 ORDER BY id DESC LIMIT $2',
         array($startId, $count));
      $lols = nsc_query($pg,
         'SELECT post_id, tag, count FROM post_lols WHERE post_id <= $1 ORDER BY post_id DESC LIMIT $2', 
         array($startId, $count));
   }
   else
   {
      $rows = nsc_query($pg,
         'SELECT id, thread_id, parent_id, author, category, date, body FROM post WHERE id >= $1 ORDER BY id LIMIT $2',
         array($startId, $count));
      $lols = nsc_query($pg,
         'SELECT post_id, tag, count FROM post_lols WHERE post_id >= $1 ORDER BY post_id LIMIT $2', 
         array($startId, $count));
   }
   $posts = array_map('nsc_newPostFromRow', $rows);
   return nsc_infuseLolCounts($posts, $lols);
}

function nsc_search($pg, $terms, $author, $parentAuthor, $category, $offset, $limit, $oldestFirst)
{
   $model = array(
      'terms' => $terms,
      'author' => $author,
      'parentAuthor' => $parentAuthor,
      'category' => $category,
      'offset' => $offset,
      'limit' => $limit,
      'oldestFirst' => $oldestFirst
   );

   $categoryStr = $model['category'];
   $category = false;
   switch ($model['category'])
   {
      case 'ontopic': $category = 1; break;
      case 'nws': $category = 2; break;
      case 'stupid': $category = 3; break;
      case 'political': $category = 4; break;
      case 'tangent': $category = 5; break;
      case 'informative': $category = 6; break;
   }
   $model['category'] = $category;

   $limit = $model['limit'];
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

   $sql .= $join;
   $sql .= ' WHERE ' . $where;
   if ($oldestFirst)
      $sql .= ' ORDER BY post.id LIMIT $' . $num++;
   else
      $sql .= ' ORDER BY post.id DESC LIMIT $' . $num++;
   $sql .= ' OFFSET $' . $num++;
   $args[] = $limit;
   $args[] = $offset;

   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      nsc_die('ERR_SERVER', 'Failed to execute SQL query: ' . $sql);

   $results = array();
   while (true)
   {
      $row = pg_fetch_row($rs);
      if ($row === false)
         break;

      $results[] = array(
         'id' => intval($row[0]),
         'threadId' => intval($row[1]),
         'parentId' => intval($row[2]),
         'author' => $row[3],
         'category' => nsc_flagIntToString($row[4]),
         'date' => nsc_date(strtotime($row[5])),
         'body' => $row[6]
      );
   }

   //TODO: infuse lol counts
   return $results;
}

function nsc_date($time)
{
   $str = str_replace('+00:00', 'Z', gmdate('c', $time));
   if (strlen($str) != 20)
      nsc_die('ERR_SERVER', 'Formatted timestamp was not 20 characters long: ' . $str);
   return $str;
}

function nsc_getClientSession($pg, $token)
{
   $row = nsc_selectRowOrFalse($pg, 'SELECT username, client_code FROM client_session WHERE token = $1', array($token));
   if ($row === false)
      nsc_die('ERR_INVALID_TOKEN', 'Invalid client session token.');
   else
      return array('username' => $row[0], 'client_code' => $row[1]);
}

function nsc_getShackerId($pg, $username)
{
   if (empty($username))
      nsc_die('ERR_SERVER', 'Empty username passed to nsc_getShackerId.');
   $username = strtolower($username);

   $id = nsc_selectValueOrFalse($pg, 'SELECT id FROM shacker WHERE username = $1', array($username));
   if ($id === false)
   {
      nsc_execute($pg, 
         'INSERT INTO shacker (username, filter_nws, filter_stupid, filter_political, filter_tangent, filter_informative) VALUES ($1, true, true, true, true, true)', 
         array($username));
      return nsc_getShackerId($pg, $username);
   }
   else
   {
      return intval($id);
   }
}

function nsc_markTypeIntToString($markType)
{
   if ($markType == 0)
      return 'unmarked';
   else if ($markType == 1)
      return 'pinned';
   else if ($markType == 2)
      return 'collapsed';
   else
      nsc_die('ERR_SERVER', 'Invalid marked post type value.');
}

function nsc_markTypeStringToInt($markType)
{
   if ($markType == 'unmarked')
      return 0;
   else if ($markType == 'pinned')
      return 1;
   else if ($markType == 'collapsed')
      return 2;
   else
      nsc_die('ERR_SERVER', 'Invalid marked post type value.');
}

function nsc_handleException($e)
{
   $message = $e->getMessage();

   if (trim(strtolower($message)) == 'unable to log into user account.')
      nsc_die('ERR_INVALID_LOGIN', 'Invalid login.');
   else
      nsc_die('ERR_SERVER', $message);   
}

function nsc_getUserRegistrationDate($pg, $username) # unix timestamp
{
   $signupDate = nsc_selectValueOrFalse($pg, 'SELECT signup_date FROM shacker WHERE username = $1', array(strtolower($username)));
   if ($signupDate === false || is_null($signupDate))
   {
      $url = 'http://www.shacknews.com/api/users/' . rawurlencode($username) . '.json';
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
      curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty API');
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_USERPWD, WINCHATTY_USERNAME . ':' . WINCHATTY_PASSWORD);
      $json = curl_exec($curl);
      curl_close($curl);

      if ($json === false)
         return false;

      $data = json_decode($json, true);
      if ($data === false || !is_array($data) || !isset($data['join_date']))
         return false;

      $signupDate = strtotime($data['join_date']);
      if ($signupDate < strtotime('1990-01-01'))
         return false;

      $shackerId = nsc_getShackerId($pg, $username);
      nsc_execute($pg,
         'UPDATE shacker SET signup_date = $1 WHERE id = $2',
         array(date('c', $signupDate), $shackerId));

      return $signupDate;
   }
   else
   {
      return strtotime($signupDate);
   }
}

function nsc_logEvent($pg, $type, $data)
{
   nsc_execute($pg, 'INSERT INTO event (date, type, data) VALUES (NOW(), $1, $2)', array($type, json_encode($data)));

   $rows = nsc_query($pg, 'SELECT id, date, type, data FROM event ORDER BY id DESC LIMIT 101', array());
   $newestRow = $rows[0];
   $newestId = intval($newestRow[0]);
   $oldestId = intval($rows[count($rows) - 1][0]);

   $events = array();
   foreach ($rows as $row)
   {
      $events[] = array(
         'eventId' => intval($row[0]),
         'eventDate' => nsc_date(strtotime($row[1])),
         'eventType' => strval($row[2]),
         'eventData' => json_decode(strval($row[3])),
      );
   }

   nsc_execute($pg, 'DELETE FROM event WHERE id < $1', array($newestId - 10000));

   file_put_contents(V2_DATA_PATH . 'LastEvents2', serialize($events));
   rename(V2_DATA_PATH . 'LastEvents2', V2_DATA_PATH . 'LastEvents');

   file_put_contents(V2_DATA_PATH . 'LastEvents2.json', json_encode($events));
   rename(V2_DATA_PATH . 'LastEvents2.json', V2_DATA_PATH . 'LastEvents.json');

   file_put_contents(V2_DATA_PATH . 'LastEventID2', intval($newestId));
   rename(V2_DATA_PATH . 'LastEventID2', V2_DATA_PATH . 'LastEventID');
}

function nsc_getActiveThreadIds($pg, $expiration = 18, $limit = 1000, $offset = 0)
{
   $rows = nsc_query($pg, 
      "SELECT thread.id FROM thread INNER JOIN post ON thread.id = post.id WHERE thread.date > (NOW() - interval '$expiration hours') ORDER BY thread.bump_date DESC OFFSET \$1 LIMIT \$2",
      array($offset, $limit));
   $ids = array();
   foreach ($rows as $row)
      $ids[] = intval($row[0]);
   return $ids;
}

function nsc_getNewestEventId($pg)
{
   return intval(nsc_selectValue($pg, 'SELECT id FROM event ORDER BY id DESC LIMIT 1', array()));
}

function nsc_reindex($pg, $postId)
{
   $postId = intval($postId);
   nsc_execute($pg, 'INSERT INTO reindex_request (post_id) VALUES ($1)', array($postId));

   while (true)
   {
      $id = nsc_selectValueOrFalse($pg, 'SELECT post_id FROM reindex_request WHERE post_id = $1', array($postId));
      if ($id === false)
         break;
      else
         sleep(1);
   }
}

function nsc_v1_date($time)
{
   $date = new DateTime(date('c', $time));
   $date->setTimezone(new DateTimeZone('America/Los_Angeles'));
   return $date->format('M d, Y g:ia T');
}

function nsc_v1_getThreadBodies($pg, $threadId)
{
   /*
   {
      "replies":
      [
         {
            "category": "stupid",
            "id": 31303256,
            "author": "hashd",
            "body": "blah blah",
            "date": "Jan 02, 2014 10:13pm PST"
         },
         ...
      ]
   }
   */
   $replies = array();

   $thread = nsc_getThread($pg, $threadId, false, true);
   $posts = $thread['posts'];

   $replies = array();
   foreach ($posts as $post)
   {
      $replies[] = array(
         'category' => nsc_v1_fromV2Category($post['category']),
         'id' => strval($post['id']),
         'author' => $post['author'],
         'body' => $post['body'],
         'date' => nsc_v1_date(strtotime($post['date']))
      );
   }

   return array('replies' => $replies);
}

function nsc_v1_getThreadTree($pg, $threadId)
{
   /*
   {
      "body": "...",
      "category": "...",
      "id": "1234",
      "author": "...",
      "date": "Jan 02, 2014 9:44pm PST",
      "preview": "...",
      "reply_count": 26,
      "last_reply_id": 1234,
      "replies":
      [
         {
            "category": "...",
            "id": "1234",
            "author": "...",
            "preview": "...",
            "depth": 0,
            "date": "Jan 02, 2014 9:44pm PST",
            "body": "..."
         },
         ...
      ],
      "story_id": 0,
      "story_name": "Latest Chatty"
   }
   */
   $thread = nsc_getThread($pg, $threadId, false, true);
   $posts = $thread['posts'];
   $threadId = $posts[0]['id'];

   $childrenOf = array();
   foreach ($posts as $post)
   {
      $parentId = $post['parentId'];
      if (isset($childrenOf[$parentId]))
         $childrenOf[$parentId][] = $post;
      else
         $childrenOf[$parentId] = array($post);
   }

   $v1posts = nsc_v1_getThreadTree_build($childrenOf, $posts[0], 0);
   $v1root = $v1posts[0];

   $lastReplyId = 0;
   foreach ($v1posts as $v1post)
      if ($v1post['id'] > $lastReplyId)
         $lastReplyId = $v1post['id'];

   return array(
      'body' => $v1root['body'],
      'category' => nsc_v1_fromV2Category($v1root['category']),
      'id' => strval($v1root['id']),
      'author' => $v1root['author'],
      'date' => $v1root['date'],
      'preview' => $v1root['preview'],
      'reply_count' => count($v1posts),
      'last_reply_id' => $lastReplyId,
      'replies' => $v1posts,
      'story_id' => 0,
      'story_name' => 'Latest Chatty'
   );
}

function nsc_v1_convertFromV2Post($v2post, $depth)
{
   return array(
      'category' => nsc_v1_fromV2Category($v2post['category']),
      'id' => strval($v2post['id']),
      'author' => $v2post['author'],
      'preview' => nsc_previewFromBody($v2post['body']),
      'depth' => $depth,
      'date' => nsc_v1_date(strtotime($v2post['date'])),
      'body' => $v2post['body']
   );
}

function nsc_v1_getThreadTree_build(&$childrenOf, $post, $depth)
{
   $posts = array(nsc_v1_convertFromV2Post($post, $depth));

   if (isset($childrenOf[$post['id']]))
   {
      foreach ($childrenOf[$post['id']] as $child)
      {
         $childPosts = nsc_v1_getThreadTree_build($childrenOf, $child, $depth + 1);
         $posts = array_merge($posts, $childPosts);
      }
   }

   return $posts;
}

function nsc_v1_fromV2Category($v2category)
{
   if ($v2category == 'tangent')
      return 'offtopic';
   else
      return $v2category;
}

function nsc_v1_getStory($pg, $page)
{
   /*
   {
      "threads":
      [
         {
            "body": ""
            "category": ""
            "id", "1234"
            "author": ""
            "date": "Jan 02, 2014, 7:53pm PST"
            "preview": ""
            "reply_count": 1234
            "last_reply_id": 1234
            "replies": 
            [
               {
                  "category": "",
                  "id": "",
                  "author": "",
                  "preview": "",
                  "depth": 0,
                  "date": "Jan 02, 2014, 7:53pm PST",
                  "body": ""
               },
               ...
            ]
         },
         ...
      ],
      "story_id": 0
      "story_name": ""
      "story_text": ""
      "story_author": ""
      "story_date": ""
      "current_page": "1"
      "last_page": 5
   }
   */

   if ($page < 1)
      $page = 1;

   $threadIds = nsc_getActiveThreadIds($pg);

   $skip = ($page - 1) * 40;
   $take = 40;
   $totalPages = ceil(count($threadIds) / 40);

   $filteredThreadIds = array();
   for ($i = $skip; $i < $skip + $take && $i < count($threadIds); $i++)
      $filteredThreadIds[] = $threadIds[$i];

   $threads = array();
   foreach ($filteredThreadIds as $threadId)
      $threads[] = nsc_v1_getThreadTree($pg, $threadId);

   return array(
      'threads' => $threads,
      'story_id' => 0,
      'story_name' => 'Latest Chatty',
      'story_text' => '',
      'story_author' => 'Shacknews',
      'story_date' => nsc_v1_date(time()),
      'current_page' => strval($page),
      'last_page' => $totalPages
   );
}

function nsc_checkLogin($username, $password)
{
   try
   {
      ChattyParser()->isModerator($username, $password);
   }
   catch (Exception $ex)
   {
      nsc_die('ERR_INVALID_LOGIN', 'Invalid username or password.');
   }
}

function nfy_registerClientId($pg, $clientId, $clientName)
{
   $existingClientId = nsc_selectValueOrFalse($pg, 'SELECT id FROM notify_client WHERE id = $1', array($clientId));
   if ($existingClientId === false)
   {
      nsc_execute($pg, 'INSERT INTO notify_client (id, app, name) VALUES ($1, $2, $3)', 
         array($clientId, 0, $clientName));
   }
}

function nfy_attachAccount($pg, $username, $clientId)
{
   if (nsc_selectValueOrFalse($pg, 'SELECT username FROM notify_user WHERE username = $1', array($username)) === false)
   {
      nsc_execute($pg, 'INSERT INTO notify_user (username, match_replies, match_mentions) VALUES ($1, false, false)', 
         array($username));
   }

   nsc_execute($pg, 'UPDATE notify_client SET username = $1 WHERE id = $2', array($username, $clientId));
}

function nfy_checkClientId($pg, $clientId, $mustHaveUsername = false)
{
   $clients = nsc_query($pg, 'SELECT id, username FROM notify_client WHERE id = $1', array($clientId));
   if (empty($clients))
      nsc_die('ERR_UNKNOWN_CLIENT_ID', 'Unknown client ID.');
   $client = $clients[0];
   if ($mustHaveUsername && (is_null($client[1]) || empty($client[1])))
      nsc_die('ERR_CLIENT_NOT_ASSOCIATED', 'Client is not associated with a Shacknews account.');
}

// Queues a notification and returns the number of clients that it was sent to.
function nfy_sendNotification($pg, $username, $subject, $body, $postId)
{
   // Don't send someone a notification about their own post.
   if ($username == $subject)
      return 0;
   
   $threadId = -1;

   if ($postId >= 0)
      $threadId = intval(nsc_selectValue($pg, 'SELECT thread_id FROM post WHERE id = $1', array($postId)));

   $rs = nsc_query($pg, 'SELECT id FROM notify_client WHERE username = $1', array(strtolower($username)));
   foreach ($rs as $row)
   {
      $clientId = strval($row[0]);
      nsc_execute($pg, 'INSERT INTO notify_client_queue (client_id, subject, body, post_id, thread_id, expiration) ' .
         'VALUES ($1, $2, $3, $4, $5, ' . "NOW() + interval '5 minutes')", 
         array($clientId, $subject, $body, $postId, $threadId));
   }
   
   return count($rs);
}

function nfy_detachAccount($pg, $clientId, $username)
{
   nsc_execute($pg, 'DELETE FROM notify_client WHERE username = $1 AND id = $2', array($username, $clientId));
}

function nsc_getRootPostsFromDay($pg, $date, $username = '')
{
   $day_of = date('Y-m-d', $date);
   $day_after = date('Y-m-d', $date + 24*60*60);
   $sql = <<<'SQL'
      SELECT 
         c.id, c.date, c.author, c.category, c.body, b.count post_count, 
         (SELECT COUNT(*) FROM post d WHERE d.thread_id = c.id AND d.author_c = $1) participant_count 
      FROM post c 
      INNER JOIN 
         (SELECT p.thread_id, COUNT(*) count 
         FROM post p 
         INNER JOIN 
            (SELECT id FROM thread WHERE date >= $2 AND date < $3) a 
            ON a.id = p.thread_id 
         GROUP BY p.thread_id 
         ORDER BY count DESC) b 
         ON c.id = b.thread_id
SQL;
   return nsc_query($pg, $sql, array($username, $day_of, $day_after));
}
