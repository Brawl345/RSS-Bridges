<?php
class EscargotTodayBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'Escargot Today';
    const URI = 'https://escargot.log1p.xyz/';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'The latest news from Escargot Land (MSN server replacement).';

    public function collectData() {
        // Retrieve webpage
        $pageUrl = self::URI . 'etc/escargot-today';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        // Process articles
        foreach($html->find('section.p1 h4') as $element) {

            if(count($this->items) >= 10) {
                break;
            }

            $article_title = trim($element->innertext);
            $article_content = trim($element->next_sibling()->innertext);
            $article_content = str_replace("=\"/", "=\"" . self::URI . "", $article_content);
            $article_timestamp = strtotime($article_title);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $pageUrl;
                $item['title'] = $article_title;
                $item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
                $item['uid'] = $article_title;
                $this->items[] = $item;
            }
        }
    }
}
