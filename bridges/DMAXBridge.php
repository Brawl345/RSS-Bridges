<?php

class DMAXBridge extends BridgeAbstract
{

    const MAINTAINER = 'Brawl';
    const NAME = 'DMAX';
    const URI = 'https://www.dmax.de/';
    const CACHE_TIMEOUT = 0; // 3600 = 1h
    const DESCRIPTION = 'Returns the newest episode of a DMAX show.';
    const PARAMETERS = array(
        array(
            'show' => array(
                'name' => 'Show ID (e.g. "6023" for Steel Buddies, check website source code)',
                'type' => 'number',
                'required' => true
            ),
        )
    );
    const TOKEN_URI = 'https://eu1-prod.disco-api.com/token?realm=dmaxde';
    const DISCO_URI = 'https://eu1-prod.disco-api.com/content/videos//?include=primaryChannel,primaryChannel.images,show,show.images,genres,tags,images,contentPackages&sort=-seasonNumber,-episodeNumber&filter[show.id]=%d&filter[videoType]=EPISODE&page[number]=1&page[size]=100';

    private $showName = '';
    private $pageUrl = self::URI . 'sendungen/';

    public function getName()
    {
        if (!empty($this->showName)) {
            return $this->showName;
        }

        return parent::getName();
    }

    public function getIcon()
    {
        return self::URI . 'apple-touch-icon.png';
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
            returnClientError('Invalid show: ' . $show);

        // Get Token
        $tokenUrl = getSimpleHTMLDOM(self::TOKEN_URI)
        or returnServerError('Could not request DMAX token.');

        $token_json = json_decode($tokenUrl, true);
        $token = $token_json['data']['attributes']['token'];
        if (empty($token))
            returnServerError('Could not get DMAX token.');

        // Retrieve discovery URI
        $pageUrl = sprintf(self::DISCO_URI, $show);
        $html = getSimpleHTMLDOM($pageUrl, array('Authorization: Bearer ' . $token))
        or returnServerError('Could not request DMAX discovery URI: ' . $pageUrl);
        $json = json_decode($html, true);

        // Get show name
        foreach ($json["included"] as $incl_element) {
            if ($incl_element["type"] == "show") {
                $this->showName = $incl_element['attributes']['name'];
                $this->pageUrl = self::URI . 'sendungen/' . $incl_element['attributes']['alternateId'];
            }
        }

        if (empty($this->showName))
            returnClientError('Show not found.');

        // Process articles
        foreach ($json['data'] as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $episodeTitle = trim($element['attributes']['name']);
            if (array_key_exists('episodeNumber', $element['attributes']) // Both season + episode no. given
                && array_key_exists('seasonNumber', $element['attributes'])) {
                $article_title = sprintf($this->showName . ' S%02dE%02d: ' . $episodeTitle,
                    $element['attributes']['seasonNumber'],
                    $element['attributes']['episodeNumber']);
            } elseif (array_key_exists('episodeNumber', $element['attributes']) // Only season no. given
                && !array_key_exists('seasonNumber', $element['attributes'])) {
                $article_title = sprintf($this->showName . ' E%02d: ' . $episodeTitle,
                    $element['attributes']['episodeNumber']);
            } else {  // Nothing given
                $article_title = $this->showName . ' - ' . $episodeTitle;
            }
            $article_content = trim($element['attributes']['description']);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $this->pageUrl;
                $item['title'] = $article_title;
                $item['enclosures'] = array();
                $item['content'] = $article_content;
                $this->items[] = $item;
            }
        }
    }
}
