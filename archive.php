<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

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

$model = array();
$day = isset($_GET['day']) ? strtotime(trim($_GET['day'])) : '';
if (empty($day) || $day < strtotime('1990-01-01'))
   $day = time();
$pg = nsc_connectToDatabase();
$rs = nsc_getRootPostsFromDay($pg, $day);

?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8"> 
      <title>WinChatty Archive</title>
      <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,400italic,700italic" rel="stylesheet" type="text/css">
      <link href="nusearch_style.css" rel="stylesheet" type="text/css">
      <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
      <script src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
      <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
   </head>
   <body>
      <div id="formContainer" style="position: fixed; width: 100%;">
         <a href="/nusearch"><img src="/img/App128.png" id="logo" alt="WinChatty NuSearch"></a>
         <div style="position: absolute; top: 0px; left: 60px; font-size: 12px;">
            <a href="/nusearch" class="top_link">NuSearch</a> &bull; 
            <a href="/search" class="top_link">Old Search</a> &bull; 
            <a href="/archive" class="top_link"><b>Archive</b></a>
         </div>
         <form action="/archive" method="get">
            <table id="formTable">
               <tr id="formHeader">
                  <td>Day:</td>
                  <td></td>
               </tr>
               <tr>
                  <td><input style="width: 100px;" id="day_txt" class="text" type="text" name="day" value="<?=date('m/d/Y',$day)?>"></td>
                  <td><input type="submit" value="View" class="button" id="btnSubmit"></td>
               </tr>
            </table>
         </form>
      </div>
      <div style="height: 55px;"></div>
      <br>
      <center>
         <div id="results">
            <table style="margin: 0 auto; margin-bottom: 50px;"><tbody>
               <? foreach ($rs as $row) { /* 0=id, 1=date, 2=author, 3=category, 4=body, 5=post_count, 6=participant_count */?>
               <tr>
                  <td class="body"><?=categoryToHTML($row[3])?><a title="<?=htmlspecialchars(strip_tags(str_replace('<br />', "\n", $row[4])))?>" class="resultLink" target="_blank" href="http://www.shacknews.com/chatty?id=<?=$row[0]?>#item_<?=$row[0]?>"><?=previewFromBody($row[4])?></a></td>
                  <td class="author"><a class="resultLink" href="/nusearch?a=<?=urlencode($row[2])?>"><?=$row[2]?></a></td>
                  <td class="date"><nobr><?=date('M d, Y h:i A', strtotime($row[1]))?></nobr></td>
                  <td class="postcount"><nobr><?=$row[5]-1?> repl<?=($row[5]-1) == 1 ? 'y' : 'ies'?></nobr></td>
               </tr>
               <? } ?>
            </tbody></table>
         </div>
      </center>
      <script>
      $(document).ready(function() {
         $('#day_txt').datepicker({ autoSize: true });
      });
      </script>
   </body>
</html>
