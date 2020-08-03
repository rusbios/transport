<?php
declare(strict_types = 1);

namespace RB\Transport;

use RB\Transport\Exceptions\{ConnectException, PathException};

class FtpClient
{
    private string $login;
    private string $password;
    private resource $connect;

    /**
     * FtpClient constructor.
     * @param string $host
     * @param string $login
     * @param string $password
     * @param int $port
     * @param int $timeout
     * @param bool $ssl
     * @throws ConnectException
     */
    public function __construct(
        string $host,
        string $login,
        string $password,
        int $port = 21,
        int $timeout = 90,
        bool $ssl = false
    )
    {
        $this->login = $login;
        $this->password = $password;
        $this->connect = $ssl ? ftp_ssl_connect($host, $port, $timeout) : ftp_connect($host, $port, $timeout);
        if ($this->connect !== false && ftp_login($this->connect, $login, $password)) {
            throw new ConnectException('Failed to connect to server');
        }
    }

    public function __destruct()
    {
        ftp_close($this->connect);
    }

    /**
     * @param string $locleFile
     * @param string|null $remoteFile
     * @return bool
     * @throws PathException
     */
    public function up(string $locleFile, string $remoteFile = null): bool
    {
        if (!file_exists($locleFile)) {
            throw new PathException(sprintf('File not found "%s"', $locleFile));
        }

        if ($remoteFile) {
            $tmp = explode('/', $locleFile);
            $remoteFile = __DIR__ . '/' . end($tmp);
            $this->mkdir(implode('/', $tmp));
        }

        return ftp_put($this->connect, $remoteFile, $locleFile);
    }

    /**
     * @param string $remoteFile
     * @param string|null $locleFile
     * @return bool
     */
    public function down(string $remoteFile, string $locleFile = null): bool
    {
        if ($locleFile) {
            $tmp = explode('/', $remoteFile);
            $locleFile = __DIR__ . '/' . end($tmp);
        }

        return ftp_get($this->connect, $locleFile, $remoteFile);
    }

    /**
     * @param string $remoteFile
     * @return bool
     */
    public function deleted(string $remoteFile): bool
    {
        return ftp_delete($this->connect, $remoteFile);
    }

    /**
     * @param string $remoteFile
     * @param string $locleFile
     * @return bool
     * @throws PathException
     */
    public function compare(string $remoteFile, string $locleFile): bool
    {
        if (!file_exists($locleFile)) {
            throw new PathException(sprintf('File not found "%s"', $locleFile));
        }

        $remoteSize = $this->getSize($remoteFile);
        $remoteTime = ftp_mdtm($this->connect, $remoteFile);

        return $remoteSize === filesize($locleFile) && $remoteTime === filemtime($locleFile);
    }

    /**
     * @param string $localDir
     * @param string $remoteDir
     * @throws PathException
     */
    public function syncDir(string $localDir, string $remoteDir): void
    {
        $paths = scandir($localDir);

        if ($paths === false) {
            throw new PathException(sprintf('Dir not found "%s"', $localDir));
        }

        foreach (scandir($localDir) as $path) {
            if (in_array($path, ['.', '..'])) continue;

            if (is_dir($path)) {
                self::syncDir("$localDir/$path", "$remoteDir/$path");
            }

            if (!$this->compare("$remoteDir/$path", "$localDir/$path")) {
                self::up("$localDir/$path", "$remoteDir/$path");
            }
        }
    }

    /**
     * @param string $dir
     * @return array|bool
     * @throws PathException
     */
    public function listFiles(string $dir = ''): array
    {
        if ($res = ftp_nlist($this->connect, $dir) !== false) {
            return $res;
        }

        throw new PathException('Folder not found');
    }

    /**
     * @param string $dir
     * @return array
     */
    public function scanDir(string $dir = ''): array
    {
        return ftp_mlsd($this->connect, $dir);
    }

    /**
     * @param string $filePath
     * @return int
     * @throws PathException
     */
    public function getSize(string $filePath): int
    {
        if ($size = ftp_size($filePath) !== -1) {
            return  $size;
        }

        throw new PathException(sprintf('File not found "%s"', $filePath));
    }

    /**
     * @param string $dir
     * @throws PathException
     */
    protected function mkdir(string $dir): void
    {
        if (ftp_mkdir($this->connect, $dir) === false) {
            throw new PathException(sprintf('Unable to create directory "%s"', $dir));
        }
    }
}