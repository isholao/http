<?php

namespace Isholao\Http;

/**
 * @author Ishola O<ishola.tolu@outlook.com>
 */
class ServerRequest implements \Psr\Http\Message\ServerRequestInterface
{

    use MessageTrait;
    use RequestTrait;

    /**
     * The request method
     *
     * @var string
     */
    protected $method;

    /**
     * The original request method (ignoring override)
     *
     * @var string
     */
    protected $originalMethod;

    /**
     * The request URI object
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * The request URI target
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * The request query string params
     *
     * @var array
     */
    protected $queryParams;

    /**
     * The request cookies
     *
     * @var array
     */
    protected $cookiesData;

    /**
     * The server environment variables at the time the request was created.
     *
     * @var \Isholao\Collection\CollectionInterface
     */
    protected $server;

    /**
     * The request attributes (route segment names and values)
     *
     * @var \Isholao\Collection\CollectionInterface
     */
    protected $attributes;

    /**
     * The request body parsed (if possible) into a PHP array or object
     *
     * @var NULL|array|object
     */
    protected $bodyParsed = NULL;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     *
     * @var callable[]
     */
    protected $bodyParsers = [];

    /**
     * List of uploaded files
     *
     * @var UploadedFileInterface[]
     */
    protected $uploadedFiles;

