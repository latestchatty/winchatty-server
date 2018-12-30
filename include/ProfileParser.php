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

class ProfileParser extends Parser
{
   public function getProfile($username)
   {
      $json = file_get_contents('http://chattyprofil.es/api/profile/' . urlencode($username));
      $input = json_decode($json, true);
      
      $fields = array(
         'wii' => 'wii',
         'sex' => 'sex',
         'playstation_network' => 'psn',
         'aim' => 'aim',
         'msn' => 'msn',
         'location' => 'location',
         'icq' => 'icq',
         'homepage' => 'homepage',
         'yahoo' => 'yahoo',
         'xfire' => 'xfire',
         'xboxlive' => 'xboxlive',
         'join_date' => 'registered',
         'gtalk' => 'gtalk',
         'steam' => 'steam'
      );
      
      $output = array(
         'info' => array(),
         'body' => ''
      );
      
      foreach ($fields as $inField => $outField)
      {
         $output['info'][$outField] = $input['profile'][$inField];
      }
      
      $output['body'] = $input['profile']['user_bio'];
      
      $birthDate = strtotime($input['profile']['birthdate']);
      if (!empty($birthDate))
      {
         $currentDate = strtotime('now');
         $ageInSeconds = $currentDate - $birthDate;
         $ageInYears = floor($ageInSeconds / 60 / 60 / 24 / 365); 
         $output['info']['age'] = $ageInYears;
      }
      
      return $output;
   }
}

function ProfileParser()
{
   return new ProfileParser();
}