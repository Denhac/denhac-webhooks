<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'https://github.com/Denhac/denhac-webhooks.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('webhooks.denhac.org')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '/var/www/html');

// Custom tasks
task('artisan:horizon:terminate', artisan('horizon:terminate'));
task('artisan:websockets:restart', artisan('websockets:restart'));

// Hooks

after('deploy:failed', 'deploy:unlock');
after('deploy:symlink', 'artisan:horizon:terminate');
after('deploy:symlink', 'artisan:websockets:restart');