    /**
     * Valid request methods
     *
     * @var string[]
     */
    protected $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
        'LINK' => 1,
        'UNLINK' => 1,
    ];

    /**
     * @param string    $method   HTTP method
     * @param \Psr\Http\Message\UriInterface $uri URI
     * @param HeadersInterface $headers Request headers
     * @param \Psr\Http\Message\StreamInterface $body Request body
     * @param string  $version Protocol version
     * @param array $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(string $method,
                                \Psr\Http\Message\UriInterface $uri,
                                HeadersInterface $headers, array $cookies,
                                array $serverParams,
                                \Psr\Http\Message\StreamInterface $body,
                                array $uploadedFiles = [])
    {

        try
        {
            $this->originalMethod = $this->filterMethod($method);
        } catch (Throwable $e)
        {
            $e = null;
            $this->originalMethod = $method;
        }

        $this->server = new \Isholao\Collection\Collection($serverParams);
        $this->cookiesData = $cookies;
        $this->uploadedFiles = self::normalizeFiles($uploadedFiles);
        $this->method = \strtoupper($method);
        $this->uri = $uri;
        $this->attributes = new \Isholao\Collection\Collection();
        $this->headers = $headers;
        $this->body = $body;

        $this->protocol = \str_replace('HTTP/', '',
                                       $this->server->get('SERVER_PROTOCOL', ''));

        if (!$this->headers->has('Host') || $this->uri->getHost() !== '')
        {
            $this->headers->set('Host', $this->uri->getHost());
        }

        $xmlMediatTypeParser = \Closure::bind(function (string $input)
                {
                    $backup = \libxml_disable_entity_loader(true);
                    $result = \simplexml_load_string($input);
                    \libxml_disable_entity_loader($backup);
                    return $result;
                }, NULL);

        $this->registerMediaTypeParser('application/xml', $xmlMediatTypeParser);
        $this->registerMediaTypeParser('text/xml', $xmlMediatTypeParser);
        $this->registerMediaTypeParser('application/x-www-form-urlencoded',
                                       \Closure::bind(function (string $input)
                {
                    $data = [];
                    \parse_str($input, $data);
                    return $data;
                }, NULL));
        $this->registerMediaTypeParser('application/json',
                                       \Closure::bind(function (string $input)
                {
                    return \json_decode($input, true);
                }, NULL));
    }

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return ServerRequest
     */
    public static function fromGlobal(array $data): ServerRequest
    {
        $method = $data['REQUEST_METHOD'] ?? 'GET';
        $uri = Uri::fromGlobal($data);
        $body = new Stream('php://temp');
        $body->write(\stream_get_contents(\fopen('php://input', 'r')));
        $body->rewind();
        $headers = Headers::fromGlobal($data);
        $cookies = $headers->get('Cookie', []);
        $request = new ServerRequest($method, $uri, $headers,
                                     Cookie::parseHeader($cookies[0] ?? ''),
                                                         $data, $body, $_FILES);
        return $request->withQueryParams($_GET)->withParsedBody($_POST);
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body = clone $this->body;
    }

    /**
     * Fetch parameter value from request body.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParsedBodyParam($key, $default = NULL)
    {
        $params = $this->getParsedBody();
        $result = $default;
        if (\is_array($params) && isset($params[$key]))
        {
            $result = $params[$key];
        } elseif (\is_object($params) && \property_exists($params, $key))
        {
            $result = $params->$key;
        }
        return $result;
    }

    /**
     * Get the original HTTP method (ignore override).
     *
     *
     * @return string
     */
    public function getOriginalMethod()
    {
        return $this->originalMethod;
    }

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     * @return null|string
     * @throws InvalidArgumentException on invalid HTTP method.
     */
    private function filterMethod(string $method)
    {
        $method = \strtoupper($method);
        if (!isset($this->validMethods[$method]))
        {
            throw new \Error('Invalid Http Method - ' . $method . '.');
        }
        return $method;
    }

    /**
     * Does this request use a given method?
     *
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === $method;
    }

    /**
     * Is this a GET request?
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this an XHR request?
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files A array which respect $_FILES structure
     * @throws InvalidArgumentException for unrecognized values
     * @return array
     */
    public static function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value)
        {
            if ($value instanceof \Psr\Http\Message\UploadedFileInterface)
            {
                $normalized[$key] = $value;
            } elseif (\is_array($value) && isset($value['tmp_name']))
            {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (\is_array($value))
            {
                $normalized[$key] = self::normalizeFiles($value);
                continue;
            } else
            {
                throw new \InvalidArgumentException('Invalid value in files specification');
            }
        }
        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    private static function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name']))
        {
            return self::normalizeNestedFileSpec($value);
        }
        return new UploadedFile($value['tmp_name'], (int) $value['size'],
                                (int) $value['error'], $value['name'] ?? NULL,
                                $value['type'] ?? NULL);
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * \Psr\Http\Message\UriInterface instances.
     *
     * @param array $files
     * @return \Psr\Http\Message\UriInterface[]
     */
    private static function normalizeNestedFileSpec(array $files): array
    {
        $normalizedFiles = [];
        foreach (\array_keys($files['tmp_name']) as $key)
        {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }
        return $normalizedFiles;
    }

    /**
     * Retrieve a server parameter.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getServerParam(string $key, $default = NULL)
    {
        return $this->server->get($key, $default);
    }

    /**
     * Get request content type.
     *
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        return $this->getHeader('Content-Type')[0] ?? NULL;
    }

    /**
     * Get request content length, if known.
     *
     * @return int|null
     */
    public function getContentLength()
    {
        return $this->getHeader('Content-Length')[0] ?? NULL;
    }

    /**
     * Fetch cookie value from cookies sent by the client to the server.
     *
     * @param string $key     The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     *
     * @return mixed
     */
    public function getCookieParam(string $key, $default = NULL)
    {
        return $this->getCookieParams()[$key] ?? $default;
    }

    /**
     * Create a new instance with the specified derived request attributes.
     *
     * This method allows setting all new derived request attributes as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attributes.
     *
     * @param  array $attributes New attributes
     * @return static
     */
    public function withAttributes(array $attributes)
    {
        $clone = clone $this;
        $clone->attributes = new Collection($attributes);
        return $clone;
    }

    /**
     * Fetch parameter value from query string.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getQueryParam(string $key, $default = NULL)
    {
        if (empty($key))
        {
            throw new \InvalidArgumentException('Query param cannot be empty.');
        }
        return $this->getQueryParams()[$key] ?? $default;
    }

    /**
     * Fetch array of body and query string parameters.
     *
     * @return \Isholao\Collection\CollectionInterface
     */
    public function getParams(): \Isholao\Collection\CollectionInterface
    {
        static $data = NULL;
        if ($data == NULL)
        {
            $data = new \Isholao\Collection\Collection(['GET' => $this->getQueryParams(),
                'POST' => (array) $this->getParsedBody()]);
        }

        return $data;
    }

    /**
     * Force Body to be parsed again.
     *
     * @return $this
     */
    public function reparseBody(): ServerRequest
    {
        $this->bodyParsed = FALSE;
        return $this;
    }

    /**
     * Register media type parser.
     *
     * @param string   $mediaType A HTTP media type (excluding content-type
     *     params).
     * @param callable $callable  A callable that returns parsed contents for
     *     media type.
     */
    public function registerMediaTypeParser(string $mediaType,
                                            callable $callable): ServerRequest
    {
        $this->bodyParsers[$mediaType] = $callable;
        return $this;
    }

    /**
     * Get request media type, if known.
     *
     * @return string|NULL The request media type, minus content-type params
     */
    public function getMediaType()
    {
        $type = $this->getContentType();
        if ($type)
        {
            $parts = \preg_split('#\s*[;,]\s*#', $type);
            return \strtolower($parts[0]);
        }
        return NULL;
    }

    /**
     * Get request media type params, if known.
     *
     * @return array
     */
    public function getMediaTypeParams(): array
    {
        $type = $this->getContentType();
        $params = [];
        if ($type)
        {
            $parts = \preg_split('#\s*[;,]\s*#', $type);
            $count = \count($parts);
            for ($i = 1; $i < $count; $i++)
            {
                $paramParts = \explode('=', $parts[$i]);
                $params[\strtolower($paramParts[0])] = $paramParts[1];
            }
        }
        return $params;
    }

    /**
     * Get request content character set, if known.
     *
     * @return string|NULL
     */
    public function getContentCharset()
    {
        return $this->getMediaTypeParams()['charset'] ?? NULL;
    }

    /**
     * Extract "login" and "password" credentials from HTTP-request
     *
     * Returns plain array with 2 items: login and password respectively
     *
     * @return array
     */
    public function getCredentials(): array
    {
        $auth = $this->server->get('HTTP_AUTHORIZATION', '');
        $server = $auth = $this->server->toArray();
        $user = '';
        $pass = '';

        if (empty($auth))
        {
            foreach ($server as $k => $v)
            {
                if (\substr($k, -18) === \strtolower('HTTP_AUTHORIZATION') && !empty($v))
                {
                    $this->server->set('HTTP_AUTHORIZATION', $v);
                    break;
                }
            }
        }

        if ($this->server->has($server['PHP_AUTH_USER']) && $this->server->has($server['PHP_AUTH_PW']))
        {
            $user = $this->server->get('PHP_AUTH_USER');
            $pass = $this->server->get('PHP_AUTH_PW');
        } elseif (!empty($auth))
        {
            list($user, $pass) = \explode(':',
                                          \base64_decode(\substr($auth,
                                                                 \strpos($auth,
                                                                         ' ') + 1)));
        } elseif ($this->server->has('Authorization') && !empty($this->server->get('Authorization')))
        {
            $auth = $this->server->get('Authorization', '');
            list($user, $pass) = \explode(':',
                                          \base64_decode(\substr($auth,
                                                                 \strpos($auth,
                                                                         ' ') + 1)));
        }

        return [$user, $pass];
    }

}
