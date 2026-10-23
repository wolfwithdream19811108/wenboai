<?php

namespace plugins\site_analysis\lib\GeoIp2\Exception;

/**
 * This class represents an error returned by plugins\site_analysis\lib\MaxMind's plugins\site_analysis\lib\GeoIp2
 * web service.
 */
class InvalidRequestException extends HttpException
{
    /**
     * The code returned by the plugins\site_analysis\lib\MaxMind web service.
     */
    public $error;

    public function __construct(
        $message,
        $error,
        $httpStatus,
        $uri,
        \Exception $previous = null
    ) {
        $this->error = $error;
        parent::__construct($message, $httpStatus, $uri, $previous);
    }
}
