<?php


namespace RB\Transport;


use RB\Transport\Exceptions\PathException;

class StreamClient
{
    protected int $maxLen;

    /**
     * StreamClient constructor.
     * @param int $maxLen
     */
    public function __construct(int $maxLen = 1000)
    {
        $this->maxLen = $maxLen;
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @throws PathException
     */
    public function toFile(string $fromPath, string $toPath): void
    {
        if ($size = filesize($fromPath) === false) {
            throw new PathException('File not found');
        }

        $size = filesize($fromPath);
        $stream = fopen($fromPath, 'r');
        $toStream = fopen($toPath, 'w');

        $offset = 0;
        while (true) {
            fwrite($toStream, stream_get_contents($stream, $this->maxLine, $offset));

            $offset += $this->maxLen;

            if ($offset >= $size) break;
        }

        fclose($stream);
        fclose($toStream);
    }

    /**
     * @param string $fromPath
     * @throws PathException
     */
    public function toOb(string $fromPath): void
    {
        if ($size = filesize($fromPath) === false) {
            throw new PathException('File not found');
        }

        ob_flush();
        $size = filesize($fromPath);
        $stream = fopen($fromPath, 'r');
        $tmp = explode('/', $fromPath);

        header('Content-type: ' . mime_content_type($fromPath) ?: 'application/octet-stream');
        header('Content-disposition: attachment;filename=' . end($tmp));

        $offset = 0;
        while (true) {
            echo stream_get_contents($stream, $this->maxLine, $offset);

            $offset += $this->maxLen;

            if ($offset >= $size) break;
        }

        fclose($stream);
    }
}