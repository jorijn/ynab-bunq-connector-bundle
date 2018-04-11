<?php

namespace Jorijn\YNAB\BunqConnectorBundle\Factory;

use YNAB\Configuration;

class ApiFactory
{
    /**
     * @param string $apiKey
     *
     * @return Configuration
     */
    public function createConfiguration(string $apiKey): Configuration
    {
        $configuration = new Configuration();
        $configuration->setApiKey('Authorization', $apiKey)
            ->setApiKeyPrefix('Authorization', 'Bearer');

        return $configuration;
    }
}
