<?php
class SSBUNewsBridge extends BridgeAbstract
{

    const MAINTAINER = 'Brawl';
    const NAME = 'Super Smash Bros. Ultimate News';
    const URI = 'https://www-aaaba-lp1-hac.cdn.nintendo.net/';
    const CACHE_TIMEOUT = 43200; // 43200 = 12h
    const DESCRIPTION = 'Returns the latest Super Smash Bros. Ultimate news.';
    const PARAMETERS = array(
        array(
            'lang' => array(
                'name' => 'Language',
                'type' => 'list',
                'values' => array(
                    'English (US)' => 'en-US',
                    'Chinese (Simplified)' => 'zh-CN',
                    'Chinese (Traditional)' => 'zh-TW',
                    'Dutch' => 'nl',
                    'English (GB)' => 'en-GB',
                    'French' => 'fr',
                    'German' => 'de',
                    'Italian' => 'it',
                    'Japanese' => 'ja',
                    'Korean' => 'ko',
                    'Russian' => 'ru'
                ),
                'defaultValue' => 'en-US'
            )
        )
    );

    private $messagesString = '';
    private $pageUrl = 'https://www-aaaba-lp1-hac.cdn.nintendo.net/en-US/index.html';

    public function getName()
    {
        if (!empty($this->messagesString)) {
            return $this->messagesString;
        }

        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://www.smashbros.com/favicon.ico';
    }

    public function getURI()
    {
        return $this->pageUrl;
    }

    public function collectData()
    {
        // Retrieve webpage
        $lang = $this->getInput('lang');
        $pageUrlBase = self::URI . $lang . '/';
        $pageUrl = $pageUrlBase . 'index.html';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        $this->messagesString = $html->find('title', 0)->plaintext . ' ' . $html->find('div.shrink-label', 0)->plaintext;
        $this->pageUrl = $pageUrl;

        // Process articles
        foreach ($html->find('li.article-item') as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $article_title = trim($element->find('h2', 0)->plaintext);
            $article_uri = $pageUrlBase . $element->find('a', 0)->href;
            $article_thumbnail = $pageUrlBase . $element->find('img', 0)->{'data-lazy-src'};
            $article_content = '<a href="' . $article_uri . '"><img src="' . $article_thumbnail . '"></a>';
            $article_timestamp = $element->attr['data-show-new-badge-published-at'];

            // Store article in items array
            if (!empty($article_title) && !empty($article_uri)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                // $item['enclosures'] = array($article_thumbnail);
                $item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
                $this->items[] = $item;
            }
        }
    }
}
