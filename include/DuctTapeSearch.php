<?
# WinChatty Server
# Copyright (C) 2015 Brian Luft
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

function dts_httpPost($url, $args) 
{
   $curl = curl_init();
   $query = http_build_query($args);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
   curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty Server');
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
   $raw_response = curl_exec($curl);
   $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
   if ($http_status == '200')
   {
      if (is_null($raw_response) || empty($raw_response))
         throw new Exception("Empty response.\n");
      else 
         return $raw_response;
   }
   else
   {
      throw new Exception($raw_response);
   }
}

/* Category_ANY = 0x00,
   Category_ON_TOPIC = 0x01,
   Category_NOT_WORK_SAFE = 0x02,
   Category_STUPID = 0x03,
   Category_POLITICAL_RELIGIOUS = 0x04,
   Category_TANGENT = 0x05,
   Category_INFORMATIVE = 0x06,
   Category_NUKED = 0x07 */
function dts_index($id, $body, $author, $parentAuthor, $categoryNum)
{
   $result = dts_httpPost("http://127.0.0.1:8081/index", array(
      'id' => intval($id),
      'text' => strval($body),
      'a' => strval($author),
      'pa' => strval($parentAuthor),
      'c' => intval($categoryNum)
   ));

   if (trim($result) != 'ok')
      throw new Exception("Unexpected result: $result");
}

function dts_search($terms, $author, $parentAuthor, $categoryNum, $skip, $take, $desc) # returns id[]
{
   $result = dts_httpPost("http://127.0.0.1:8081/search", array(
      'q' => strval($terms),
      'a' => strval($author),
      'pa' => strval($parentAuthor),
      'skip' => intval($skip),
      'take' => intval($take),
      'desc' => $desc ? 1 : 0
   ));

   $json = json_decode($result, true);
   if ($json === false || !is_array($json))
      throw new Exception("Unexpected result: $result");

   return $json;
}

