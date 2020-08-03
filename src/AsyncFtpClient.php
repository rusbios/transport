<?php
declare(strict_types = 1);

namespace RB\Transport;

use RB\Transport\Exceptions\AsyncException;
use Exception;

class AsyncFtpClient extends FtpClient
{
    public const STATUS_NEW = 1;
    public const STATUS_PROCCESS = 2;
    public const STATUS_DONE = 3;
    public const STATUS_ERROR = -1;

    /** @var int[] */
    private array $process = [];
    private array $errors = [];

    /**
     * @param string $locleFile
     * @param string|null $remoteFile
     * @return int
     * @throws AsyncException
     */
    public function up(string $locleFile, string $remoteFile = null): int
    {
        $this->process[] = self::STATUS_NEW;
        $key = array_key_last($this->process);

        $pid = pcntl_fork();
        if ($pid == -1) {
            unset($this->process[$key]);
            throw new AsyncException('Failed to spawn child process');
        } elseif ($pid) {
            $this->process[$key] = self::STATUS_PROCCESS;
            try {
                if (parent::up($locleFile, $remoteFile)) {
                    $this->process[$key] = self::STATUS_DONE;
                }
                $this->process[$key] = self::STATUS_ERROR;
            } catch (Exception $e) {
                $this->process[$key] = self::STATUS_ERROR;
                $this->errors[$key] = $e;
            }
            return 0;
        }

        return $key;
    }

    /**
     * @param string $remoteFile
     * @param string|null $locleFile
     * @return int
     * @throws AsyncException
     */
    public function down(string $remoteFile, string $locleFile = null): int
    {
        $this->process[] = self::STATUS_NEW;
        $key = array_key_last($this->process);

        $pid = pcntl_fork();
        if ($pid == -1) {
            unset($this->process[$key]);
            throw new AsyncException('Failed to spawn child process');
        } elseif ($pid) {
            $this->process[$key] = self::STATUS_PROCCESS;
            try {
                if (parent::down($remoteFile, $locleFile)) {
                    $this->process[$key] = self::STATUS_DONE;
                }
                $this->process[$key] = self::STATUS_ERROR;
            } catch (Exception $e) {
                $this->process[$key] = self::STATUS_ERROR;
                $this->errors[$key] = $e;
            }
            return 0;
        }

        return $key;
    }

    /**
     * @param string $localDir
     * @param string $remoteDir
     * @return int
     * @throws AsyncException
     */
    public function syncDir(string $localDir, string $remoteDir): int
    {
        $this->process[] = self::STATUS_NEW;
        $key = array_key_last($this->process);

        $pid = pcntl_fork();
        if ($pid == -1) {
            unset($this->process[$key]);
            throw new AsyncException('Failed to spawn child process');
        } elseif ($pid) {
            $this->process[$key] = self::STATUS_PROCCESS;
            try {
                if (parent::sync($localDir, $remoteDir)) {
                    $this->process[$key] = self::STATUS_DONE;
                }
                $this->process[$key] = self::STATUS_ERROR;
            } catch (Exception $e) {
                $this->process[$key] = self::STATUS_ERROR;
                $this->errors[$key] = $e;
            }
            return 0;
        }

        return $key;
    }

    /**
     * @param int $key
     * @return int
     * @throws AsyncException
     * @throws Exception
     */
    public function getStatus(int $key): int
    {
        if (!empty($this->errors[$key])) {
            throw $this->errors[$key];
        }

        if (!empty($this->process[$key])) {
            return $this->process[$key];
        }

        throw new AsyncException('Key not found');
    }
}