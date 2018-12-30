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

# Data directory ---------------------------------------------------------------
#  Filename = the SHA-1 hash of the user's lowercase username.  This way, we
#  won't have to deal with funny characters in usernames.
#
# File format ------------------------------------------------------------------
#  array('username'  => 'poster123',
#        'bookmarks' => array([0] => array('id'      => 1234567,
#                                          'note'    => 'User comment',
#                                          'preview' => 'Comment preview',
#                                          'author'  => 'Comment author',
#                                          'flag'    => 'stupid'),
#                                 .
#                                 . 
#                            )
#       )

function get_bookmark_data($user)
{
   $user = strtolower($user);
   $file = bookmarks_data_directory . sha1($user);
   
   # Default data
   $data = array('username' => $user, 'bookmarks' => array());

   # If the user has bookmarks, read them.
   if (file_exists($file))
      $data = unserialize(file_get_contents($file));
   
   return $data;
}

function write_bookmark_data($user, $data)
{
   $user = strtolower($user);
   $file = bookmarks_data_directory . sha1($user);
   
   # Make sure that $data isn't bogus.
   if (!is_array($data))
      die('Bogus data.');
   
   # Ensure the 'username' and 'bookmarks' fields are present
   $data['username'] = $user;
   
   if (!isset($data['bookmarks']))
      $data['bookmarks'] = array();
   
   file_put_contents($file, serialize($data));
   chmod($file, 0666);
}

function delete_bookmark($data, $id)
{
   $new_bookmarks = array();
   
   foreach ($data['bookmarks'] as $bookmark)
      if ($bookmark['id'] != $id)
         $new_bookmarks[] = $bookmark;
   
   $data['bookmarks'] = $new_bookmarks;
   return $data;
}

function add_bookmark($data, $story, $id, $note, $preview, $author, $flag)
{
   # Delete an existing bookmark, if one exists.
   $data = delete_bookmark($data, $id);
   
   $preview = html_entity_decode(strip_tags($preview));
         
   if (strlen($preview) > 50)
      $preview = substr($preview, 0, 47) . '...';
         
   $data['bookmarks'][] = array(
      'id'       => $id, 
      'story_id' => $story,
      'note'     => $note,
      'preview'  => $preview,
      'author'   => $author,
      'flag'     => $flag);
   
   return $data;
}

