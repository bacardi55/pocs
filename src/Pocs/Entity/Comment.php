<?php
namespace Pocs\Entity;

class Comment implements \JsonSerializable
{
    protected $id;
    protected $user_name;
    protected $user_email;
    protected $url_id;
    protected $date;
    protected $comment;

    public function getId()
    {
        return $this->id;
    }

    public function getUserName()
    {
        return $this->user_name;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function getUrlId()
    {
        return $this->url_id;
    }

    public function importFromArray(Array $comment)
    {
        $this->id = $comment['id'];
        $this->user_name = $comment['user_name'];
        $this->user_email = $comment['user_email'];
        $this->url_id = $comment['url_id'];
        $this->date = $comment['date'];
        $this->comment = $comment['comment'];
    }

    public function transformInArray()
    {
        return array(
            'id' => $this->id,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'url_id' => $this->url_id,
            'date' => $this->date,
            'comment' => $this->comment,
        );
    }

    public function jsonSerialize()
    {
        return $this->transformInArray();
    }
}
