<?php

namespace Database\Solarium\Support\DataFixtures;

use Database\Solarium\Core\Client\Client;

/**
 * @author Baldur Rensch <brensch@gmail.com>
 */
interface FixtureInterface
{
    /**
     * @param Client $client
     */
    public function load(Client $client);
}
