<?
require_once '../../include/Global.php';

class ProfileService
{
   public function getProfile($username)
   {
      return ProfileParser()->getProfile($username);
   }
}
