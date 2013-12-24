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
   <!--
   <?
      if (!use_chattysearchd)
      {
         # Web browsers won't display anything until they get X bytes of data first.
         for ($i = 0; $i < 512; $i++)
            echo ' ';
      }
   ?>
   -->
   <head>
      <meta http-equiv="Content-type" content="text/html; charset=utf-8"> 
      <title>WinChatty Search</title>
      <style type="text/css">
         *                { font-family: Tahoma; font-size: 12px; }
         body             { overflow-y: scroll; overflow: -moz-scrollbars-vertical;}
         a                { text-decoration: none; color: #051047}
         a:hover          { text-decoration: underline; }
         .button          { padding: 5px; padding-left: 10px; padding-right: 10px; }
         tr.header td     { font-weight: bold; }
         tr.odd           { background: #F2F2F2; }
         table            { padding: 5px; }
         tr.formHeader td { padding-left: 5px; padding-top: 5px; font-size: 11px; }
         .results tr td   { padding-left: 10px; }
         input, select    { font-weight: bold; font-size: 14px; }
         input.text       { border: 1px solid #C6C6C6; padding: 3px; }
         .text, select    { height: 26px; }
         select           { border: 1px solid #C6C6C6; padding: 2px; }
         table.formTable  { border: 1px solid #C6C6C6; background: #E8E8E8; margin: 0 auto;
                            padding-left: 10px; }
         .formTable tr td { padding-right: 5px; }
         #cmbCategory     { width: 125px; }
         table.results    { width: 100%; }
         .results tr td   { overflow-x: hidden; white-space: nowrap; height: 24px; }
         .preview         { width: 200px; }
         .author          { width: 100px; }
         .parentAuthor    { width: 0px; }
         .date            { width: 125px; }
         .story           { width: 0px; }
         .category        { width: 25px; }
         .older           { margin: 0 auto; text-align: center; padding-top: 10px; }
         td.informative   { background: url(/img/FlagBlue16.png) no-repeat 3px 3px; padding-left: 20px !important; }
         td.nws           { background: url(/img/FlagRed16.png) no-repeat 3px 3px; padding-left: 20px !important; }
         td.political     { background: url(/img/FlagYellow16.png) no-repeat 3px 3px; padding-left: 20px !important; }
         .frontPage       { margin: 0 auto; width: 700px; font-size: 14px; padding-top: 20px; }
         .frontPage a     { font-weight: bold; font-size: 14px; }
         .frontPage ul li { font-size: 14px; margin-bottom: 15px; }
         .frontPage ul    { padding-top: 10px; padding-bottom: 5px; }
         
         .jt_red          { color: #f00; }
         .jt_green        { color: #8dc63f; }
         .jt_pink         { color: #f4569a; }
         .jt_olive        { color: olive; }
         .jt_fuchsia      { color: #c0ffc0; }
         .jt_yellow       { color: #c3a900; } /* ffde00 */
         .jt_blue         { color: #44aedf; }
         .jt_lime         { color: #90bf90; } /* c0ffc0 */
         .jt_orange       { color: #f7941c; }
         .jt_italic       { font-style: italic; }
         .jt_bold         { font-weight: bold; }
         .jt_underline    { text-decoration: underline; }
         .jt_strike       { text-decoration: line-through; }
         .jt_sample       { font-size: 80%; }
         .jt_quote        { font-family: serif; font-size: 110%; }
         .jt_spoiler      { color: #383838; background-color: #383838; }
         .jt_spoiler_show { color: #f00; }
         .jt_codesmall    { font-family: monospace; }
         .jt_code         { font-family: monospace; }
         div.fpauthor_168952 .jt_wtf242, div.olauthor_168952  { color: #808080; }
         </style>
   </head>
   <body>
      <div style="text-align: center;"><a href="/nusearch" style="color: red;">A new search engine is now available! 11/28/13</a></div>
      <form action="search" method="get">
         <table class="formTable" cellspacing=0 cellpadding=0>
            <tr class="formHeader">
               <td>Search:</td>
               <td>Author:</td>
               <td>Parent Author:</td>
               <td>Category:</td>
            </tr>
            <tr>
               <td><input class="text" type="text" name="terms" value="<?=escapequotes($terms)?>"></td>
               <td><input class="text" type="text" name="author" value="<?=escapequotes($author)?>"></td>
               <td><input class="text" type="text" name="parentAuthor" value="<?=escapequotes($parentAuthor)?>"></td>
               <td>
                  <select name="category" id="cmbCategory">
                     <option></option>
                     <option>nws</option>
                     <option>informative</option>
                     <option>political</option>
                  </select>
                  <script language="JavaScript" type="text/javascript">
                     document.getElementById("cmbCategory").value = "<?=$category?>";
                  </script>
               </td>
               <td><input type="submit" value="Search" class="button" id="btnSubmit"></td>
            </tr>
         </table>
      </form>
<?
if (strlen($terms . $author . $parentAuthor . $category) > 0)
{
   $storyCount = count(glob(search_data_directory . '*.story'));
   
   if (!use_chattysearchd)
   {
      ?>
      <div style="margin: 20px auto; width: <?=$storyCount?>px; height: 20px; border: 1px solid #C60F52;" id="progressOuter">
         <div style="width: 0px; height: 100%; background: #E8D1D9;" id="progressInner"></div>
      </div>
      <?
   }

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
   
/*   if (use_chattysearchd)
      $results = SearchEngine()->memorySearch($terms, $author, $parentAuthor, $category, $offset, $perPage);
   else
      $results = SearchEngine()->search($terms, $author, $parentAuthor, $category, $limit, true);
*/

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

   if (!use_chattysearchd)
   {
      $results = array_slice($results, $offset, $perPage);
      ?>
      <script type="text/javascript">
         document.getElementById("progressOuter").style.display = "none";
      </script>
      <?
   }
   
   ?>
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
   <table border=0 cellspacing=0 cellpadding=4 class="results">
   <tr class="header">
      <td>Preview</td>
      <td>Author</td>
      <td><!--Parent&nbsp;Author--></td>
      <td>Date</td>
      <td><!--Story--></td>
   </tr>
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
   
   $odd = true;
   foreach ($results as $result)
   {
      ?>
      <tr class="<?=$odd ? 'odd' : 'even'?>">
         <td class="<?=$result['category']?>">
            <div class="preview">
            <a href="http://shacknews.com/laryn.x?id=<?=$result['id']?>#item_<?=$result['id']?>"><?=$result['preview']?></a>
            </div>
         </td>
         <td class="author"><a href="search?terms=&amp;parentAuthor=&amp;author=<?=urlencode($result['author'])?>"><?=$result['author']?></a></td>
         <td class="parentAuthor"><a href="search?terms=&amp;parentAuthor=&amp;author=<?=urlencode($result['parentAuthor'])?>"><?=$result['parentAuthor']?></a></td>
         <td class="date"><?=$result['date']?></td>
         <td class="story"><a href="http://shacknews.com/laryn.x?story=<?=$result['story_id']?>"><?=substr($result['story_name'], 0, 30)?></a></td>
      </tr>
      <?
      $odd = !$odd;
   }

   # Give the user a link to perform this search on the real Shacknews search
#   if (!empty($parentAuthor))
#      $terms .= " parentauthor:$parentAuthor";

   ?>
   </table>

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
   $minutes_ago = '&#8734;';
   $story_count = count(glob(data_directory . 'Search/*.story'));
   
   if (file_exists(data_directory . 'ReindexFinish.zzz'))
   {
      $last_reindex = filemtime(data_directory . 'ReindexFinish.zzz');
      $minutes_ago = ceil((time() - $last_reindex) / 60);
   }
   ?>
   <div class="frontpage">
      <div style="float: right; text-align: center;">
         <a href="http://winchatty.com/WinChatty-3.0k.air">
         <img src="img/App128.png" alt="" border=0><br>
         Download WinChatty 3.0k</a>
      </div>
      <!--Comments were indexed <b style="font-size: 14px;"><?=$minutes_ago?></b> minute<?=$minutes_ago == 1 ? '' : 's'?> ago. 
      There are <b style="font-size: 14px;"><?=$story_count?></b> stories indexed.<br><br>-->

<?
   $finishTime = filemtime(data_directory . 'ReindexFinish.zzz');
   $startTime = filemtime(data_directory . 'ReindexBegin.zzz');
   $currentTime = time();
   if ($finishTime < $startTime)
   {
      $duration = $currentTime - $startTime;
      $time = ($duration > 60) ? intval($duration / 60) . " minutes" : "$duration seconds";
      
      echo '<img src="ajax-loader.gif" align="top"> <b style="font-size: 14px; color: #C60F52;">Reindex in progress... <!--' . $time . '--></b><br><br>';
   }
?>
      <!--<b style="font-size: 14px;">WinChatty Search</b> supports:
      <ul>
         <li>Searching for Unicode symbols:  <a href="http://winchatty.com/search.php?terms=%E2%99%A5&amp;author=&amp;parentAuthor=&amp;category=">&hearts;</a>
         <li>Search terms with punctuation:  <a href="http://winchatty.com/?terms=C%2B%2B&amp;author=&amp;parentAuthor=&amp;category=">C++</a>
         <li>Searching for URLs:  <a href="http://winchatty.com/search.php?terms=http://www.youtube.com&amp;author=&amp;parentAuthor=&amp;category=">http://www.youtube.com</a>
         <li>Shacktags, moderation flags, and parent author listed in the search results.
         <li>Custom indexer that is not affected if the Shacknews search breaks.
      </ul>
      Only stories appearing on the first <b style="font-size: 14px;"><?=search_retention?></b> pages of the Shacknews main site are indexed.  For older posts, you will need to 
      use the regular Shacknews comment search.<br><br>
      Install the <a href="http://lmnopc.com/greasemonkey/shack2007/shack-altcommentsearch.user.js ">Greasemonkey script</a> to replace the Shacknews
      search box with WinChatty Search.  Thanks to <a href="http://shacknews.com/profile/ThomW">ThomW</a> for writing the script.<br><br>
      WinChatty Search was written and is hosted by <a href="http://shacknews.com/profile/electroly">electroly</a>.<br><br>
      -->
      Friends,<br><br>
      
      WinChatty Search is now a front-end for the official Shacknews search engine.  You can download
      the latest WinChatty desktop client by clicking the icon on the right.<br><br>
      
      Love,<br>
      electroly
   </div>
   <?
}
?>
      <script type="text/javascript">
      var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
      document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
      </script>
      <script type="text/javascript">
      try {
      var pageTracker = _gat._getTracker("UA-1952492-2");
      pageTracker._trackPageview();
      } catch(err) {}</script>
   </body>
</html>
