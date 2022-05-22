<?php
class WHODiseaseOutbreakBridge extends BridgeAbstract
{

    const MAINTAINER = 'Brawl';
    const NAME = 'WHO Disease Outbreak News';
    const URI = 'https://www.who.int/emergencies/disease-outbreak-news';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'Latest WHO Disease Outbreak News (DONs), providing information on confirmed acute public health events or potential events of concern.';

    public function collectData()
    {
        // Retrieve webpage
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request webpage: ' . self::URI);

        // Process articles
        foreach ($html->find('div.sf-list-vertical a.sf-list-vertical__item') as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $row = $element->find('.sf-list-vertical__title', 0);

            $article_link = $element->href;
            $article_title = trim($row->find('.full-title', 0)->plaintext);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $article_link;
                $item['title'] = $article_title;
                $item['uid'] = $article_link;
            }

            $timestamp = $row->find('span', 1)->plaintext;
            if (isset($timestamp) && !empty($timestamp)) {
                $timestamp = str_replace(' | ', '', $timestamp);
                $article_timestamp = strtotime($timestamp);
                $item['timestamp'] = $article_timestamp;
            }

            $this->items[] = $item;
        }
    }
}
