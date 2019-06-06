<?php
class SevenTVBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = '7TV';
    const URI = 'https://www.7tv.de/';
    const CACHE_TIMEOUT = 1800; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest episode of a 7TV show.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show (e.g. "auf-streife")',
                'type' => 'text',
                'required' => true
            ),
            'clip_type' => array(
                'name' => 'Type',
                'type' => 'list',
                'values' => array(
                    'Ganze Folgen' => 'ganze-folgen',
                    'Clips' => 'alle-clips'
                ),
                'defaultValue' => 'ganze-folgen'
            )
        )
    );
    
    private $showName = '';
    private $pageUrl = self::URI;
    
    public function getName(){
        switch($this->getInput('clip_type')) {

            case 'ganze-folgen':
                if(!empty($this->showName)) {
                    return $this->showName . ' - Ganze Folgen';
                }
                break;

            case 'alle-clips':
                if(!empty($this->showName)) {
                    return $this->showName . ' - Alle Clips';
                }
                break;

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
        $clip_type = $this->getInput('clip_type');

        // Retrieve webpage
        $pageUrl = self::URI . $show . '/' . $clip_type;
        $html = getSimpleHTMLDOM($pageUrl)
        or returnServerError('Could not request 7TV: ' . $pageUrl);
        
        // Get show name
        $showName = trim($html->find('span.format-header_title', 0)->plaintext);
        $this->showName = $showName;
        $this->pageUrl = $pageUrl;

        // Process articles
        foreach($html->find('article.teaser') as $element) {

            if(count($this->items) >= 10) {
                break;
            }

            $article_title = $showName . ': "' . trim($element->find('h5.teaser-title', 0)->plaintext) . '"';
            $article_uri = self::URI . substr($element->find('a', 0)->href, 1);
            $article_thumbnail = $element->find('figure.teaser-figure', 0)->attr['data-src'];
            $article_timestamp = strtotime($element->find('p.teaser-info', 0)->plaintext);

            // Store article in items array
            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['enclosures'] = array($article_thumbnail);
                $item['timestamp'] = $article_timestamp;
                $this->items[] = $item;
            }
        }
    }
}
