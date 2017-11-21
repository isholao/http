<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
class Response implements \Psr\Http\Message\ResponseInterface
{

    use MessageTrait;

    /* Http Status Code, http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml */

    const HTTP_STATUS_CONTINUE = 100;
    const HTTP_STATUS_SWITCHING_PROTOCOLS = 101;
    const HTTP_STATUS_PROCESSING = 102;
    const HTTP_STATUS_OK = 200;
    const HTTP_STATUS_CREATED = 201;
    const HTTP_STATUS_ACCEPTED = 202;
    const HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_STATUS_NO_CONTENT = 204;
    const HTTP_STATUS_RESET_CONTENT = 205;
    const HTTP_STATUS_PARTIAL_CONTENT = 206;
    const HTTP_STATUS_MULTI_STATUS = 207;
    const HTTP_STATUS_ALREADY_REPORTED = 208;
    const HTTP_STATUS_IM_USED = 226;
    const HTTP_STATUS_MULTIPLE_CHOICES = 300;
    const HTTP_STATUS_MOVED_PERMANENTLY = 301;
    const HTTP_STATUS_FOUND = 302;
    const HTTP_STATUS_SEE_OTHER = 303;
    const HTTP_STATUS_NOT_MODIFIED = 304;
    const HTTP_STATUS_USE_PROXY = 305;
    const HTTP_STATUS_SWITCH_PROXY = 306; // Deprecated
    const HTTP_STATUS_TEMPORARY_REDIRECT = 307;
    const HTTP_STATUS_PERMANENT_REDIRECT = 308;
    const HTTP_STATUS_BAD_REQUEST = 400;
    const HTTP_STATUS_UNAUTHORIZED = 401;
    const HTTP_STATUS_PAYMENT_REQUIRED = 402;
    const HTTP_STATUS_FORBIDDEN = 403;
    const HTTP_STATUS_NOT_FOUND = 404;
    const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_NOT_ACCEPTABLE = 406;
    const HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_STATUS_REQUEST_TIMEOUT = 408;
    const HTTP_STATUS_CONFLICT = 409;
    const HTTP_STATUS_GONE = 410;
    const HTTP_STATUS_LENGTH_REQUIRED = 411;
    const HTTP_STATUS_PRECONDITION_FAILED = 412;
    const HTTP_STATUS_PAYLOAD_TOO_LARGE = 413;
    const HTTP_STATUS_URI_TOO_LONG = 414;
    const HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_STATUS_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_STATUS_EXPECTATION_FAILED = 417;
    const HTTP_STATUS_I_AM_A_TEOPOT = 418;
    const HTTP_STATUS_MISDIRECTED_REQUEST = 421;
    const HTTP_STATUS_UNPROCESSABLE_ENTITY = 422;
    const HTTP_STATUS_LOCKED = 423;
    const HTTP_STATUS_FAILED_DEPENDENCY = 424;
    const HTTP_STATUS_UNORDERED_COLLECTION = 425;
    const HTTP_STATUS_UPGRADE_REQUIRED = 426;
    const HTTP_STATUS_PRECONDITION_REQUIRED = 428;
    const HTTP_STATUS_TOO_MANY_REQUESTS = 429;
    const HTTP_STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const HTTP_STATUS_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    const HTTP_STATUS_NOT_IMPLEMENTED = 501;
    const HTTP_STATUS_BAD_GATEWAY = 502;
    const HTTP_STATUS_SERVICE_UNAVAILABLE = 503;
    const HTTP_STATUS_GATEWAY_TIMEOUT = 504;
    const HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_STATUS_VARIANT_ALSO_NEGOTIATES = 506;
    const HTTP_STATUS_INSUFFICIENT_STORAGE = 507;
    const HTTP_STATUS_LOOP_DETECTED = 508;
    const HTTP_STATUS_NOT_EXTENDED = 510;
    const HTTP_STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /** @var array Map of standard HTTP status code/reason phrases */
    private static $phrases = [
        self::HTTP_STATUS_CONTINUE => 'Continue',
        self::HTTP_STATUS_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::HTTP_STATUS_PROCESSING => 'Processing',
        self::HTTP_STATUS_OK => 'OK',
        self::HTTP_STATUS_CREATED => 'Created',
        self::HTTP_STATUS_ACCEPTED => 'Accepted',
        self::HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::HTTP_STATUS_NO_CONTENT => 'No Content',
        self::HTTP_STATUS_RESET_CONTENT => 'Reset Content',
        self::HTTP_STATUS_PARTIAL_CONTENT => 'Partial Content',
        self::HTTP_STATUS_MULTI_STATUS => 'Multi-status',
        self::HTTP_STATUS_ALREADY_REPORTED => 'Already Reported',
        self::HTTP_STATUS_MULTIPLE_CHOICES => 'Multiple Choices',
        self::HTTP_STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::HTTP_STATUS_FOUND => 'Found',
        self::HTTP_STATUS_SEE_OTHER => 'See Other',
        self::HTTP_STATUS_NOT_MODIFIED => 'Not Modified',
        self::HTTP_STATUS_USE_PROXY => 'Use Proxy',
        self::HTTP_STATUS_SWITCH_PROXY => 'Switch Proxy',
        self::HTTP_STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::HTTP_STATUS_BAD_REQUEST => 'Bad Request',
        self::HTTP_STATUS_UNAUTHORIZED => 'Unauthorized',
        self::HTTP_STATUS_PAYMENT_REQUIRED => 'Payment Required',
        self::HTTP_STATUS_FORBIDDEN => 'Forbidden',
        self::HTTP_STATUS_NOT_FOUND => 'Not Found',
        self::HTTP_STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::HTTP_STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
        self::HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::HTTP_STATUS_REQUEST_TIMEOUT => 'Request Time-out',
        self::HTTP_STATUS_CONFLICT => 'Conflict',
        self::HTTP_STATUS_GONE => 'Gone',
        self::HTTP_STATUS_LENGTH_REQUIRED => 'Length Required',
        self::HTTP_STATUS_PRECONDITION_FAILED => 'Precondition Failed',
        self::HTTP_STATUS_PAYLOAD_TOO_LARGE => 'Payload Too Large',
        self::HTTP_STATUS_URI_TOO_LONG => 'Request-URI Too Large',
        self::HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::HTTP_STATUS_RANGE_NOT_SATISFIABLE => 'Requested range not satisfiable',
        self::HTTP_STATUS_EXPECTATION_FAILED => 'Expectation Failed',
        self::HTTP_STATUS_I_AM_A_TEOPOT => 'I\'m a teapot',
        self::HTTP_STATUS_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        self::HTTP_STATUS_LOCKED => 'Locked',
        self::HTTP_STATUS_FAILED_DEPENDENCY => 'Failed Dependency',
        self::HTTP_STATUS_UNORDERED_COLLECTION => 'Unordered Collection',
        self::HTTP_STATUS_UPGRADE_REQUIRED => 'Upgrade Required',
        self::HTTP_STATUS_PRECONDITION_REQUIRED => 'Precondition Required',
        self::HTTP_STATUS_TOO_MANY_REQUESTS => 'Too Many Requests',
        self::HTTP_STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::HTTP_STATUS_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        self::HTTP_STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::HTTP_STATUS_NOT_IMPLEMENTED => 'Not Implemented',
        self::HTTP_STATUS_BAD_GATEWAY => 'Bad Gateway',
        self::HTTP_STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::HTTP_STATUS_GATEWAY_TIMEOUT => 'Gateway Time-out',
        self::HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version not supported',
        self::HTTP_STATUS_VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
        self::HTTP_STATUS_INSUFFICIENT_STORAGE => 'Insufficient Storage',
        self::HTTP_STATUS_LOOP_DETECTED => 'Loop Detected',
        self::HTTP_STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
    ];

