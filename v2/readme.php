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
$html = str_replace('border:solid #781049 4.5pt;', 'border-left: 8pt #781049 solid; border-radius: 10pt; margin-top: 36pt; margin-bottom: 12pt; margin-left: -12pt; margin-right:-12pt;', $html);
$html = str_replace('border:solid #B3186D 4.5pt;', 'border-left: 8pt #B3186D solid; border-radius: 10pt; margin-top: 36pt; margin-bottom: 12pt; margin-left: -12pt; margin-right:-12pt;', $html);
$html = str_replace('<p class=MsoNormal>&nbsp;</p>', '', $html);

$repl = '<style>'
   . 'body { margin: 20 auto; min-width: 800px; width: 800px; }' . "\n"
   . 'a, span.MsoHyperlink, span.MsoHyperlinkFollowed { text-decoration: none !important; }' . "\n"
   . 'a:hover { text-decoration: underline !important; }' . "\n"
   . 'a:visited { color: rgb(227, 45, 145) !important; }' . "\n"
   . 'h1, h2, p.MsoTocHeading { margin-top: 6pt !important; }' . "\n"
   . '</style></head>';
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

echo $tidy;
