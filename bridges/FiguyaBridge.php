<?php
class FiguyaBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'Figuya';
    const URI = 'https://figuya.com/';
    const CACHE_TIMEOUT = 3600; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest figures from Figuya.';
    const PARAMETERS = array(
        array(
            'query' => array(
                'name' => 'Search query (leave blank to return the newest figures)',
                'type' => 'text'
            ),
            'limit' => array(
                'name' => 'Number of items to return',
                'type' => 'number',
                'defaultValue' => 10
            ),
            'language' => array(
                'name' => 'Language',
                'type' => 'list',
                'values' => array(
                    'German' => 'de/produkte.json',
                    'English' => 'en/products.json'
                ),
                'defaultValue' => 'de/produkte.json'
            )
        )
    );
    
    private $feedTitle = '';
    
    public function getName(){
        if(!empty($this->feedTitle)) {
            return parent::getName() . ' - ' . $this->feedTitle;
        }

        return parent::getName();
    }

    public function collectData(){
        // Retrieve and check user input
        $query = $this->getInput('query');
        
        $limit = $this->getInput('limit');
        if ($limit <= 0 || $limit > 100) {
            returnClientError('Limit must be greater than 0 and lower than 100!');
        }
        
        $lang = $this->getInput('language');
        
        // Retrieve webpage
        if (empty($query)) {
            $pageUrl = self::URI . $lang . '?q[query]=&q[page]=0&q[stock_states][]=preorder&q[stock_states][]=backorder&q[stock_states][]=stocked&q[order]=touched_at&q[direction]=desc&q[per]=' . $limit;
        } else {
            $pageUrl = self::URI . $lang . '?q[query]=' . urlencode($query) . '&q[page]=0&q[stocked]=true&q[per]=' . $limit;
        }
        
        $html = getContents($pageUrl)
            or returnServerError('Could not request Figuya.com.');
        $data = json_decode($html);
        
        if (!empty($query)) {
            $this->feedTitle = $data->search->query->label . ': ' . $data->search->query->value;
        }

        // Process articles
        foreach($data->list as $element) {
            $article_title = $element->title;
            $article_uri = self::URI . substr($element->href, 1);
            $article_thumbnail = self::URI . substr($element->image->profile, 1);
            $article_content = $element->price->label . ': ' . $element->price->formatted;
            if (!$element->discount->price == 0) {
                $article_content .= ' (' . $element->discount->label . ': ' . $element->discount->formatted . ')';
            }
            
            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['title'] = $article_title;
                $item['uri'] = $article_uri;
                $item['enclosures'] = array($article_thumbnail);
                $item['content'] = $article_content;
                $this->items[] = $item;
            }
        }
    }
}
