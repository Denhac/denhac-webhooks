<?php

namespace App\Slack\api;


use Illuminate\Support\Collection;
use JetBrains\PhpStorm\ArrayShape;

trait SlackApiTrait
{
    protected function paginate($key, $request): Collection
    {
        $cursor = "";
        $collection = collect();
        do {
            $response = json_decode($request($cursor)->getBody(), true);
            if (array_key_exists($key, $response)) {
                $collection = $collection->merge($response[$key]);
            } else {
                return collect($response);
            }

            if (!array_key_exists("response_metadata", $response)) break;
            if (!array_key_exists("next_cursor", $response["response_metadata"])) break;

            $cursor = $response["response_metadata"]["next_cursor"];
        } while ($cursor != "");

        return $collection;
    }

    #[ArrayShape(['name' => "", 'contents' => ""])] protected function _multipart($name, $content) {
        return [
            'name' => $name,
            'contents' => $content,
        ];
    }
}
