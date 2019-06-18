<?php
class SevenTVBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'ProSiebenSat.1';
    const URI = 'https://www.sat1.de/';
    const CACHE_TIMEOUT = 1800; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest episode of a ProSiebenSat.1 show.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show (from URL, e.g. "auf-streife")',
                'type' => 'text',
                'required' => true
            ),
            'channel' => array(
                'name' => 'TV channel',
                'type' => 'list',
                'values' => array(
                    'SAT.1' => 'https://www.sat1.de/',
                    'ProSieben' => 'https://www.prosieben.de/',
                    'ProSieben MAXX' => 'https://www.prosiebenmaxx.de/',
                    'Kabel Eins' => 'https://www.kabeleins.de/',
                    'SIXX' => 'https://www.sixx.de/'
                ),
                'defaultValue' => 'https://www.sat1.de/'
            )
        )
    );
    
    private $showName = '';
    private $pageUrl = self::URI;
    
    public function getName(){
        if (!empty($this->showName)) {
            return $this->showName . ' - Ganze Folgen';
        }
        return parent::getName();
    }
    
    public function getURI(){
        return $this->pageUrl;
    }

    public function collectData() {
        // Retrieve and check user input
        $show = $this->getInput('show');
        if (empty($show))
            returnClientError('Invalid show: ' . $show);
        $tvchannel = $this->getInput('channel');

        // Retrieve webpage
        $pageUrl = $tvchannel . 'tv/' . $show;
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request: ' . $pageUrl);
        
        // Get show name
        $showName = trim($html->find('div.channel-title > h1', 0)->plaintext);
        $this->showName = $showName;
        $this->pageUrl = $pageUrl;
        
        // Process article
        $element = $html->find('div.clickable-box-container')[0];
        
        $article_uri = $tvchannel . substr($element->find('a.clickable-box-link', 0)->href, 1);
        $article_title = $showName . ': "' . trim($element->find('h3.teaser-headline', 0)->plaintext) . '"';
        // Get article thumb from inline CSS
        $article_thumbnail = trim($element->find('figure.teaser-img-squashed', 0)->attr['style']);
        $article_thumbnail = str_replace("background-image:url(", "", $article_thumbnail);
        $article_thumbnail = str_replace(");background-repeat:no-repeat", "", $article_thumbnail);
        $article_thumbnail = str_replace("213x94", "996x440", $article_thumbnail);
                
        // Store article in items array
        if (!empty($article_title) && !empty($article_uri)) {
            $item = array();
            $item['uri'] = $article_uri;
            $item['title'] = $article_title;
            $item['enclosures'] = array($article_thumbnail);
            $this->items[] = $item;
        }

    }
}
