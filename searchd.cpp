/******************************************************************************
 *
 *                searchd - Shacknews comments search indexer
 *                              by electroly
 *
 *****************************************************************************/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>
#include <sys/time.h>
#include <sys/types.h> 
#include <sys/socket.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <string>
#include <sstream>
#include <vector>
#include <map>
#include <iostream>
#include <fstream>

#define PORT_NUMBER  6786
#define DATA_FILE "/home/joeluft/winchatty.com/data/Search/index.dat"

using namespace std;

/******************************************************************************
 *
 *   Classes
 *
 *****************************************************************************/
class Repository;
class Username;
class Story;
class Post;
class Webserver;

enum Category
{
   INFORMATIVE = 0,
   NWS         = 1,
   POLITICAL   = 2,
   OTHER       = 3
};

static const char* CategoryNames[] = 
{
   "informative",
   "nws",
   "political",
   "ontopic"
};

class Repository
{
public:
   void Populate(void);
   string Search(string terms, string author, string parent, string category, 
                 unsigned int offset, unsigned int max);

private:
   vector<Post>     posts;
   vector<Story>    stories;
   vector<Username> usernames;
   map<string, int> usernameIndexMap;
   time_t           timestamp;
   
   unsigned int IndexFromUsername(string username);
   void Clear(void);
};

class Story
{
public:
   unsigned int id;
   string       name;
   
   Story(unsigned int id, string name);
};

class Username
{
public:
   string original;
   string lowercase;
   
   Username(string original);
};

class Post
{
public:
   unsigned int id;
   unsigned int authorIndex;
   unsigned int parentAuthorIndex;
   Category     category;
   string       preview;
   time_t       date;
   string       lowercaseBody;
   unsigned int storyIndex;
   
   Post(void);
};

class Webserver
{
public:
   Webserver(unsigned int port);
   void Go(void);

private:
   unsigned int port;
   Repository repository;

   void HandleRequest(string request, int sock);
};

/******************************************************************************
 *
 *   Utility functions
 *
 *****************************************************************************/
static void die(const char* string)
{
   printf("%s\n", string);
   exit(1);
}

static void strtolower(string& str)
{
   unsigned int i;
   
   for (i = 0; i < str.size(); i++)
      str[i] = tolower(str[i]);
}

static char* my_strtok(char* string, char token, int next)
{
   char* ptr;
   
   ptr = strchr(string, token);
   if (ptr)
      *ptr = 0;
   
   if (next)
      string += strlen(string) + 1;
      
   return string;
}

static void fixAmpersands(char* string)
{
   while (strchr(string, '|'))
      *strchr(string, '|') = '&';
}

static bool postCompare(const Post* a, const Post* b)
{
   return a->id > b->id;
}

static double timestamp(void)
{
   timeval tim;
   gettimeofday(&tim, NULL);
   return tim.tv_sec + (tim.tv_usec / 1000000.0);
}

static Category categoryFromString(string category)
{
   if (category[0] == 'i')
      return INFORMATIVE;
   else if (category[0] == 'n')
      return NWS;
   else if (category[0] == 'p')
      return POLITICAL;
   else
      return OTHER;
}

static string stripTags(string str)
{
   unsigned int length = str.size();
   const char* in = str.c_str();
   char* out = (char*) malloc(length + 1);
   unsigned int i, j = 0;
   bool insideTag = false;
   
   for (i = 0; i < length; i++, in++)
   {
      char ch = *in;
      if (!insideTag && ch != '<')
         out[j++] = ch;
      
      if (ch == '<')
         insideTag = true;
      else if (ch == '>')
         insideTag = false;
   }
   
   out[j] = 0;
   
   string outStr = out;
   free(out);
   return outStr;
}

static time_t timeFromString(string str)
{
   const char* haystack = str.c_str();
   unsigned int i;
   tm t;
   
   // 01234567890123456789
   // Apr 03, 2010 9:11pm
   // Apr 03, 2010 11:38am
   
   memset(&t, 0, sizeof(tm));
   
   switch (str[0])
   {
      case 'A':
         if (str[1] == 'p')
            t.tm_mon = 3; // Apr
         else
            t.tm_mon = 7; // Aug
         break;
      case 'D':
         t.tm_mon = 11; // Dec
         break;
      case 'F':
         t.tm_mon = 1; // Feb
         break;
      case 'J':
         if (str[1] == 'a')
            t.tm_mon = 0; // Jan
         else if (str[2] == 'n')
            t.tm_mon = 5; // Jun
         else
            t.tm_mon = 6; // Jul
         break;
      case 'M':
         if (str[2] == 'r')
            t.tm_mon = 2; // Mar
         else
            t.tm_mon = 4; // May
         break;
      case 'N':
         t.tm_mon = 10; // Nov
         break;
      case 'O':
         t.tm_mon = 9; // Oct
         break;
      case 'S':
         t.tm_mon = 8; // Sep
         break;
   }
   
   t.tm_mday = atoi(haystack + 4);
   t.tm_year = atoi(haystack + 8) - 1900;
   
   if (str[17] == 'p' || str[18] == 'p')
      t.tm_hour = 11 + atoi(haystack + 13);
   else
      t.tm_hour = atoi(haystack + 13) - 1;
   
   if (str[14] == ':')
      t.tm_min = atoi(haystack + 15);
   else
      t.tm_min = atoi(haystack + 16);
   
   return mktime(&t);
}

