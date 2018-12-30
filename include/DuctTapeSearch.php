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
      'text' => strval(html_entity_decode(strip_tags($body))),
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

