<?php
class LoveLiveSIFBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'Love Live! School idol festival';
    const URI = 'https://www.school-fes.klabgames.net/';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'Get the latest news for the "Love Live! School idol festival" mobile game.';
    
    public function getIcon(){
        return 'https://www.school-fes.klabgames.net/images/favicon.png';
    }

    public function collectData() {
        // Retrieve webpage
        $pageUrl = self::URI . 'news/';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        // Process articles
        foreach($html->find('div.news-event-content') as $element) {

            if(count($this->items) >= 10) {
                break;
            }

            $article_title = trim(strip_tags($element->find('h2.news-event-content__desc__head', 0)->innertext));
            $article_uri = self::URI . substr($element->find('a', 0)->href, 1);
            $article_thumbnail = self::URI . substr($element->find('img', 0)->src, 3);
            $article_content = '<img src="'. $article_thumbnail .'"><br>';
            $article_content .= trim(strip_tags($element->find('p.news-event-content__desc__paragraph', 0)->innertext));
            $article_timestamp = strtotime($element->find('time', 0)->attr['datetime']);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                //$item['enclosures'] = array($article_thumbnail);
                $item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
                $this->items[] = $item;
            }
        }
    }
}
