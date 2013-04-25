<?php
namespace Pocs\Entity;

class Url implements \JsonSerializable
{
    protected $id;
    protected $url;
    protected $frontend_id;

    protected $comments;

    /* GET / SET */
    public function getId()
    {
        return $this->id;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getFrontendId()
    {
        return $this->frontend_id;
    }

    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add a comment
     *
     * @param Comment $comment
     *   A comment to add.
     */
    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;
    }

    public function importFromArray(Array $url)
    {
        $this->id = $url['id'];
        $this->frontend_id = $url['frontend_id'];
        $this->url = $url['url'];
    }

    public function transformInArray()
    {
        return array(
            'id' => $this->id,
            'fontend_id' => $this->frontend_id,
            'url' => $this->url,
        );
    }

    public function jsonSerialize()
    {
        return $this->transformInArray();
    }
}
