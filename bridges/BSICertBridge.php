<?php
class BSICertBridge extends BridgeAbstract
{
    const MAINTAINER = 'Brawl';
    const NAME = 'BSI Bürger-CERT-Sicherheitshinweise';
    const URI = 'https://www.bsi.bund.de/';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'Sicherheitshinweise des Bürger-CERT vom Bundesministerium für Internetsicherheit';

    public function getIcon()
    {
        return self::URI . "SiteGlobals/Frontend/Images/favicons/android-chrome-256x256.png?__blob=normal&amp;v=3";
    }

    public function collectData()
    {
        // Retrieve webpage
        $pageUrl = self::URI . 'DE/Service-Navi/Abonnements/Newsletter/Buerger-CERT-Abos/Buerger-CERT-Sicherheitshinweise/buerger-cert-sicherheitshinweise_node.html';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        foreach ($html->find('tbody > tr') as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $title_cell = $element->find('td', 1);
            $article_title = trim($title_cell->find('a', 0)->plaintext);
            $article_uri = self::URI . trim($title_cell->find('a', 0)->href);
            $article_content = trim(substr($title_cell->plaintext, strlen($article_title)));
            $article_timestamp = strtotime($element->find('td', 2)->plaintext);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
                $this->items[] = $item;
            }
        }
    }
}
