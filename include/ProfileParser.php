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