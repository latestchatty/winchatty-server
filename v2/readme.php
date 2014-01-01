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
$endStr = "none'>1</span></a></p>";
$startPos = strpos($html, $startStr);
$endPos = strrpos($html, $endStr);

if ($startPos === false)
   die($html);
if ($endPos === false)
   die($html);

$html = substr_replace($html, '</div>&nbsp;</div>', $endPos + strlen($endStr), 0);
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
