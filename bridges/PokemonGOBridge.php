<?php
class PokemonGOBridge extends BridgeAbstract {

    const MAINTAINER = 'Brawl';
    const NAME = 'Pokémon GO';
    const URI = 'https://pokemongolive.com/';
    const CACHE_TIMEOUT = 21600; // 21600 = 6h
    const DESCRIPTION = 'Get the latest official "Pokémon GO" news.';
        const PARAMETERS = array(
        array(
            'lang' => array(
                'name' => 'Language',
                'type' => 'list',
                'values' => array(
                    'English' => 'en',
                    'Chinese (Traditional)' => 'zh_hant',
                    'French' => 'fr',
                    'German' => 'de',
                    'Italian' => 'it',
                    'Japanese' => 'ja',
                    'Korean' => 'ko',
                    'Portugese' => 'pt_br',
                    'Spanish' => 'es',
                ),
                'defaultValue' => 'en'
            )
        )
    );
    
    public function getIcon(){
        return 'https://pokemongolive.com/img/icons/favicon.ico';
    }

    public function collectData() {
        // Retrieve webpage
        $lang = $this->getInput('lang');
        $pageUrlBase = self::URI . $lang . '/';
        $pageUrl = $pageUrlBase . 'post/';
        $html = getSimpleHTMLDOM($pageUrl)
            or returnServerError('Could not request webpage: ' . $pageUrl);

        // Process articles
        foreach($html->find('div.post-list__title') as $element) {

            if(count($this->items) >= 5) {
                break;
            }

            $article_title = trim(strip_tags($element->find('a', 0)->innertext));
            $article_uri = self::URI . substr($element->find('a', 0)->href, 1);
            
            $article_html = getSimpleHTMLDOM($article_uri)
                or returnServerError('Could not request webpage: ' . $article_uri);
                
            $article_content = $article_html->find('div.grid__item--10-cols--gt-md', 1)->innertext;
            $article_content = str_replace('src="/', 'src="' . self::URI . '/', $article_content);

            // Store article in items array
            if (!empty($article_title)) {
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['content'] = $article_content;
                $this->items[] = $item;
            }
        }
    }
}
