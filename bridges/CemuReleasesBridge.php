<?php
class CemuReleasesBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'Cemu Releases';
    const URI = 'http://cemu.info/';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'Returns the latest Cemu releases.';

    public function collectData() {
        // Retrieve webpage
        $pageUrl = self::URI . 'changelog.html';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        // Process articles
        foreach($html->find('div[class=col-sm-12 well]') as $element) {

            if(count($this->items) >= 10) {
                break;
            }

            $title_array = explode('|', $element->find('h2.changelog', 0)->innertext);
            $title_array_len = count($title_array);
            $article_title = trim(strip_tags($title_array[0]));
            
            $article_content = '';
            if ($title_array_len >= 3) {
                $article_content .= str_replace('<a href="', '<a href="' . self::URI,$title_array[2]);
            }
            if ($title_array_len >= 4) {
                $article_content .= ' | ' . $title_array[3];
            }
            $article_content .= $element->find('ul', 0);
            
            $article_timestamp = strtotime(strip_tags($title_array[1]));

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
