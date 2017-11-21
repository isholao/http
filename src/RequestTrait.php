<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
trait RequestTrait
{

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        if ($this->method === NULL)
        {
            $this->method = $this->originalMethod;
            $customMethod = $this->server->find('X-HTTP-Method-Override', FALSE);
            if ($customMethod)
            {
                $this->method = $this->filterMethod($customMethod);
            } elseif ($this->originalMethod === 'POST')
            {
                $overrideMethod = $this->filterMethod($this->getParsedBodyParam('_METHOD'));
                if ($overrideMethod !== NULL)
                {
                    $this->method = $overrideMethod;
                }
                if ($this->getBody()->eof())
                {
                    $this->getBody()->rewind();
                }
            }
        }
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method case-insensitive method.
     * @return static
     * @throws InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method = $this->filterMethod($method);
        $clone = clone $this;
        $clone->originalMethod = $method;
        $clone->method = $method;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->server->toArray();
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== NULL)
        {
            return $this->requestTarget;
        }
        $target = $this->uri->getPath();
        if (empty($target))
        {
            $target = '/';
        }
        if ($this->uri->getQuery() != '')
        {
            $target .= '?' . $this->uri->getQuery();
        }
        return $target;
    }

    public function withRequestTarget($requestTarget)
    {
        if (\preg_match('#\s#', $requestTarget))
        {
            throw new \InvalidArgumentException(
                    'Invalid request target provided; cannot contain whitespace'
            );
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function withUri(\Psr\Http\Message\UriInterface $uri,
                            $preserveHost = FALSE)
    {
        $clone = clone $this;
        $clone->uri = $uri;
        if (!$preserveHost)
        {
            if ($uri->getHost() !== '')
            {
                $clone->headers->set('Host', $uri->getHost());
            }
        } else
        {
            if ($this->uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeader('Host') === NULL))
            {
                $clone->headers->set('Host', $uri->getHost());
            }
        }
        return $clone;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookiesData;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookiesData = $cookies;
        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if (\is_array($this->queryParams))
        {
            return $this->queryParams;
        }
        if ($this->uri === NULL)
        {
            return [];
        }
        \parse_str($this->uri->getQuery(), $this->queryParams);

        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return ICollection Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes->all();
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = NULL)
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);
        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);
        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A NULL value indicates
     * the absence of body content.
     *
     * @return NULL|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     * @throws RuntimeException if the request body media type parser returns an invalid value
     */
    public function getParsedBody()
    {
        if ($this->bodyParsed !== FALSE)
        {
            return $this->bodyParsed;
        }
        if (!$this->body)
        {
            return NULL;
        }
        $type = $this->getMediaType();
        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = \explode('+', $type);
        if (\count($parts) >= 2)
        {
            $type = 'application/' . $parts[\count($parts) - 1];
        }
        if (isset($this->bodyParsers[$type]) === TRUE)
        {
            $body = (string) $this->getBody();
            $parsed = $this->bodyParsers[$type]($body);
            if ($parsed !== NULL && !\is_object($parsed) && !\is_array($parsed))
            {
                throw new \RuntimeException(
                        'Request body media type parser return value must be an array, an object, or NULL'
                );
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }
        return NULL;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param NULL|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        if ($data !== NULL && !\is_object($data) && !\is_array($data))
        {
            throw new \InvalidArgumentException('Parsed body value must be an array, an object, or NULL');
        }
        $clone = clone $this;
        $clone->bodyParsed = $data;
        return $clone;
    }

}
