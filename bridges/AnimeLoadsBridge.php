<?php
class AnimeLoadsBridge extends BridgeAbstract {

	const MAINTAINER = 'Brawl';
	const NAME = 'Anime-Loads';
	const URI = 'https://www.anime-loads.org/';
	const CACHE_TIMEOUT = 21600; // 21600 = 6h
	const DESCRIPTION = 'New series on Anime-Loads.';

	public function collectData() {
		// Retrieve webpage
		$pageUrl = self::URI . 'all';
		$html = getSimpleHTMLDOM($pageUrl)
			or returnServerError('Could not request webpage: ' . $pageUrl);

		// Process articles
		foreach($html->find('div.col-sm-6.col-xs-12') as $element) {

			if(count($this->items) >= 10) {
				break;
			}

			$article_title = trim($element->find('h4', 0)->innertext);
			$article_thumbnail = $element->find('img', 0)->src;            
			$article_uri = $element->find('h4 a', 0)->href;
			
            $article_content = '<img src="'. $article_thumbnail .'"><br>';
			$article_content .= trim(strip_tags($element->find('div.mt10', 0)->innertext));

			// Store article in items array
			if (!empty($article_title)) {
				$item = array();
				$item['uri'] = $article_uri;
				$item['title'] = $article_title;
				//$item['enclosures'] = array($article_thumbnail);
				$item['content'] = $article_content;
				$this->items[] = $item;
			}
		}
	}
}
