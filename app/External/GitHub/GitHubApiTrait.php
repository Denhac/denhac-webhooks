<?php

namespace App\External\GitHub;


use App\External\ApiProgress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;

trait GitHubApiTrait
{

    protected function paginate($firstUrl, $request, ApiProgress $progress = null): Collection
    {
        $url = $firstUrl;
        $collection = collect();
        $stepCount = 0;
        do {
            /** @var Response $response */
            $response = $request($url);
            $json = json_decode($response->getBody(), true);
            $collection = $collection->merge($json);

            $stepCount++;

            $headers = $response->getHeaders();
            if (!array_key_exists("Link", $headers)) break;
            $linkHeader = $headers["Link"][0];
            $individualLink = preg_split("/, /", $linkHeader, -1, PREG_SPLIT_NO_EMPTY);
            $url = null;
            foreach($individualLink as $link) {
                $matches = [];
                if(1 !== preg_match("/<(?P<link>.*)>; rel=\"(?P<name>\w+)\"/", $link, $matches)) {
                    continue;  // We couldn't match anything. Might be an error to report.
                }
                if($matches['name'] != 'next') {
                    continue;  // We don't care about anything but our next page.
                }

                $url = $matches['link'];
                break;
            }

            if(is_null($url)) {
                break;  // We couldn't find the next URL after all of that.
            }

            if (!is_null($progress)) {
                $progress->setProgress($stepCount, $stepCount + 1);
            }
        } while (true);

        if (!is_null($progress)) {
            $progress->setProgress($stepCount, $stepCount);
        }

        return $collection;
    }
}
