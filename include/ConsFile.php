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

define('CF_BYTES_PER_LINE', 10);
define('CF_DATA_BYTES', 4);
define('CF_NEXT_BYTES', 5);

# List node range requirements:
#  data: about 50 million
#  next: about 2 billion

# Encoding as base223:
#  "AAAABBBBB\n"
#  10 bytes per line
#  data: 78 million
#  next: 7 billion

function cf_intToBytes($num, $width)
{
   # Encodes using "Base 223"
   $index = 0;
   $str = '';
   while ($num != 0)
   {
      $str = chr(($num % 223) + 33) . $str;
      $num = (int) ($num / 223);
   }
   if (strlen($str) > $width)
      throw new Exception("$num is too large.");
   while (strlen($str) < $width)
      $str = '!' . $str;
   return $str;
}

function cf_bytesToInt($str)
{
   # Decodes using "Base 223"
   $num = 0;
   for ($i = 0; $i < strlen($str); $i++)
   {
      $num *= 223;
      $num += ord($str[$i]) - 33;
   }
   return $num;
}

function cf_create($filePath) # void
{
   $fp = fopen($filePath, 'w+');
   if (!$fp)
      throw new Exception('cf_create: fopen failed.');
   fwrite($fp, cf_intToBytes(0, CF_DATA_BYTES) . cf_intToBytes(1, CF_NEXT_BYTES) . "\n");
   return $fp;
}

function cf_openRead($filePath) # fp
{
   $fp = fopen($filePath, 'r');
   if (!$fp)
      throw new Exception('cf_openRead: fopen failed.');
   return $fp;
}

function cf_openWrite($filePath) # fp
{
   $fp = fopen($filePath, 'r+');
   if (!$fp)
      throw new Exception('cf_openWrite: fopen failed.');
   return $fp;
}

function cf_close($fp) # void
{
   fclose($fp);
}

function cf_cons($fp, $data, $next) # pointer
{
   $newPtr = cf_getNext($fp, 0);
   cf_set($fp, $newPtr, $data, $next);
   cf_set($fp, 0, 0, $newPtr + 1);
   return $newPtr;
}

function cf_get($fp, $ptr) # array(1234, 2345) data, next
{
   if (fseek($fp, $ptr * CF_BYTES_PER_LINE) === -1)
      throw new Exception('cf_get: fseek failed.');

   $line = fread($fp, CF_BYTES_PER_LINE);
   if (empty($line))
      throw new Exception('cf_get: Line is empty.');

   $data = cf_bytesToInt(substr($line, 0, CF_DATA_BYTES));
   $next = cf_bytesToInt(substr($line, CF_DATA_BYTES, CF_NEXT_BYTES));
   return array($data, $next);
}

function cf_getData($fp, $ptr)
{
   $cell = cf_get($fp, $ptr);
   return $cell[0];
}

function cf_getNext($fp, $ptr)
{
   $cell = cf_get($fp, $ptr);
   return $cell[1];
}

function cf_set($fp, $ptr, $data, $next)
{
   if (fseek($fp, $ptr * CF_BYTES_PER_LINE) === -1)
      throw new Exception('cf_set: fseek failed.');

   $line = cf_intToBytes($data, CF_DATA_BYTES) . cf_intToBytes($next, CF_NEXT_BYTES) . "\n";
   if (fwrite($fp, $line) === false)
      throw new Exception('cf_set: fwrite failed.');
}