    /** @var string */
    private $reasonPhrase;

    /** @var int */
    private $statusCode = self::HTTP_STATUS_OK;

    /**
     * @param int  $status  Status code
     */
    public function __construct(int $status = self::HTTP_STATUS_OK)
    {
        $this->statusCode = $status;
        $this->headers = new Headers();
        $this->reasonPhrase = self::$phrases[$status];
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * Filter HTTP status code.
     *
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    private function filterStatus(int $status): int
    {
        if ($status < 100 || $status > 599)
        {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }
        return $status;
    }

    /**
     * Write data to the response body.
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     * @return $this
     */
    public function write(string $data): Response
    {
        $this->getBody()->write($data);
        return $this;
    }

    /**
     * Write json data to the response body.
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param mixed $data
     * @return $this
     */
    public function writeJson($data): Response
    {
        $new = $this->withHeader('Content-Type', 'application/json');
        $new->getBody()->write(\json_encode($data, JSON_PRETTY_PRINT));
        return $new;
    }

    /**
     * Redirect.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param  string   The redirect destination.
     * @param  bool            $permanent The redirect HTTP status code.
     * @return static
     */
    public function withRedirect(string $url, bool $permanent = FALSE): Response
    {
        return $this->withHeader('Location', (string) $url)
                        ->withStatus($permanent ? 301 : 303);
    }

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return \in_array($this->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Is this response a redirect?
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return \in_array($this->getStatusCode(), [301, 302, 303, 307]);
    }

    /**
     * Is this response forbidden?
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response not Found?
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a client error?
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Convert response to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $headerName = function(string $name)
        {
            $header = [];
            $parts = \explode('-', $name);
            foreach ($parts as $part)
            {
                $header[] = \ucwords(\strtolower($part));
            }
            return \implode('-', $header);
        };

        $output = "HTTP/{$this->getProtocolVersion()} {$this->getStatusCode()} {$this->getReasonPhrase()}\r\n";
        $output .= "\r\n";
        foreach ($this->getHeaders() as $name => &$values)
        {
            $values = NULL;
            if ($name == \strtolower('Set-Cookie'))
            {
                $cookies = $this->getHeader('Set-Cookie');
                foreach ($cookies as $cookie)
                {
                    $output .= "Set-Cookie: $cookie\r\n";
                }
            } else
            {
                $output .= \sprintf('%s: %s', $headerName($name),
                                                          $this->getHeaderLine($name)) . "\r\n";
            }
        }
        $output .= "\r\n";
        $output .= (string) $this->getBody();
        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        if ($this->reasonPhrase)
        {
            return $this->reasonPhrase;
        }
        if (isset(static::$phrases[$this->statusCode]))
        {
            return static::$phrases[$this->statusCode];
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $code = $this->filterStatus($code);
        if (!\is_string($reasonPhrase))
        {
            throw new \InvalidArgumentException('ReasonPhrase must be a string');
        }

        $new = clone $this;
        $new->statusCode = (int) $code;
        if ($reasonPhrase == '' && isset(self::$phrases[$code]))
        {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }

        if ($reasonPhrase === '')
        {
            throw new \InvalidArgumentException('Reason phrase must be supplied for this code');
        }

        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

}
