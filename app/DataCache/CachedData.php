<?php

namespace App\DataCache;

use App\External\HasApiProgressBar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inherit from this class to automatically scope your instance to the current request lifecycle. It also provides an
 * easy to use cache that can be used with or without a key. This allows you to inject data objects that you need or
 * even compose data objects that will only fetch the data once.
 *
 * By convention, subclasses should implement the get method and accept any parameters they may need through
 * that.
 */
abstract class CachedData
{
    use HasApiProgressBar;

    private Collection $cache;

    public function __construct()
    {
        // This makes it so any class that inherits from this doesn't need to manually scope itself in a service
        // provider, since multiple classes will probably use the same bit of data and get resolved in different places
        // and different ways.
        /** @var Container $app */
        $app = app();
        $class_name = get_class($this);
        if (! $app->resolved($class_name)) {
            $app->scoped($class_name, function () {
                return $this;
            });
        }

        $this->cache = collect();
    }

    protected function cache($key, $fn = null): mixed
    {
        if (is_callable($key) && is_null($fn)) {
            $fn = $key;
            $key = '';
        }

        return $this->cache->get($key, $fn);
    }
}
