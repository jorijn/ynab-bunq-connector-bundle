<?php

namespace Jorijn\YNAB\BunqConnectorBundle\Factory;

use YNAB\Configuration;

class ApiFactory
{
    /**
     * @param string|null $apiKey
     *
     * @return Configuration
     */
    public function createConfiguration($apiKey = null): Configuration
    {
        $configuration = new Configuration();
        $configuration->setApiKey('Authorization', $apiKey)
            ->setApiKeyPrefix('Authorization', 'Bearer');

        return $configuration;
    }
}
