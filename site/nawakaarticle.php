<?php

// Initialize Database
$db = JFactory::getDbo();

$url = "https://deliver.kenticocloud.com/bf245c4e-805c-0006-f04d-81be5711929e/items";

// Get API contents
$ch = curl_init();
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result= json_decode(curl_exec($ch), true)["items"];
curl_close($ch);

function get_string_between($string, $start, $end){
        $string = " ".$string;
       $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
         $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
}

foreach($result as $item) {
  $article_id = $item["system"]["id"];
  $title = $item["elements"]["artikeltitel"]["value"];
  $category_name = $item["elements"]["welke_categorie_"]["value"][0]["codename"];
  print($category_name);
  $title = preg_replace('`\[[^\]]*\]`','', $title);

  $lead = $item["elements"]["lead"]["value"];
  $text = $item["elements"]["tekst"]["value"];
  $youtube = $item["elements"]["youtube_embed_url"]["value"];
  // $flickr = $item["elements"]["flickr_embed_id"]["value"];
  $embed = $item["elements"]["embed_url"]["value"];

  echo $embed;

  $photographers = array();
  foreach($item["elements"]["welke_fotograaf_heeft_de_foto_s_gemaakt_"]["value"] as $photographer){
    array_push($photographers, $photographer["name"]);
  }
  $photographers = implode(", ", $photographers);


  $authors = array();
  foreach($item["elements"]["wie_is_zijn_de_auteurs_van_dit_stukje_"]["value"] as $author){
    array_push($authors, $author["name"]);
  }
  $authors = implode(", ", $authors);


 file_put_contents(JPATH_BASE.'/images/articles/'.$photo["name"],
          fopen($photo["url"], 'r'));

  // Check if other photos have been specified and if they exist.
  $photos = array();
  foreach ($item["elements"]["foto_s"]["value"] as $photo) {
    if (!empty($photo)) {
      array_push($photos, $photo["name"]);
     
      file_put_contents(JPATH_BASE.'/images/articles/'.$photo["name"],
          fopen($photo["url"], 'r'));
     
    }
  }

  $lead_url = "";
  if (!empty($item["elements"]["lead_foto"]["value"][0])) {
    $lead_url = JUri::base().'images/articles/'.$item["elements"]["lead_foto"]["value"][0]["name"];
  }
  $full_lead_url = $lead_url;

  if (!empty($embed)) {
    $full_lead_url = $embed;
  }

  if (!empty($youtube)) {
    $full_lead_url = $youtube;
  }

  $extra_html = "";
  if ($photos) {
    $extra_html .= '<link href="/components/com_nawakaarticle/flickity.min.css" rel="stylesheet" type="text/css" media="screen" />';
    $extra_html .= '<script src="/components/com_nawakaarticle/flickity.pkgd.min.js"></script>';
    $extra_html .= '<div class="flickity" data-flickity=\'{ "imagesLoaded": true, "percentPosition": false }\'>';
    foreach ($photos as $photo) {
      $extra_html .= '<img src="'.JUri::base().'images/articles/'.$photo.'" />';
    }
    $extra_html .= '</div>';
  }

//   if ($flickr) {
//     $extra_html .= '<iframe id="flickr-iframe" style="position: relative; top: 0; left: 0; width: 100%; height: 800px; max-height: 800px;" src="https://flickrembed.com/cms_embed.php?source=flickr&layout=responsive&'.$flickr.'&sort=0&by=album&theme=tiles_justified&scale=fit&limit=300&skin=default&autoplay=true" scrolling="yes" frameborder="0" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe><script type="text/javascript">function showpics(){var a=$("#box").val();$.getJSON("http://api.flickr.com/services/feeds/photos_public.gne?tags="+a+"&tagmode=any&format=json&jsoncallback=?",function(a){$("#images").hide().html(a).fadeIn("fast"),$.each(a.items,function(a,e){$("<img/>").attr("src",e.media.m).appendTo("#images")})})}</script>';
//   }

  // Check if article already exists
  $query = $db->getQuery(true);
  $query->select($db->quoteName(array('id')));
  $query->from($db->quotename('#__content'));
  $query->where($db->quoteName('metadata'). ' LIKE '.$db->quote('%'.$article_id.'%'));
  $db->setQuery($query);
  $results = $db->loadObjectList();

  if (!empty($results) && count($results) > 0) {
    // Article already exist, update article

    if (version_compare(JVERSION, '3.0', 'lt')) {
        JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
    }
    $article = & JTable::getInstance('content');
    $id = $results[0]->id;
    $article->load(array('id' => $results[0]->id));
    $article->title            = $title;
    $article->introtext        = $lead;
    $article->fulltext         = $text . $extra_html;
    $article->created_by_alias = $authors;
    $cat_id = 19;

    switch (true){
       case stristr($category_name,'stuurlui'):
          $cat_id = 20;
          break;
       case stristr($category_name,'vis'):
          $cat_id = 21;
          break;
       case stristr($category_name,'reilen'):
          $cat_id = 27;
          break;
       case stristr($category_name,'onderste'):
          $cat_id = 28;
          break;
       case stristr($category_name,'zee'):
          $cat_id = 29;
          break;
       case stristr($category_name,'kanawa'):
          $cat_id = 30;
          break;
       case stristr($category_name,'english'):
          $cat_id = 25;
          break;
    }
    $article->catid            = $cat_id;
    $article->images          = '{"image_intro":"'.$lead_url.'","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"'.$full_lead_url.'", "float_fulltext":"none","image_fulltext_alt":"","image_fulltext_caption":""}';
    $article->access           = 1;
    $article->metadata         = '{"page_title":"","rights":"'.$photographers.'", "author":"'.$authors.'","robots":"", "xreference": "'.$article_id.'"}';
    $article->language         = '*';

    // Check to make sure our data is valid, raise notice if it's not.

    // Check the data.
    if (!$article->check())
    {

    }

    // Store the data.
    if (!$article->store())
    {

    }
  } else {
    // Create new Article
    if (version_compare(JVERSION, '3.0', 'lt')) {
        JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
    }
    $article = JTable::getInstance('content');
    $article->title            = $title;
    $article->alias            = JFilterOutput::stringURLSafe($title);
    $article->introtext        = $lead;
    $article->fulltext         = $text . $extra_html;
    $cat_id = 19;

    switch (true){
       case stristr($category_name,'stuurlui'):
          $cat_id = 20;
          break;
       case stristr($category_name,'vis'):
          $cat_id = 21;
          break;
       case stristr($category_name,'reilen'):
          $cat_id = 27;
          break;
       case stristr($category_name,'onderste'):
          $cat_id = 28;
          break;
       case stristr($category_name,'zee'):
          $cat_id = 29;
          break;
       case stristr($category_name,'kanawa'):
          $cat_id = 30;
          break;
       case stristr($category_name,'english'):
          $cat_id = 25;
          break;
    }
    $article->catid            = $cat_id;
    $article->created          = JFactory::getDate()->toSQL();
    $article->created_by_alias = $authors;
    $article->images          = '{"image_intro":"'.$lead_url.'","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"'.$full_lead_url.'", "float_fulltext":"none","image_fulltext_alt":"","image_fulltext_caption":""}';
    $article->state            = 0;
    $article->access           = 1;
    $article->metadata         = '{"page_title":"","rights":"'.$photographers.'", "author":"'.$authors.'","robots":"", "xreference": "'.$article_id.'"}';
    $article->language         = '*';

    // Check to make sure our data is valid, raise notice if it's not.

    if (!$article->check()) {
    }

    // Now store the article, raise notice if it doesn't get stored.

    if (!$article->store(TRUE)) {
    }
  }
}

?>
