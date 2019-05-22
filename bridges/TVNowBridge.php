<?php
class TVNowBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'TVNow';
    const URI = 'https://www.tvnow.de/';
    const CACHE_TIMEOUT = 3600; // 3600 = 1h
    const DESCRIPTION = 'Returns the latest episodes from TVNow.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show number (e.g. verdachtsfaelle-248 -> 248)',
                'type' => 'number',
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
    
    public function getURI(){
        return $this->pageUrl;
    }

    public function collectData(){
        // Retrieve and check user input
        $show = $this->getInput('show');
        if (empty($show))
            returnClientError('Invalid show number: ' . $show);
        
        // Retrieve webpage
        $pageUrl = 'https://apigw.tvnow.de/module/teaserrow/format/episode/' . $show;
        
        $html = getContents($pageUrl)
            or returnServerError('Could not request apigw.tvnow.de.');
        $data = json_decode($html);
        
        $this->showName = $data->items[0]->ecommerce->teaserFormatName;
        $this->pageUrl = self::URI . 'serien/' . $show;
        $items_array = $data->items;
        
        if (count($items_array) > 1) {
            // Do we have to reverse the array?
            $timestamp_one = $this->TVNowStringToTime($items_array[0]->text);
            $timestamp_two = $this->TVNowStringToTime($items_array[1]->text);
            if ($timestamp_two > $timestamp_one) {
                $items_array = array_reverse($items_array);
            }
        }

        // Process articles
        foreach($items_array as $element) {
            
            if(count($this->items) >= 10) {
                break;
            }
            
            $article_title = $this->showName . ': "' . $element->headline . '"';
            $article_uri = self::URI . substr($element->url, 1);
            $article_thumbnail = end($element->images)->src;
            $article_uid = $element->id;

            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['title'] = $article_title;
                $item['uri'] = $article_uri;
                $item['enclosures'] = array($article_thumbnail);
                $item['uid'] = $article_uid;
                $this->items[] = $item;
            }
        }
    }
    
    private function TVNowStringToTime($text) {
       /* Converts TVNow string date into a timestamp (e.g. "Staffel 1, Folge 1 | Sa. 18.08.2018, 20:15 Uhr") */
       $timestamp = explode('|', $text)[1];  // Split text string
       $timestamp = substr($timestamp, 4);  // Remove weekday
       $timestamp = trim(str_replace('Uhr', '', $timestamp)); // Remove "Uhr" and whitespaces
       $timestamp = strtotime($timestamp);  // Finally convert to timestamp

       return $timestamp;
    }

}
