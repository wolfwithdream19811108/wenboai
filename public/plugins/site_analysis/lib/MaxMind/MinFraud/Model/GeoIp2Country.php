<?php

namespace plugins\site_analysis\lib\MaxMind\MinFraud\Model;

use plugins\site_analysis\lib\GeoIp2\Record\Country;

/**
 * Model of the plugins\site_analysis\lib\GeoIp2 country information.
 *
 * @property-read bool $isHighRisk This value is true if the IP country is high
 * risk. <b>Deprecated effective August 29, 2019.</b>
 **/
class GeoIp2Country extends Country
{
    /**
     * @internal
     */
    protected $validAttributes = [
        'confidence',
        'geonameId',
        'isInEuropeanUnion',
        'isoCode',

        /*
         * @deprecated
         */
        'isHighRisk',
        'names',
    ];
}
