<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
class Headers implements HeadersInterface
{

    private $headers;

    public function __construct()
    {
        $this->headers = new \Isholao\Collection\Collection([]);
    }

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    const HTTP_HEADERS = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];

    /**
     * Create new headers collection with data extracted from
     * the application Environment object
     *
     * @return HeadersInterface
     */
    public static function fromGlobal(array $data): HeadersInterface
    {
        $headers = new Headers();
        foreach ($data as $key => $value)
        {
            if ($key == 'argv')
            {
                continue;
            }
            if (\substr($key, 0, 5) == 'HTTP_')
            {
                $headers->set(\substr($key, 5), $value);
            } else
            {
                $headers->set($key, $value);
            }
        }
        return $headers;
    }

    /**
     * Return array of HTTP header names and values.
     * This method returns the original header name
     * as specified by the end user.
     *
     * @return array
     */
    public function all(): array
    {
        $out = [];
        foreach ($this->headers->all() as $key => $props)
        {
            $out[$props['originalName']] = \implode(',', $props['value']);
        }
        return $out;
    }

    /**
     * Set HTTP header value
     *
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     *
     * @param string $key   The case-insensitive header name
     * @param string $value The header value
     */
    public function set(string $key, $value)
    {
        if (empty($key))
        {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }
        if ($key == \strtolower('set-cookie'))
        {
            $value = (string) Cookie::fromString($value);
        }

        return $this->headers->set($this->normalizeKey($key),
                                               [
                            'originalName' => $key,
                            'value' => [(string) $value]
        ]);
    }

    /**
     * Get HTTP header value
     *
     * @param  string  $key     The case-insensitive header name
     * @param  mixed   $default The default value if key does not exist
     *
     * @return string[]
     */
    public function get(string $key, $default = null)
    {
        if (empty($key))
        {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }
        if ($this->has($key))
        {
            return $this->headers->get($this->normalizeKey($key))['value'];
        }
        return $default;
    }

    /**
     * Add HTTP header value
     *
     * This method appends a header value. Unlike the set() method,
     * this method _appends_ this new value to any values
     * that already exist for this header name.
     *
     * @param string       $key   The case-insensitive header name
     * @param array|string $value The new header value(s)
     */
    public function add(string $key, $value)
    {
        if (empty($key))
        {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }
        $header = $this->get($key, []);
        if ($key === \strtolower('set-cookie'))
        {
            $value = (string) Cookie::fromString($value);
        }
        $new = \array_merge($header, \array_values([$value]));
        return $this->headers->set($key, ['value' => $new]);
    }

    /**
     * Does this collection have a given header?
     *
     * @param  string $key The case-insensitive header name
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->headers->has($this->normalizeKey($key));
    }

    /**
     * Remove header from collection
     *
     * @param  string $key The case-insensitive header name
     */
    public function remove(string $key): void
    {
        $this->headers->remove($this->normalizeKey($key));
    }

    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $key The case-insensitive header name
     *
     * @return string Normalized header name
     */
    public function normalizeKey(string $key): string
    {
        if (empty($key))
        {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }

        if (\strpos($key, 'HTTP_') === 0)
        {
            $key = \substr($key, 5);
        }
        return \strtolower($key);
    }

}
