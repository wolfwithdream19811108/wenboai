<?php

namespace plugins\site_analysis\lib\MaxMind\Exception;

/**
 * Thrown when a plugins\site_analysis\lib\MaxMind web service returns an error relating to the request.
 */
class InvalidRequestException extends HttpException
{
    /**
     * The code returned by the plugins\site_analysis\lib\MaxMind web service.
     */
    private $error;

    /**
     * @param string     $message    the exception message
     * @param int        $error      the error code returned by the plugins\site_analysis\lib\MaxMind web service
     * @param int        $httpStatus the HTTP status code of the response
     * @param string     $uri        the URI queries
     * @param \Exception $previous   the previous exception, if any
     */
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

    public function getErrorCode()
    {
        return $this->error;
    }
}
