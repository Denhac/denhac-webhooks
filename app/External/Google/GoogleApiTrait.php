<?php

namespace App\External\Google;

use App\External\ApiProgress;
use Illuminate\Support\Collection;

trait GoogleApiTrait
{
    protected function paginate($key, $request, ApiProgress $progress = null): Collection
    {
        $nextPageToken = '';
        $collection = collect();
        $stepCount = 0;
        do {
            $response = json_decode($request($nextPageToken)->getBody(), true);
            if (array_key_exists($key, $response)) {
                $collection = $collection->merge($response[$key]);
            } else {
                return collect($response);  // TODO Might need to raise exception here, don't know why we'd want this? Empty return instead?
            }

            $stepCount++;

            if (! array_key_exists('nextPageToken', $response)) {
                break;
            }

            $nextPageToken = $response['nextPageToken'];

            if (! is_null($progress)) {
                $progress->setProgress($stepCount, $stepCount + 1);
            }
        } while ($nextPageToken != '');

        if (! is_null($progress)) {
            $progress->setProgress($stepCount, $stepCount);
        }

        return $collection;
    }
}
