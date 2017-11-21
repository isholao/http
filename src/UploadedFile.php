<?php

namespace Isholao\Http;

use \Psr\Http\Message\StreamInterface;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
class UploadedFile implements \Psr\Http\Message\UploadedFileInterface
{

    /**
     * @var string
     */
    private $clientFilename;

    /**
     * @var string
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var bool
     */
    private $moved = FALSE;

    /**
     * @var int
     */
    private $size;

    /**
     * @var StreamInterface|NULL
     */
    private $stream;

    /**
     * @var array
     */
    private static $errors = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    /**
     * @param StreamInterface|string $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|NULL $clientFilename
     * @param string|NULL $clientMediaType
     */
    public function __construct($streamOrFile, ?int $size, int $errorStatus,
                                ?string $clientFilename = NULL,
                                ?string $clientMediaType = NULL)
    {
        $this->setError($errorStatus);
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        if ($this->isOk())
        {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * Depending on the value set file or stream variable
     *
     * @param mixed $streamOrFile
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile)
    {
        if (\is_string($streamOrFile))
        {
            $this->stream = new Stream($streamOrFile);
        } elseif ($streamOrFile instanceof \Psr\Http\Message\StreamInterface)
        {
            $this->stream = $streamOrFile;
        } else
        {
            throw new \InvalidArgumentException(
                    'Invalid stream or file provided for UploadedFile'
            );
        }
    }

    /**
     * @param int $error
     * @throws InvalidArgumentException
     */
    private function setError(int $error)
    {
        if (FALSE === \in_array($error, self::$errors))
        {
            throw new \InvalidArgumentException(
                    'Invalid error status for UploadedFile'
            );
        }
        $this->error = $error;
    }

    /**
     * Return TRUE if there is no upload error
     *
     * @return boolean
     */
    private function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @return boolean
     */
    public function hasMoved(): bool
    {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive()
    {
        if (FALSE === $this->isOk())
        {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }
        if ($this->hasMoved())
        {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the upload was not successful.
     */
    public function getStream()
    {
        $this->validateActive();
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \RuntimeException if the upload was not successful.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        try
        {
            $this->validateActive();
            if (!\is_string($targetPath) || empty($targetPath))
            {
                throw new \InvalidArgumentException(
                        'Invalid path provided for move operation; must be a non-empty string'
                );
            }
            $this->copyToStream($this->getStream(),
                                new Stream($targetPath, 'w+b'));
            if (FALSE === $this->moved)
            {
                throw new \RuntimeException('Uploaded file could not be moved to ' . $targetPath);
            }
        } catch (\Throwable $exc)
        {
            throw $exc;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return int|NULL The file size in bytes or NULL if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|NULL The filename sent by the client or NULL if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Copy upload stream to destination stream
     * 
     * @param StreamInterface $source Source
     * @param StreamInterface $dest Destination
     * @param int $maxLength
     */
    private function copyToStream(StreamInterface $source,
                                  StreamInterface $dest, int $maxLength = -1)
    {
        $bufferSize = 1024;
        if ($maxLength == -1)
        {
            while (!$source->eof())
            {
                if (!$dest->write($source->read($bufferSize)))
                {
                    break;
                }
            }
        } else
        {
            $remaining = $maxLength;
            while ($remaining > 0 && !$source->eof())
            {
                $buf = $source->read(\min($bufferSize, $remaining));
                $len = \strlen($buf);
                if (!$len)
                {
                    break;
                }
                $remaining -= $len;
                $dest->write($buf);
            }
        }

        $this->moved = TRUE;
    }

    function __destruct()
    {
        if ($this->stream)
        {
            $this->stream->close();
        }
    }

}
