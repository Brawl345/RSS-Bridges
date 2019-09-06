<?php
class ViperOSBridge extends BridgeAbstract {

	const MAINTAINER = 'Brawl';
	const NAME = 'ViperOS';
	const URI = 'https://viperos.org/';
	const CACHE_TIMEOUT = 0; // 3600 = 1h
	const DESCRIPTION = 'Get the newest ViperOS Android build.';
	const PARAMETERS = array(
		array(
            'device' => array(
                'name' => 'Device codename',
                'type' => 'text'
            ),
		)
	);
    
    private $feedTitle = '';
    
    public function getName(){
        if(!empty($this->feedTitle)) {
            return parent::getName() . ' - ' . $this->feedTitle;
        }

		return parent::getName();
	}
    
	public function getIcon() {
		return 'https://download.viperos.org/favicons/android-chrome-192x192.png';
	}

	public function collectData(){
        // Retrieve and check user input
        $device = $this->getInput('device');
        
        // Retrieve webpage
        if (empty($device))
            returnClientError('Invalid device: ' . $device);
        
        $pageUrl = 'https://raw.githubusercontent.com/Viper-Devices/official_devices/master/' . urlencode($device) . '/build.json';
        
		$html = getContents($pageUrl)
			or returnServerError('Could not request raw.githubusercontent.com.');
        $data = json_decode($html);
        
        $this->feedTitle = $device;

		// Process articles
        foreach($data->response as $element) {
            $article_title = $element->filename;
            $article_uri = $element->url;
            $article_content = $element->romtype . ' ViperOS build for ' . $device . ':<br><a href="' . $element->url . '">Download ' . $element->version . '</a> ('. number_format($element->size / 1048576, 2) . ' MB, MD5: '. $element->id . ')';
            $article_timestamp = $element->datetime;
            
            if (!empty($article_title) && !empty($article_uri)) {
				$item = array();
				$item['title'] = $article_title;
				$item['uri'] = $article_uri;
				$item['content'] = $article_content;
                $item['timestamp'] = $article_timestamp;
				$this->items[] = $item;
            }
		}
	}
}
