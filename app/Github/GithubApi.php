<?php

namespace App\Github;


class GithubApi
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function team($name)
    {
        return new TeamApi($name, $this->tokenManager->getInstallationAccessToken());
    }
}
