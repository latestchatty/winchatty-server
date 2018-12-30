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

define('data_directory',             '/mnt/websites/winchatty.com/data/');
define('bookmarks_data_directory',   '/mnt/websites/winchatty.com/data/Bookmarks/');
define('search_data_directory',      '/mnt/websites/winchatty.com/data/Search/');

# For WinChatty v2 API
define('V2_CONNECTION_STRING', 'hostaddr=127.0.0.1 port=6432 dbname=chatty user=nusearch password=nusearch');
define('V2_DATA_PATH', '/mnt/ssd/ChattyIndex/'); # must have trailing slash. must already exist.
define('V2_ADMIN_USERNAME', 'electroly'); # this person is allowed to use /v2/broadcastServerMessage
define('V2_INDEXER_SCRIPT', 'html_scraping_indexer.php'); # script must be in the indexer-server/ folder
define('V2_SEARCH_ENGINE', 'pgsql-search'); # 'duct-tape' or 'pgsql-search'
