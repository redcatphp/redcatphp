<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Surikat\Geo\Geocoder\Provider;

use Surikat\Geo\Geocoder\Exception\InvalidCredentialsException;
use Surikat\Geo\Geocoder\Exception\ChainNoResultException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ChainProvider implements ProviderInterface
{
    /**
     * @var ProviderInterface[]
     */
    private $providers = [];

    /**
     * Constructor
     *
     * @param ProviderInterface[] $providers
     */
    public function __construct( array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Add a provider
     *
     * @param ProviderInterface $provider
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address)
    {
        $exceptions = [];

        foreach ($this->providers as $provider) {
            try {
                return $provider->getGeocodedData($address);
            } catch (InvalidCredentialsException $e) {
                throw $e;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        throw new ChainNoResultException(sprintf('No provider could provide the address "%s"', $address), $exceptions);
    }

    /**
     * {@inheritDoc}
     */
    public function getReversedData( array $coordinates)
    {
        $exceptions = [];

        foreach ($this->providers as $provider) {
            try {
                return $provider->getReversedData($coordinates);
            } catch (InvalidCredentialsException $e) {
                throw $e;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        throw new ChainNoResultException(sprintf('No provider could provide the coordinated %s', json_encode($coordinates)), $exceptions);
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxResults($limit)
    {
        foreach ($this->providers as $provider) {
            $provider->setMaxResults($limit);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'chain';
    }
}
