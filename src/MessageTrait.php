<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
trait MessageTrait
{

    /** @var Collection Map of all registered headers */
    protected $headers;

    /** @var string */
    protected $protocol = '1.1';

    /**
     * A map of valid protocol versions
     *
     * @var array
     */
    protected static $validProtocolVersions = [
        '1.0' => TRUE,
        '1.1' => TRUE,
        '2.0' => TRUE,
    ];

    /** @var StreamInterface */
    protected $body;

    /**
     * Disable magic setter to ensure immutability
     */
    public function __set($name, $value)
    {
        // Do nothing
    }

    public function getBody(): \Psr\Http\Message\StreamInterface
    {
        if ($this->body === NULL)
        {
            $this->body = new Stream('php://temp');
        }
        return $this->body;
    }

    public function getHeader($name)
    {
        return $this->headers->get($name, []);
    }

    public function getHeaderLine($name)
    {
        return \implode(',', $this->headers->get($name, []));
    }

    public function getHeaders()
    {
        return $this->headers->all();
    }

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);
        return $clone;
    }

    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        if ($body === $this->body)
        {
            return $this;
        }
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);
        return $clone;
    }

    public function withProtocolVersion($version)
    {

        if (!isset(self::$validProtocolVersions[$version]))
        {
            throw new \InvalidArgumentException(
            'Invalid HTTP version. Must be one of: `'
            . \implode('`, `', \array_keys(self::$validProtocolVersions).'`')
            );
        }

        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);
        return $clone;
    }

}
