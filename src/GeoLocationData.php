<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Locale;

/**
 * Stores geo location data.
 */
class GeoLocationData extends \BitFrame\Data\ApplicationData
{
    /**
     * {@inheritdoc}
     *
     * Note that this specific implementation 
     * makes this method immutable.
     *
     * @see: ApplicationData::offsetSet()
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($key, $value)
    {
        throw new \BadMethodCallException(self::class . ' objects are immutable.');
    }

    /**
     * {@inheritdoc}
     *
     * Note that this specific implementation 
     * makes this method immutable.
     *
     * @see: ApplicationData::offsetUnset()
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($key)
    {
        throw new \BadMethodCallException(self::class . ' objects are immutable.');
    }
}