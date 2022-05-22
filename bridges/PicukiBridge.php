<?php
class PicukiBridge extends BridgeAbstract
{

    const MAINTAINER = 'Brawl';
    const NAME = 'Picuki';
    const URI = 'https://www.picuki.com/';
    const CACHE_TIMEOUT = 3600; // 3600 = 1h
    const DESCRIPTION = 'Get user feed from Instagram via Picuki.';
    const PARAMETERS = array(
        array(
            'username' => array(
                'name' => 'Username',
                'type' => 'text'
            )
        )
    );

    private $username;

    public function getURI()
    {
        return self::URI . 'profile/' . $this->username;
    }

    public function getName()
    {
        return $this->username . ' on Instagram';
    }

    public function getIcon()
    {
        return 'https://www.instagram.com/static/images/ico/favicon-192.png/68d99ba29cc8.png';
    }

    private function str_starts_with($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }

    public function collectData()
    {
        // Retrieve and check user input
        $this->username = $this->getInput('username');

        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Could not request picuki.com.');

        $this->username = $html->find('h1.profile-name-top', 0)->plaintext;
        $name = $html->find('h2.profile-name-bottom', 0)->plaintext;

        if (empty($this->username) || empty($name)) {
            returnServerError('Couldn\'t get profile');
        }

        // Process articles
        foreach ($html->find('div.box-photo[data-s="media"]') as $element) {

            if (count($this->items) >= 10) {
                break;
            }

            $post_text = trim($element->find('.photo-description', 0)->plaintext) or "Untitled";
            $post_link = $element->find('.photo > a', 0)->href;

            // TODO: Load video from the URL if Picuki decides to not suck
            $is_video = $element->find('.video-icon', 0);

            $image =  $element->find('.post-image', 0)->src;
            if (!$this->str_starts_with($image, 'http')) {
                $image  = self::URI . $image;
            }

            $post_content = '<p><img src="' . $image . '"</p>';
            if ($is_video === true) {
                $post_content .= '<p><strong>Post contains a video - visit the site to show it.</strong></p>';
            }
            $post_content .= '<p>' . $post_text . '</p>';

            if (!empty($post_text)) {
                $item = array();
                $item['title'] = $post_text;
                $item['author'] = $name;
                $item['uri'] = $post_link;
                $item['content'] = $post_content;
                $this->items[] = $item;
            }
        }
    }
}