static string stringFromTime(time_t t)
{
   char buf[128];
   memset(buf, 0, 128);
   tm* timeStruct;
   timeStruct = localtime(&t);
   strftime(buf, 127, "%b %d, %Y %I:%M%p", timeStruct);
   return string(buf);
}

/******************************************************************************
 *
 *   Method definitions
 *
 *****************************************************************************/
Username::Username(string original)
{
   this->original = original;
   lowercase = original;
   strtolower(lowercase);
}
 
Story::Story(unsigned int id, string name)
{
   this->id   = id;
   this->name = name;
}
 
Post::Post(void)
{
   id = 0;
   authorIndex = 0;
   parentAuthorIndex = 0;
   storyIndex = 0;
   category = OTHER;
}

Webserver::Webserver(unsigned int port)
{
   this->port = port;
   repository.Populate();
}

void Repository::Populate(void)
{
   unsigned int count = 0;
   struct stat statBuf;
   ifstream file;
   string buf;
   
   // Read the file modification time so we can see when it has been updated
   stat(DATA_FILE, &statBuf);
   if (timestamp == statBuf.st_mtime)
   {
      // The index has not changed since we last checked. 
      return;
   }
   else
   {
      printf("Nuking index from orbit...\n");
      Clear();
      timestamp = statBuf.st_mtime;
   }

   file.open(DATA_FILE);
   if (!file.is_open())
   {
      printf("Panic: The data file is missing!\n");
      return;
   }
   
   // Each post is a fixed number of lines in the flat text file.
   //   ---
   //   Story ID
   //   Story Name
   //   ID
   //   Author
   //   ParentAuthor
   //   Body
   //   Category
   //   Preview
   //   Date
   printf("Populating repository...\n");
   printf("...\r");
   while (!file.eof())
   {
      unsigned int storyID;
      string storyName, body, author, parentAuthor, categoryStr, date;
   
      if (count % 5000 == 0)
      {
         printf("  Read %uk posts.\r", count / 1000);
         fflush(stdout);
      }
   
      getline(file, buf);
      if (strncmp(buf.c_str(), "---", 3) != 0)
      {
         // We're done when we don't find the divider line.
         break;
      }
      
      posts.push_back(Post());
      Post& post = posts[count++];
      
      getline(file, buf);  storyID = (unsigned int) atoi(buf.c_str());
      getline(file, storyName);
      getline(file, buf);  post.id = (unsigned int) atoi(buf.c_str());
      getline(file, author);
      getline(file, parentAuthor);
      getline(file, body);
      getline(file, categoryStr);
      getline(file, post.preview);
      getline(file, date);
      
      // We precompute the stripped and lowercased body for performance.
      post.lowercaseBody = stripTags(body);
      strtolower(post.lowercaseBody);
      
      // We need a Story object to associate with this post.  If one
      // already exists, it will be the last element in the list.
      if (stories.size() == 0 || stories[stories.size() - 1].id != storyID)
         stories.push_back(Story(storyID, storyName));
      post.storyIndex = stories.size() - 1;
      
      // The author and parentAuthor fields are stored as an index into
      // the usernames array, for compactness.
      post.authorIndex = IndexFromUsername(author);
      post.parentAuthorIndex = IndexFromUsername(parentAuthor);
      
      // We store the category as an integer for compactness.
      post.category = categoryFromString(categoryStr);
      
      // We store the date as a UNIX timestamp for compactness.
      post.date = timeFromString(date);
   }
   
   printf("                         \r");
   
   file.close();

   printf("Done populating. %u stories, %u posts, %u authors.\n", stories.size(), posts.size(), usernames.size());
}

string Repository::Search(
   string terms, string author, string parent, string categoryStr, unsigned int offset, unsigned int max)
{
   vector<Post*> results;
   stringstream out(ios_base::in | ios_base::out | ios_base::app);
   string lowercaseTerms = terms;
   string lowercaseAuthor = author;
   string lowercaseParent = parent;
   bool searchTerms = terms.size() > 0;
   bool searchAuthor = author.size() > 0;
   bool searchParent = parent.size() > 0;
   bool searchCategory = categoryStr.size() > 0;
   Category category = categoryFromString(categoryStr);
   string buf;
   unsigned int i, j;

   out << "Results Begin" << endl;
   
   strtolower(lowercaseTerms);
   strtolower(lowercaseAuthor);
   strtolower(lowercaseParent);
   
   const int hardLimit = 5000;
   for (i = 0; i < posts.size() && results.size() < hardLimit; i++)
   {
      Post& post = posts[i];
      
      if ((searchTerms && post.lowercaseBody.find(lowercaseTerms) == string::npos) ||
          (searchAuthor && usernames[post.authorIndex].lowercase != lowercaseAuthor) ||
          (searchParent && usernames[post.parentAuthorIndex].lowercase != lowercaseParent) ||
          (searchCategory && post.category != category))
         continue;
      
      results.push_back(&post);
   }

   // Sort the results by post ID.
   sort(results.begin(), results.end(), postCompare);

   // Trim down to just the specified results.
   for (i = offset; i < offset + max && i < results.size(); i++)
   {
      Post& post = *results[i];
      
      out 
         << "---" << endl
         << post.id << endl
         << post.preview << endl
         << usernames[post.authorIndex].original << endl
         << usernames[post.parentAuthorIndex].original << endl
         << stringFromTime(post.date) << endl
         << CategoryNames[post.category] << endl
         << stories[post.storyIndex].id << endl
         << stories[post.storyIndex].name << endl;
   }
   
   return out.str();
}

