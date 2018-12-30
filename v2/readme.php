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

$html = file_get_contents('WinChatty v2 API.html');
$html = iconv('UTF-16LE', 'UTF-8', $html);
if ($html === false)
   die("Could not convert UTF-16LE to UTF-8.");

$html = str_replace('; charset=unicode', '', $html);
$html = str_replace('<head>', '<head><meta charset="utf-8">', $html);
$html = str_replace('border:solid #781049 4.5pt;', 'border-left: 8pt #781049 solid; margin-top: 36pt; margin-bottom: 12pt; margin-left: -12pt; margin-right:-12pt;', $html);
$html = str_replace('border:solid #B3186D 4.5pt;', 'border-left: 8pt #B3186D solid; margin-top: 36pt; margin-bottom: 12pt; margin-left: -12pt; margin-right:-12pt;', $html);
$html = str_replace('<p class=MsoNormal>&nbsp;</p>', '', $html);
$html = str_replace('·', '&bull;', $html);
$html = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $html);

$startStr = '<p class=MsoToc1>';
$endStr = "<div style='border-left: 8pt #B3186D solid; margin-top:";
$startPos = strpos($html, $startStr);
$endPos = strpos($html, $endStr, $startPos);

if ($startPos === false)
   die($html);
if ($endPos === false)
   die($html);

$html = substr_replace($html, '</div>&nbsp;</div>', $endPos, 0);
$html = substr_replace($html, '<div id="tree"><div style="padding: 10px;">', $startPos, 0);
$html = str_replace('<p class=MsoTocHeading>Contents</p>', '', $html);

$repl = '<style>'
   . '.MsoToc2 { line-height: 100% !important; }' . "\n"
   . 'body { margin: 20px; margin-left: 290px; min-width: 800px; width: 800px; }' . "\n"
   . 'a, span.MsoHyperlink, span.MsoHyperlinkFollowed { text-decoration: none !important; }' . "\n"
   . 'a:hover { text-decoration: underline !important; }' . "\n"
   . 'a:visited { color: rgb(227, 45, 145) !important; }' . "\n"
   . 'h1, h2, p.MsoTocHeading { margin-top: 10pt !important; }' . "\n"
   . '#tree { overflow-y: auto; position: fixed; top: 0px; left: 0px; border-right: 1px solid gray; width: 260px; height: 100%; background: #F9F9F9; }' . "\n"
   . '#tree .MsoToc1 a, #tree .MsoToc2 a { color: black !important; }' . "\n"
   . '</style>'
   . '<link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,400italic,700italic" rel="stylesheet" type="text/css">'
   . '<link href="//fonts.googleapis.com/css?family=Source+Code+Pro:400" rel="stylesheet" type="text/css">'
   . '</head>';
$html = str_replace('</head>', $repl, $html);

$config = array(
   'indent'         => 2,
   'output-html'    => true,
   'wrap'           => 120,
   'clean'          => true,
   'join-classes'   => true,
   'join-styles'    => true);

$tidy = new tidy;
$tidy->parseString($html, $config, 'utf8');
$tidy->cleanRepair();

$html = strval($tidy);
$html = str_replace("<div class='c6'></div>", '', $html); # This will break easily

echo $html;
