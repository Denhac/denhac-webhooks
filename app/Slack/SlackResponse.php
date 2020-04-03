<?php

namespace App\Slack;


use Illuminate\Contracts\Support\Jsonable;

class SlackResponse implements Jsonable
{
    /**
     * @var string
     */
    private $text;
    private $blocks;

    public function __construct()
    {
        $this->text = '';
        $this->blocks = collect();
    }

    public function text($text)
    {
        $this->text = $text;
        $this->blocks->add([
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => $text,
            ],
        ]);

        return $this;
    }

    public function toJson($options = 0)
    {
        $data = [
            "response_type" => "ephemeral",
            "blocks" => $this->blocks,
        ];

        return json_encode($data);
    }
}
