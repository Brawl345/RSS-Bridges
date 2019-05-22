<?php
class MTVBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'MTV';
    const URI = 'http://www.mtv.de/';
    const CACHE_TIMEOUT = 1800; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest episode of an MTV show.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show (e.g. "2o5kug/yo-mtv-raps")',
                'type' => 'text',
                'required' => true
            )
        )
    );
    
    private $showName = '';
    private $pageUrl = self::URI;
    
    public function getName(){
        if ( !empty($this->showName) ) {
            return $this->showName . ' - Neue Folgen';
        } else {
            return parent::getName();
        }
    }
    
    public function getIcon(){
        return 'http://www.mtv.de/sitewide/images/brand/mtv/favicon.ico';
    }
    
    public function getURI(){
        return $this->pageUrl;
    }
    
    private function startsWith($str, $searchFor) {
        $length = strlen($searchFor);
        return (substr($str, 0, $length) === $searchFor);
    }

    public function collectData() {
        // Retrieve and check user input
        $show = $this->getInput('show');
        if (empty($show))
            returnClientError('Invalid show: ' . $show);

        // Retrieve webpage
        $pageUrl = self::URI . '/shows/' . $show;
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request MTV: ' . $pageUrl);
        
        // Get show name
        $showName = $html->find('h1', 0)->plaintext;
        $this->showName = $showName;
        $this->pageUrl = $pageUrl;
        
        // Get us some nice JSON
        preg_match_all('/\.*triforceManifestFeed = (.+);/i', $html, $result);
        $json = json_decode($result[1][0]);
        
        // Process articles
        if ( property_exists($json->manifest->zones->t4_lc_promo1->feedData->result, "data") ) {
            foreach($json->manifest->zones->t4_lc_promo1->feedData->result->data->items as $element) {

                if(count($this->items) >= 10) {
                    break;
                }
                
                // Get data
                if ( $this->startsWith($element->headline, $showName) ) {
                    $article_title = $element->headline;
                } else {
                    $article_title = $showName . ' - ' . $element->headline;
                }
                $article_uri = $element->canonicalURL;
                $article_content = $element->description;
                if (property_exists($element, "images")) {
                    $article_thumbnail = $element->images->url;
                } else {
                    $article_thumbnail = "";
                }
                $article_timestamp = $element->publishDate;

                // Store article in items array
                if (!empty($article_title) && !empty($article_uri)) {
                    $item = array();
                    $item['uri'] = $article_uri;
                    $item['title'] = $article_title;
                    $item['content'] = $article_content;
                    $item['enclosures'] = array($article_thumbnail);
                    $item['timestamp'] = $article_timestamp;
                    $this->items[] = $item;
                }
            }
        }
    }
}
