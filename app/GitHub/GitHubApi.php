<?php

namespace App\GitHub;

class GitHubApi
{
    /**
     * @var TokenManager
     */
    private TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function team($name)
    {
        return new TeamApi($name, $this->tokenManager->getInstallationAccessToken());
    }
}
