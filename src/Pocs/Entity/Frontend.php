<?php
namespace Pocs\Entity;

class Frontend implements \JsonSerializable
{
    protected $id;
    protected $base_url;
    protected $name;
    protected $apikey;

    protected $urls = array();

    public function getId()
    {
        return $this->id;
    }
    public function getBaseUrl()
    {
        return $this->base_url;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getApiKey()
    {
        return $this->apikey;
    }

    public function getUrls()
    {
        return $this->urls;
    }

    public function setUrls(Array $urls)
    {
        $this->urls = $urls;
    }

    /**
     * Add comments in the matching url.
     *
     * @param Array $comments
     *   An array of comments.
     */
    public function addCommentsToUrls(Array $comments)
    {
        for ($i = 0, $nb = count($comments); $i < $nb; ++$i) {
            $url = $this->getUrlById($comments[$i]->getUrlId());
            $url->addComment($comments[$i]);
        }

    }

    /**
     * Return the Url object corresponding to the given $id.
     *
     * @param int $id
     *   The url id.
     */
    public function getUrlById($id)
    {
        for ($i = 0, $nb = count($this->urls); $i < $nb; ++$i) {
            if ($this->urls[$i]->getId() == $id) {
                return $this->urls[$i];
            }
        }
    }

    public function importFromArray(Array $frontend)
    {
        if (array_key_exists('id', $frontend)) {
            $this->id = array_key_exists('id', $frontend) ? $frontend['id'] : '';
            $this->base_url = $frontend['base_url'];
        }
        else {
            $this->id = '';
            $this->base_url = $this->removeSchemeFromUrl($frontend['base_url']);
        }

        $this->name = $frontend['name'];
        $this->apikey = $frontend['apikey'];
    }

    public function transformInArray()
    {
        return array(
            'id' => $this->id,
            'base_url' => $this->base_url,
            'name' => $this->name,
            'apikey' => $this->apikey,
        );
    }

    public function jsonSerialize()
    {
        return $this->transformInArray();
    }

    public function removeSchemeFromUrl($url)
    {
        $parsed = parse_url($url);
echo '<pre>';print_r($parsed);echo '</pre>';
        if (!array_key_exists('host', $parsed)) {
            throw new \Exception('Incorrect Url (no host found)');
        }

        return $parsed['host']
            . (array_key_exists('port', $parsed) ? ':' . $parsed['port'] : '')
            . (array_key_exists('path', $parsed) ? $parsed['path'] : '');
    }
}

