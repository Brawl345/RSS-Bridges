<?php

class GenshinImpactBridge extends BridgeAbstract
{
    const MAINTAINER = 'Brawl';
    const NAME = 'Genshin Impact News';
    const URI = 'https://genshin.mihoyo.com/';
    const CACHE_TIMEOUT = 3600; // 3600 = 1h
    const DESCRIPTION = 'Get the latest news from Genshin Impact!';
    const PARAMETERS = array(
        array(
            'lang' => array(
                'name' => 'Language',
                'type' => 'list',
                'values' => array(
                    'en' => 10,
                    'zh-tw' => 87,
                    'ko' => 124,
                    'ja' => 141,
                    'fr' => 161,
                    'de' => 182,
                    'pt' => 203,
                    'es' => 224,
                    'ru' => 245,
                    'id' => 266,
                    'vi' => 287,
                    'th' => 308,
                ),
                'required' => true
            ),
            'limit' => array(
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'defaultValue' => 10
            )
        )
    );

    private $pageUrl = self::URI;

    public function getURI()
    {
        return $this->pageUrl;
    }

    public function collectData()
    {
        // Retrieve and check user input
        $channelId = $this->getInput('lang');
        if (empty($channelId)) {
            returnClientError('Invalid language: ' . $channelId);
        }
        $parameters = $this->getParameters()[0]['lang']['values'];
        $langCode = array_search($channelId, $parameters);
        if (empty($langCode)) {
            returnClientError('Invalid language: ' . $langCode);
        }

        $limit = $this->getInput('limit');

        // Retrieve webpage
        $jsonUrl = sprintf(
            '%1$scontent/yuanshen/getContentList?pageSize=%2$d&pageNum=1&channelId=%3$d',
            $this->getURI(),
            $limit,
            $channelId
        );
        $json = getContents($jsonUrl)
            or returnServerError('Could not request genshin.mihoyo.com.');
        $data = json_decode($json);

        // Set feed web page
        $this->pageUrl = sprintf('%1$s%2$s/news', $this->pageUrl, $langCode);

        // Process articles
        foreach ($data->data->list as $element) {
            $article_title = $element->title;
            $article_uid = $element->contentId;

            if (!empty($article_title) && !empty($article_uid)) {
                $article_uri = sprintf('%1$s/detail/%2$d', $this->pageUrl, $article_uid);
                $article_timestamp = strtotime($element->start_time);
                $article_content = $element->intro;

                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['timestamp'] = $article_timestamp;
                $item['content'] = $article_content;
                $item['uid'] = $article_uid;

                // Search for thumbnail
                foreach ($element->ext as $ext) {
                    if ($ext->arrtName == 'banner') {
                        $banner_arr = $ext->value;
                        if (!empty($banner_arr)) {
                            $article_thumbnail = $banner_arr[0]->url;
                            $item['enclosures'] = array($article_thumbnail);
                        }
                    }
                }

                // Add article to all articles
                $this->items[] = $item;
            }
        }
    }
}
