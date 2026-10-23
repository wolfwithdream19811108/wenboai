<?php

namespace plugins\site_analysis\lib\GeoIp2\Model;

/**
 * Model class for the data returned by plugins\site_analysis\lib\GeoIp2 Enterprise database lookups.
 *
 * The only difference between the City and Enterprise model classes is which
 * fields in each record may be populated. See
 * https://dev.plugins\site_analysis\lib\MaxMind.com/geoip/plugins\site_analysis\lib\GeoIp2/web-services for more details.
 */
class Enterprise extends City
{
}
