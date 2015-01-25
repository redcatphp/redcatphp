<?php

namespace Surikat\Model\Solarium\Support\DataFixtures;

use Surikat\Model\Solarium\Core\Client\Client;

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
