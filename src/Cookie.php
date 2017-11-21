<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
class Cookie
{

    private $name;
    private $value;
    private $domain;
    private $expire;
    private $path;
    private $secure;
    private $httpOnly;
    private $raw;
    private $sameSite;

    private const SAMESITE_LAX = 'lax';
    private const SAMESITE_STRICT = 'strict';

    /**
     * Creates cookie from raw header string.
     *
     * @param string $cookie
     * @param bool   $decode
     *
     * @return static
     */
    public static function fromString(string $cookie, bool $decode = FALSE)
    {
        if (empty($cookie))
        {
            throw new \InvalidArgumentException("Cookie cannot be empty.");
        }
        $data = [
            'expires' => 0,
            'path' => '/',
            'domain' => NULL,
            'secure' => FALSE,
            'httponly' => TRUE,
            'raw' => !$decode,
            'samesite' => NULL,
        ];
        foreach (\explode(';', $cookie) as $part)
        {
            if (FALSE === \strpos($part, '='))
            {
                $key = \trim($part);
                $value = TRUE;
            } else
            {
                list($key, $value) = \explode('=', \trim($part), 2);
                $key = \trim($key);
                $value = \trim($value);
            }
            if (!isset($data['name']))
            {
                $data['name'] = $decode ? \urldecode($key) : $key;
                $data['value'] = (TRUE === $value ? NULL : ($decode ? \urldecode($value) : $value));
                continue;
            }
            switch ($key = \strtolower($key))
            {
                case 'name':
                case 'value':
                    break;
                case 'max-age':
                    $data['expires'] = \time() + (int) $value;
                    break;
                default:
                    $data[$key] = $value;
                    break;
            }
        }
        return new Cookie($data['name'], $data['value'], $data['expires'],
                          $data['path'], $data['domain'], $data['secure'],
                          $data['httponly'], $data['raw'], $data['samesite']);
    }

    /**
     *
     * @param string                        $name     The name of the cookie
     * @param ?string                   $value    The value of the cookie
     * @param int $expire   The time the cookie expires
     * @param string                        $path     The path on the server in which the cookie will be available on
     * @param ?string                   $domain   The domain that the cookie is available to
     * @param bool                          $secure   Whether the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param bool                          $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @param bool                          $raw      Whether the cookie value should be sent with no url encoding
     * @param ?string                   $sameSite Whether the cookie will be available for cross-site requests
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, ?string $value = NULL,
                                int $expire = 0, string $path = '/',
                                ?string $domain = NULL, bool $secure = FALSE,
                                bool $httpOnly = TRUE, bool $raw = FALSE,
                                ?string $sameSite = NULL)
    {
        // from PHP source code
        if (\preg_match('#[=,; \t\r\n\013\014]#', $name))
        {
            throw new \InvalidArgumentException('The cookie name `$name` contains invalid characters.');
        }
        if (empty($name))
        {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        $this->name = $name;
        $this->value = \is_NULL($value) ? '' : $value;
        $this->domain = $domain;
        $this->expire = $expire ? \time() + $expire : 0;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = (bool) $secure;
        $this->httpOnly = (bool) $httpOnly;
        $this->raw = (bool) $raw;
        if (!\in_array($sameSite,
                       [self::SAMESITE_LAX, self::SAMESITE_STRICT, NULL], TRUE))
        {
            throw new \InvalidArgumentException('The `samesite` parameter value is not valid.');
        }
        $this->sameSite = $sameSite;
    }

    /**
     * Returns the cookie as a string.
     *
     * @return string The cookie
     */
    public function __toString(): string
    {
        $str = ($this->raw ? $this->name : \urlencode($this->name)) . '=';
        if ('' === $this->value)
        {
            $str .= 'deleted; expires=' . \gmdate('D, d-M-Y H:i:s T',
                                                  \time() - 31536001) . '; max-age=-31536001';
        } else
        {
            $str .= $this->raw ? $this->value : \urlencode($this->value);
            if (0 !== $this->expire)
            {
                $str .= '; expires=' . \gmdate('D, d-M-Y H:i:s T',
                                               \time() - $this->expire) . '; max-age=' . $this->getMaxAge();
            }
        }

        if ($this->path)
        {
            $str .= '; path=' . $this->path;
        }
        if ($this->domain)
        {
            $str .= '; domain=' . $this->domain;
        }
        if (TRUE === $this->secure)
        {
            $str .= '; secure';
        }
        if (TRUE === $this->httpOnly)
        {
            $str .= '; httponly';
        }
        if (NULL !== $this->sameSite)
        {
            $str .= '; samesite=' . $this->sameSite;
        }
        return $str;
    }

    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the value of the cookie.
     *
     * @return string|NULL
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Gets the domain that the cookie is available to.
     *
     * @return string|NULL
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Gets the time the cookie expires.
     *
     * @return int
     */
    public function getExpiresTime(): int
    {
        return $this->expire;
    }

    /**
     * Gets the max-age attribute.
     *
     * @return int
     */
    public function getMaxAge(): int
    {
        return (0 !== $this->expire) ? $this->expire - \time() : 0;
    }

    /**
     * Gets the path on the server in which the cookie will be available on.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Whether this cookie is about to be cleared.
     *
     * @return bool
     */
    public function isCleared(): bool
    {
        return $this->expire < \time();
    }

    /**
     * Checks if the cookie value should be sent with no url encoding.
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * Gets the same_site attribute.
     *
     * @return string|NULL
     */
    public function getSameSite()
    {
        return $this->sameSite;
    }

    /**
     * Parse HTTP request `Cookie:` header and extract
     * into a PHP associative array.
     *
     * @param  string $header The raw HTTP request `Cookie:` header
     *
     * @return array Associative array of cookie names and values
     */
    public static function parseHeader(string $header): array
    {
        if (empty($header))
        {
            return [];
        }

        $pieces = \preg_split('#[;]\s*#', \rtrim($header, "\r\n"));
        $cookies = [];
        foreach ($pieces as $header)
        {
            $cookie = self::fromString($header);
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        return $cookies;
    }

}
