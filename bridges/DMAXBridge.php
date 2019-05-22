<?php
class DMAXBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'DMAX';
    const URI = 'https://www.dmax.de/';
    const CACHE_TIMEOUT = 3600; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest episode of a DMAX show.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show (e.g. "112-feuerwehr-im-einsatz")',
                'type' => 'text',
                'required' => true
            ),
        )
    );
    
    private $showName = '';
    private $pageUrl = self::URI;
    
    public function getName(){
        if(!empty($this->showName)) {
            return $this->showName;
        }
        
        return parent::getName();
    }
    
    public function getIcon(){
        return self::URI . 'assets/favicons/dmax/favicon-96x96.png';
    }
    
    public function getURI(){
        return $this->pageUrl;
    }

    public function collectData() {
        // Retrieve and check user input
        $show = $this->getInput('show');
        if (empty($show))
            returnClientError('Invalid show: ' . $show);

        // Retrieve webpage
        $pageUrl = self::URI . 'programme/' . $show;
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request DMAX: ' . $pageUrl);
        
        // Get show name
        $showName = $html->find('title', 0)->plaintext;
        $this->showName = $showName;
        $this->pageUrl = $pageUrl;

        // Process articles
        foreach(array_reverse($html->find('li.vertical-list-item')) as $element) {
            $article_title = $showName . ' - ' . trim($element->find('h3', 0)->plaintext);
            $article_uri = self::URI . substr($element->find('a', 0)->href, 1);
            $article_thumbnail = explode('?', $element->find('img', 0)->src)[0];
            $article_content = trim($element->find('div.vertical-list-item__description', 0)->plaintext);

            // Store article in items array
            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['enclosures'] = array($article_thumbnail);
                $item['content'] = $article_content;
                $this->items[] = $item;
            }
        }
    }
}
