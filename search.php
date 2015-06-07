<?
require_once 'include/Global.php';

$terms = isset($_GET['terms']) ? $_GET['terms'] : '';
$author = isset($_GET['author']) ? $_GET['author'] : '';
$parentAuthor = isset($_GET['parentAuthor']) ? $_GET['parentAuthor'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

function escapequotes($str)
{
   while (strstr($str, '"') !== false)
      $str = str_replace('"', '&quot;', $str);
   return $str;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"> 
<html>
   <head>
      <meta charset="utf-8"> 
      <title>WinChatty Old Search</title>
      <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,400italic,700italic" rel="stylesheet" type="text/css">
      <link href="nusearch_style.css" rel="stylesheet" type="text/css">
      <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
   </head>
   <body>
      <div id="formContainer" style="position: fixed; width: 100%;">
         <a href="/nusearch"><img src="/img/App128.png" id="logo" alt="WinChatty NuSearch"></a>
         <div style="position: absolute; top: 0px; left: 60px; font-size: 12px;">
            <a href="/nusearch" class="top_link">NuSearch</a> &bull; 
            <a href="/search" class="top_link"><b>Old Search</b></a> &bull; 
            <a href="/archive" class="top_link">Archive</a>
         </div>
         <form action="/search" method="get">
            <table id="formTable">
               <tr id="formHeader">
                  <td>Search:</td>
                  <td>Author:</td>
                  <td>Parent author:</td>
                  <td>Moderation flag:</td>
                  <td></td>
               </tr>
               <tr>
                  <td><input class="text" autofocus type="text" name="terms" value="<?=isset($_GET['terms']) ? htmlspecialchars($_GET['terms']) : ''?>"></td>
                  <td><input class="text" type="text" name="author" value="<?=isset($_GET['author']) ? htmlspecialchars($_GET['author']) : ''?>"></td>
                  <td><input class="text" type="text" name="parentAuthor" value="<?=isset($_GET['parentAuthor']) ? htmlspecialchars($_GET['parentAuthor']) : ''?>"></td>
                  <td>
                     <select name="category" id="category">
                        <option></option>
                        <option>nws</option>
                        <option>informative</option>
                        <option>political</option>
                     </select>
                     <script>
                        $("#category").val("<?=htmlspecialchars($category)?>");
                     </script>
                  </td>
                  <td><input type="submit" value="Search" class="button" id="btnSubmit"></td>
               </tr>
            </table>
         </form>
      </div>


<?
if (strlen($terms . $author . $parentAuthor . $category) > 0)
{
   $storyCount = count(glob(search_data_directory . '*.story'));
   
   flush();

   $page = (isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1);
   $perPage = 30;
   $offset = $page * $perPage;
   $limit = ($page + 1) * $perPage;
   
   # -1 is used to jump to the last page.
   if ($page == -1)
   {
      $offset = 0;
      $limit = 0;
   }
   
   $startingTime = microtime(true);

   $realPage = $page * 2 - 1;
   $results1 = array();
   $results2 = array();
   
   try
   {
      $results1 = SearchParser()->search($terms, $author, $parentAuthor, $category, $realPage);
   }
   catch (Exception $ex)
   {
      $results1 = array();
   }
   
   try
   {
      $results2 = SearchParser()->search($terms, $author, $parentAuthor, $category, $realPage + 1);
   }
   catch (Exception $ex)
   {
      $results2 = array();
   }

   $results = array_merge($results1, $results2);

   $totalResults = 0;
   if (!empty($results))
      $totalResults = $results[0]['totalResults'];
      
   $totalPages = ceil($totalResults / $perPage);

   $endingTime = microtime(true);
   $duration = $endingTime - $startingTime;
   
   if ($page == -1)
   {
      $page = $totalPages - 1;
      $offset = $page * $perPage;
      $limit = ($page + 1) * $perPage;
   }

   ?>
   <div style="height: 70px;"></div>
   <div style="font-size: 16px; padding: 10px; float: left;">
   Found <?=number_format($totalResults)?> total results.
   </div>
   <div style="text-align: right; float: right;">
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="1">
         <input class="button" type="submit" value="|&lt;" <?=$page == 1 ? 'disabled' : ''?>>
      </form>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$page > 1 ? $page - 1 : 1?>">
         <input class="button" type="submit" value="&lt; Back" <?=$page == 1 ? 'disabled' : ''?>>
      </form>
      <span style="font-size: 16px; padding: 10px;">Page <?=$page?></span>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$page + 1?>">
         <input class="button" type="submit" value="Next &gt;" <?=count($results) < $perPage ? 'disabled' : ''?>>
      </form>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$totalPages?>">
         <input class="button" type="submit" value="&gt;|" <?=count($results) < $perPage ? 'disabled' : ''?>>
      </form>
   </div>   
   <div id="results">
      <table style="margin: 0 auto; margin-bottom: 50px;"><tbody>
   <?
   
   if (empty($results))
   {
      ?>
      <tr>
         <td>
         No matching comments were found.
         </td>
      </tr>
      <?
   }
   
   foreach ($results as $result)
   {
      ?>
      <tr>
         <td class="body"><a class="resultLink" href="http://www.shacknews.com/chatty?id=<?=$result['id']?>#item_<?=$result['id']?>"><?=$result['preview']?></a></td>
         <td class="author"><a class="resultLink" href="search?terms=&amp;parentAuthor=&amp;author=<?=urlencode($result['author'])?>"><?=$result['author']?></a></td>
         <td class="date"><?=$result['date']?></td>
      </tr>
      <?
   }

   # Give the user a link to perform this search on the real Shacknews search
#   if (!empty($parentAuthor))
#      $terms .= " parentauthor:$parentAuthor";

   ?>
   </table></div>

   <div style="text-align: right;">
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="1">
         <input class="button" type="submit" value="|&lt;" <?=$page == 1 ? 'disabled' : ''?>>
      </form>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$page > 1 ? $page - 1 : 1?>">
         <input class="button" type="submit" value="&lt; Back" <?=$page == 1 ? 'disabled' : ''?>>
      </form>
      <span style="font-size: 16px; padding: 10px;">Page <?=$page?></span>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$page + 1?>">
         <input class="button" type="submit" value="Next &gt;" <?=count($results) < $perPage ? 'disabled' : ''?>>
      </form>
      <form method="get" action="search" style="display: inline;">
         <input type="hidden" name="terms" value="<?=escapequotes($terms)?>">
         <input type="hidden" name="author" value="<?=escapequotes($author)?>">
         <input type="hidden" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>">
         <input type="hidden" name="category" value="<?=escapequotes($category)?>">
         <input type="hidden" name="page" value="<?=$totalPages?>">
         <input class="button" type="submit" value="&gt;|" <?=count($results) < $perPage ? 'disabled' : ''?>>
      </form>
   </div>   
   
   
   <!--<div class="older">
      <form method="get" action="http://www.shacknews.com/search.x">
         <input type="hidden" name="type" value="comments">
         <input type="hidden" name="terms" value="<?=htmlentities($terms)?>">
         <input type="hidden" name="cs_user" value="<?=htmlentities($author)?>">
         <input class="button" type="submit" value="See older posts">
      </form>
      <small style="color: #BBBBBB">Query execution took <?=floor($duration * 100) / 100?> seconds.</small>
   </div>-->
   <?
}
else
{
   ?>
   <div class="frontpage">
   </div>
   <?
}
?>
   </body>
</html>
