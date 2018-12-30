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

class Parser
{
   protected $cursors;
   protected $html;
   protected $len;
   
   protected function init($html)
   {
      $this->cursors = array(null, 0, 0);
      $this->html    = $html;
      $this->len     = strlen($html);
   }
   
   protected function peek($which_cursor, $keyword)
   {
      return strpos($this->html, $keyword, $this->cursors[$which_cursor]);
   }
   
   protected function clip($before_keywords, $after_keyword)
   {
      $this->seek(1, $before_keywords);
      $this->incr(1);
      $this->seek(2, $after_keyword);
      return $this->read();
   }
   
   protected function incr($which_cursor)
   {
      $this->cursors[$which_cursor]++;
      
      if ($this->cursors[$which_cursor] >= $this->len)
         throw new Exception('Unexpected end of HTML data.');
   }

   protected function seek($which_cursor, $keywords)
   {
      # If $keyword is an array, then seek to each one in sequence.
      if (is_array($keywords))
      {
         foreach ($keywords as $keyword)
            $this->seek($which_cursor, $keyword);
      }
      else
      {
         $i = $this->cursors[1];
         $j = strpos($this->html, $keywords, $i);
         if ($j === false)
            throw new Exception("Did not find '$keywords' starting at index '$i'");
         else
            $this->cursors[$which_cursor] = $j;
      }
   }
   
   protected function read()
   {
      $c1 = $this->cursors[1];
      $c2 = $this->cursors[2];
      return substr($this->html, $c1, $c2 - $c1);
   }

   protected function download($url, $fast = false)
   {
      # This function will reuse a login over and over again until Shacknews kicks us off.
      # This cannot be used for user-specific pages, like Shackmessages.
      $cookiejar = '/tmp/Login.cookie';
      
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, true);
      curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
      curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiejar);
      curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiejar);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty API');
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: libcurl'));
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);

      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, false);
      $html = curl_exec($curl);
      
      $username = WINCHATTY_USERNAME;
      $password = WINCHATTY_PASSWORD;
      
      # We'll keep using the same session until Shacknews kicks us off.
      if ($fast == false && 
          #strpos($html, '<li class="user light"><a href="/user/' . $username . '/posts">' . $username . '</a></li>') === false &&
          strpos($html, '<a id="user_posts" href="/user/' . $username . '/posts">') === false)
      {

#$log = fopen('/tmp/winchatty.log', 'a');
#fwrite($log, date("Y-m-d H:i:s") . " - Re-logging into latestchatty\n");
#fclose($log);


         # Need to log in, first.
         $fields = 'get_fields%5B%5D=result&user-identifier=' . $username . '&supplied-pass=' . $password . '&remember-login=1';
         
         
         curl_setopt($curl, CURLOPT_URL, 'https://www.shacknews.com/account/signin');
         curl_setopt($curl, CURLOPT_POST, true);
         curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
         
         curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
         $response = curl_exec($curl);
         curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: libcurl'));
         
         if (strpos($response, '{"result":{"valid":"true"') !== false)
         {
            # Successfully logged in.  Get the data again.
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, null);
            $html = curl_exec($curl);
            curl_close($curl);
            return str_replace("\0", "", $html);
         }
         else
         {
            curl_close($curl);
            throw new Exception('Unable to log into the shared user account.');
         }
      }
      
      curl_close($curl);
      return str_replace("\0", "", $html);
   }
   
   protected function userDownload($url, $username, $password, $postArgs = null)
   {
      # This function does not reuse logins.  It will log in using $username and $password
      # on every call.
      $cookiejar = tempnam(sys_get_temp_dir(), 'WinChatty.CookieJar.');
      chmod($cookiejar, 0666);
      
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
      curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiejar);
      curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiejar);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty API');
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);

      # Log in first.
      $fields = 'get_fields%5B%5D=result&user-identifier=' . urlencode($username) . '&supplied-pass=' . urlencode($password);
      
      curl_setopt($curl, CURLOPT_URL, 'https://www.shacknews.com/account/signin');
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);

      curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
      $response = curl_exec($curl);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: libcurl'));

      if (strpos($response, '{"result":{"valid":"true"') === false)
      {
#$log = fopen('/tmp/winchatty.log', 'a');
#fwrite($log, date("Y-m-d H:i:s") . " - Failed to log in for: $username \n");
#fclose($log);

         curl_close($curl);
         unlink($cookiejar);
         throw new Exception('Unable to log into user account.');
      }
      
      # Download the requested page.
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, false);
      curl_setopt($curl, CURLOPT_POSTFIELDS, null);

      if ($postArgs != null)
      {
         curl_setopt($curl, CURLOPT_POST, 1);
         curl_setopt($curl, CURLOPT_POSTFIELDS, $postArgs);
      }
      
      $html = curl_exec($curl);

      curl_close($curl);
      unlink($cookiejar);
      
      return str_replace("\0", "", $html);
   }
}
