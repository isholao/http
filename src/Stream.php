<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
class Stream implements \Psr\Http\Message\StreamInterface
{

    /**
     * Resource modes
     *
     * @var  array
     * @link http://php.net/manual/function.fopen.php
     */
    protected static $modes = [
        'readable' => ['rb', 'r+b', 'w+b', 'a+b', 'x+b', 'c+b'],
        'writable' => ['r+b', 'wb', 'w+b', 'ab', 'a+b', 'xb', 'x+b', 'cb', 'c+b'],
    ];
    protected $mode;
    protected $uri;

    /**
     * The underlying stream resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Stream metadata
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * @var null|int
     */
    protected $size;

    /**
     * Create a new Stream.
     * 
     * @param string $uri valid fopen uri path
     * @param string $mode fopen file mode
     */
    public function __construct(string $uri = 'php://temp', string $mode = 'r+b')
    {
        $this->uri = $uri;
        $this->mode = $mode;
    }

    /**
     * Attach new resource to this object.
     *
     *
     * @param resource $stream A PHP resource handle.
     *
     * @throws InvalidArgumentException If argument is not a valid PHP resource.
     */
    protected function attach(): Stream
    {
        if (\is_resource($this->stream))
        {
            return $this;
        }
        $stream = \fopen($this->uri, $this->mode);
        if (\is_resource($stream))
        {
            $this->stream = $stream;
            return $this;
        }
        throw new \InvalidArgumentException('Stream must be a valid PHP resource uri and must be a local stream.');
    }

    /**
     * Is a resource attached to this stream?
     *
     * @return bool
     */
    protected function isAttached(): bool
    {
        return \is_resource($this->stream);
    }

    public function __destruct()
    {
        if ($this->isAttached())
        {
            \fclose($this->stream);
        }
    }

    public function getMetadata($key = null)
    {
        if (!$this->isAttached())
        {
            $this->attach();
        }

        $this->meta = \stream_get_meta_data($this->stream);
        if ($key === null)
        {
            return $this->meta;
        }
        return $this->meta[$key] ?? null;
    }

    public function detach()
    {
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;
        $this->uri = $this->mode = null;
    }

    /**
     * Closes the stream and any underlying resources.
     */
    public function close()
    {
        if ($this->isAttached())
        {
            \fclose($this->stream);
        }
        $this->detach();
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->isAttached())
        {
            $this->attach();
        }
        // Clear the stat cache if the stream has a URI
        \clearstatcache(true, $this->uri);
        $stats = \fstat($this->stream);
        return $this->size = (int) $stats['size'] ?? null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     *
     * @throws RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isAttached())
        {
            $this->attach();
        }

        if (($position = \ftell($this->stream)) !== false)
        {
            return $position;
        }
        throw new \RuntimeException('Could not get the position of the pointer in stream');
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (!$this->isAttached())
        {
            $this->attach();
        }

        if (($eof = \feof($this->stream)) === false)
        {
            return false;
        }

        return $eof;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if ($this->readable === null)
        {
            $meta = $this->getMetadata();
            foreach (self::$modes['readable'] as $mode)
            {
                if (\strpos($meta['mode'], $mode) === 0)
                {
                    $this->readable = true;
                    break;
                }
            }
        }

        return $this->readable;
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if ($this->writable === null)
        {
            $this->writable = false;
            $meta = $this->getMetadata();
            foreach (self::$modes['writable'] as $mode)
            {
                if (\strpos($meta['mode'], $mode) === 0)
                {
                    $this->writable = true;
                    break;
                }
            }
        }
        return $this->writable;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if ($this->seekable === null)
        {
            $this->seekable = false;
            $meta = $this->getMetadata();
            $this->seekable = $meta['seekable'];
        }
        return (bool) $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        // Note that fseek returns 0 on success!
        if (!$this->isSeekable() || \fseek($this->stream, $offset, $whence) === -1)
        {
            throw new \RuntimeException('Could not seek in stream');
        }
    }

    public function rewind()
    {
        if (!$this->isSeekable() || \rewind($this->stream) === false)
        {
            throw new \RuntimeException('Could not rewind stream');
        }
    }

    public function read($length)
    {
        if (!$this->isReadable() || ($data = \stream_get_contents($this->stream,
                                                                  $length)) === false)
        {
            throw new \RuntimeException('Could not read from stream');
        }
        return $data;
    }

    public function write($string)
    {
        if (!$this->isWritable() || ($written = \fwrite($this->stream, $string)) === false)
        {
            throw new \RuntimeException('Could not write to stream');
        }
        // reset size so that it will be recalculated on next call to getSize()
        $this->size = null;
        return $written;
    }

    public function getContents()
    {
        if (!$this->isReadable() || ($contents = \stream_get_contents($this->stream)) === false)
        {
            throw new \RuntimeException('Could not get contents of stream');
        }
        return $contents;
    }

    public function __toString()
    {
        if (!$this->isAttached())
        {
            return '';
        }
        try
        {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e)
        {
            return '';
        }
    }

}
