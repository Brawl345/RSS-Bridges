<?php
class JapanTimesFeaturesBridge extends BridgeAbstract {

	const MAINTAINER = 'Brawl';
	const NAME = 'Deep Reads by The Japan Times';
	const URI = 'https://features.japantimes.co.jp/';
	const CACHE_TIMEOUT = 21600; // 21600 = 6h
	const DESCRIPTION = 'Deep Dives from the JT.';

	public function collectData() {
		// Retrieve webpage
		$pageUrl = self::URI;
		$html = getSimpleHTMLDOM($pageUrl)
			or returnServerError('Could not request webpage: ' . $pageUrl);

		// Process articles
		foreach($html->find('div.esg-media-cover-wrapper') as $element) {

			if(count($this->items) >= 10) {
				break;
			}
            
			$article_title = trim(strip_tags($element->find('div.eg-jt-features-grid-skin-element-0', 0)->innertext));
			$article_uri = $element->find('a.eg-invisiblebutton', 0)->href;
			$article_thumbnail = $element->find('img', 0)->src;
            $article_content = '<img src="'. $article_thumbnail .'"><br>';
			$article_content .= trim(strip_tags($element->find('div.eg-jt-features-grid-skin-element-6', 0)->innertext));
			$article_timestamp = strtotime($element->find('div.eg-jt-features-grid-skin-element-24', 0)->innertext);

			// Store article in items array
			if (!empty($article_title)) {
				$item = array();
				$item['uri'] = $article_uri;
				$item['title'] = $article_title;
				//$item['enclosures'] = array($article_thumbnail);
				$item['content'] = $article_content;
				$item['timestamp'] = $article_timestamp;
				$this->items[] = $item;
			}
		}
	}
}
