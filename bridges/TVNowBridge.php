<?php
class TVNowBridge extends BridgeAbstract
{

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

    public function getName()
    {
        if (!empty($this->showName)) {
            return $this->showName;
        }

        return parent::getName();
    }

    public function getURI()
    {
        return $this->pageUrl;
    }

    public function collectData()
    {
        // Retrieve and check user input
        $show = $this->getInput('show');
        if (empty($show))
            returnClientError('Invalid show number: ' . $show);

        // Retrieve webpage
        $pageUrl = 'https://api.tvnow.de/v3/movies?fields=*,format,paymentPaytypes,pictures,trailers&filter={%20%22FormatId%22%20:%20' . $show . '}&maxPerPage=10&order=BroadcastStartDate%20desc';

        $html = getContents($pageUrl)
            or returnServerError('Could not request api.tvnow.de.');
        $data = json_decode($html);

        $this->showName = $data->items[0]->format->title;
        $this->pageUrl = self::URI . 'serien/' . $show;

        // Process articles
        foreach ($data->items as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $article_title = $this->showName . ': "' . $element->title . '"';
            $article_uri = self::URI . 'serien/' . $element->format->seoUrl . '-' . $show;
            $article_content = trim($element->articleLong);
            $article_uid = $element->id;
            $article_timestamp = strtotime($element->broadcastStartDate);

            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['title'] = $article_title;
                $item['uri'] = $article_uri;
                $item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
                $item['uid'] = $article_uid;
                $this->items[] = $item;
            }
        }
    }
}
