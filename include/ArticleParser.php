<?
class ArticleParser extends Parser
{
   public function getArticle($storyID)
   {
      $storyID = intval($storyID);
      $this->init($this->download("http://www.shacknews.com/article/$storyID"));
      return $this->parseArticle($this);
   }

   public function parseArticle(&$p)
   {
      $story = array(
         'preview'       => false,
         'name'          => false,
         'body'          => false,
         'date'          => false,
         'comment_count' => false,
         'id'            => false,
         'thread_id'     => false);
   
      $p->seek(1, '<div id="main">');

      $story['name'] = $p->clip(array('<h1>', '>'), '<');
      $story['date'] = nsc_v1_date(strtotime($p->clip(array('<span class="vitals">', 'by ', ', ', ' '), '</span>')));
      $story['body'] = '<div>' . $p->clip(array('<div id="article_body">', '>'), '<script type="text/javascript" src="/js/jquery.ba-postmessage.min.js"></script>');
      $story['preview'] = nsc_previewFromBody($story['body']);
      $story['comment_count'] = 0;

      $p->seek(1, '<div class="threads">');
      $story['thread_id'] = intval($p->clip(array('<div id="root_', '_'), '"'));

      $p->seek(1, '<input type="hidden" name="content_type_id" id="content_type_id" value="2" />');
      $story['id'] = intval($p->clip(array('<input type="hidden" value="', 'value', '"'), '"'));
      
      return $story;
   }
}

function ArticleParser()
{
   return new ArticleParser();
}