unsigned int Repository::IndexFromUsername(string username)
{
   unsigned int index;

   if (usernameIndexMap.find(username) == usernameIndexMap.end())
   {
      // This is the first time we've seen this username.
      usernames.push_back(Username(username));
      index = usernames.size() - 1;
      usernameIndexMap[username] = index;
   }
   else
   {
      // We already have an entry for this user.
      index = usernameIndexMap[username];
   }
   
   return index;
}

void Repository::Clear(void)
{
   posts.clear();
   stories.clear();
   usernames.clear();
   usernameIndexMap.clear();
}

void Webserver::Go(void)
{
   int           sock, newSock, retVal;
   sockaddr_in   address;
   int           byteCount;
   socklen_t     sockLen = sizeof(address);
   char          buf[1024];
   
   /* Populate the index */
   repository.Populate();
   
   /* Start listening on the specified port */
   sock = socket(AF_INET, SOCK_STREAM, 0);
   if (sock < 0)
      die("Error creating the socket.");
   
   memset(&address, 0, sizeof(address));
   address.sin_family      = AF_INET;
   address.sin_addr.s_addr = INADDR_ANY;
   address.sin_port        = htons(port);

   retVal = bind(sock, (sockaddr*) &address, sizeof(address));
   if (retVal < 0)
      die("Error binding the socket.");
   
   retVal = listen(sock, 5);
   if (retVal < 0)
      die("Error listening on the socket.");

   while (1)
   {
      double startTime, endTime;
   
      /* Accept incoming connections */
      newSock = accept(sock, (sockaddr*) &address, &sockLen);
      if (newSock < 0)
         die("Error accepting a connection");

      startTime = timestamp();
         
      /* Update our in-memory cache if needed */
      repository.Populate();
      
      /* Read the request */
      byteCount = recv(newSock, buf, sizeof(buf), 0);
      string request = buf;
      HandleRequest(request, newSock);
      close(newSock);
      
      endTime = timestamp();
      printf(" Done in %0.2f seconds.\n", endTime - startTime);
   }
}

void Webserver::HandleRequest(string request, int sock)
{
   const string responseHeader = "HTTP/1.1 200 OK\r\nConnection: close\r\nContent-Type: text/plain\r\nContent-Length: ";
   string buf, action, response;

   // Generate the response

   action = "GET /search?";
   if (strncmp(request.c_str(), action.c_str(), action.size()) == 0)
   {
      char* queryString;
      char* token;
      char* parameters[6];
      unsigned int i;

      // Format: /search?terms&author&parentAuthor&category&offset&max& 
      queryString = strdup(request.c_str() + action.size());
      token = my_strtok(queryString, '&', 0);
      
      for (i = 0; i < 6 && token; i++)
      {
         parameters[i] = token;
         token = my_strtok(token, '&', 1);
         fixAmpersands(parameters[i]);
      }
      
      if (i == 6)
      {
         string terms = parameters[0];
         string author = parameters[1];
         string parent = parameters[2];
         string category = parameters[3];
         unsigned int offset = (unsigned int) atoi(parameters[4]);
         unsigned int max = (unsigned int) atoi(parameters[5]);
         
         printf("Search> T:<%s>  A:<%s>  P:<%s>  C:<%s>  O:<%s>  M:<%s>\n",
            parameters[0], parameters[1],
            parameters[2], parameters[3],
            parameters[4], parameters[5]);

         response = repository.Search(terms, author, parent, category, offset, max);         
            
         free(queryString);
      }
      else
      {
         response = "Invalid request.\n";
      }
   }
   else
   {
      response = "Unrecognized request.\n";
   }

   /* Write the response */
   char* out = (char*) malloc(responseHeader.size() + response.size() + 32);
   sprintf(out, "%s%u\r\n\r\n%s\r\n\r\n", responseHeader.c_str(), response.size(), response.c_str());
   send(sock, out, strlen(out), 0);
   free(out);
}

int main(int argc, const char** argv)
{
   unsigned int port = (argc == 2) ? atoi(argv[1]) : 6786;
   Webserver(port).Go();
   return 0;
}
