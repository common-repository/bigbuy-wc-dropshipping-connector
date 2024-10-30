<?php

namespace WcMipConnector\Service;

defined('ABSPATH') || exit;

use WcMipConnector\Enum\MipWcConnector;
use WcMipConnector\Enum\StatusTypes;

class DirectoryService implements DirectoryServiceInterface
{
    private const HTACCESS_FILE_CONTENT = 'Order deny,allow
                            Deny from all
                            Allow from ::1
                            Allow from localhost
                            Allow from 127.0.0.1
                            Allow from 90.161.45.249
                            Allow from 176.98.223.114
                            Allow from 194.30.88.4';

    private const HTACCESS_FILE_NAME = '.htaccess';

    /** @var DirectoryService */
    private static $instance;

    private $pluginFileOwner = null;

    /**
     * @var null|string
     */
    private static $rootDir = null;

    /**
     * @var null|string
     */
    private static $uploadDir = null;

    /**
     * @return DirectoryService
     */
    public static function getInstance(): DirectoryService
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $folder
     */
    public function removeDirectory(string $folder): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $dir = new \DirectoryIterator($folder);
        $loggerService = new LoggerService();

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
                $loggerService->getInstance()->info('Removing directory '.$item->getPathname());
                rmdir($item->getPathname());

                continue;
            }

            $loggerService->getInstance()->info('Removing file '.$item->getPathname());

            try {
                unlink($item->getPathname());
            } catch (\Exception $unlinkException) {
                $loggerService->getInstance()->info('Failed executing unlink '.$unlinkException->getMessage());
            }
        }

        rmdir($folder);
    }

    public function getUploadDir()
    {
        if (empty(self::$uploadDir)) {
            $uploadDir = \wp_upload_dir();

            self::$uploadDir = $uploadDir['basedir'];
        }

        return self::$uploadDir;
    }

    /**
     * @return string|null
     */
    public function getVarDir(): string
    {
        return $this->getUploadDir().'/mip-connector';
    }

    /**
     * @return string|null
     */
    public function getLogDir(): string
    {
        return $this->getVarDir().'/logs';
    }

    /**
     * @return string|null
     */
    public function getImportFilesDir(): string
    {
        return $this->getVarDir().'/importFiles';
    }

    /**
     * @return string
     */
    public function getTranslationsDir(): string
    {
        return MipWcConnector::MODULE_NAME.'/app/translations';
    }

    /**
     * @return string
     */
    public function getViewsDir(): string
    {
        return $this->getModuleDir().'/app/views';
    }

    /**
     * @return string
     */
    public function getModuleDir(): string
    {
        return __DIR__.'/../..';
    }

    /**
     * @return string
     */
    public function getUploadDirByCurrentYear(): string
    {
        $year = \date('Y');

        return $this->getUploadDirByYear((int)$year);
    }

    /**
     * @param int|null $year
     * @return string
     */
    public function getUploadDirByYear(int $year): string
    {
        return $this->getUploadDir().'/'.$year;
    }

    /**
     * @return string
     */
    public function getPluginsDir(): string
    {
        return $this->getModuleDir().'/../';
    }

    /**
     * @return string
     */
    public function getUpdateDir(): string
    {
        return $this->getModuleDir().'/upgrade';
    }

    /**
     * @return string
     */
    public function getUpdateSqlDir(): string
    {
        return $this->getModuleDir().'/upgrade/sql';
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        if (empty(self::$rootDir)) {
            self::$rootDir = \get_home_path();
        }

        return \rtrim(self::$rootDir, '/');
    }

    /**
     * @param string $fileName
     * @param string $fileDir
     * @param string $content
     * @param int $permissions
     * @return bool
     * @throws \Exception
     */
    public function saveFileContent(string $fileName, string $fileDir, string $content, int $permissions = 0755): bool
    {
        $this->createDirectory($fileDir);

        try {
            @file_put_contents($fileDir.'/'.$fileName, $content);
        } catch (\Throwable $exception) {}

        try {
            return @chmod($fileDir.'/'.$fileName, $permissions);
        } catch (\Throwable $exception) {}

        return false;
    }

    /**
     * @param string $filename
     * @param string|null $fileDir
     * @throws \Exception
     */
    public function deleteFile(string $filename, ?string $fileDir = null): void
    {
        if ($fileDir) {
            $filename = $fileDir.'/'.$filename;
        }

        try {
            @unlink($filename);
        } catch (\Throwable $exception) {}
    }

    /**
     * @param string $folder
     * @param int $permissions
     * @throws \Exception
     */
    public function createDirectory(string $folder, int $permissions = 0755): void
    {
        $oldPermissions = umask(0000);

        $mkdirSuccess = false;

        try {
            $mkdirSuccess = @mkdir($folder, $permissions, true);
        } catch (\Throwable $exception) {}

        if (!$mkdirSuccess && !is_dir($folder)) {
            throw new \Exception($folder.' could not be created');
        }

        try {
            @chmod($folder, $permissions);
        } catch (\Throwable $exception) {}

        umask($oldPermissions);
    }

    public function createHtaccessIfNotExists()
    {
        $logDir = $this->getLogDir();
        $importFilesDir = $this->getImportFilesDir();

        if (!file_exists($logDir.'/'.self::HTACCESS_FILE_NAME)) {
            $this->createDirectory($logDir);
            file_put_contents($logDir.'/'.self::HTACCESS_FILE_NAME, self::HTACCESS_FILE_CONTENT);
        }

        try {
            @chmod($logDir.'/'.self::HTACCESS_FILE_NAME, 0664);
        } catch (\Throwable $exception) {}

        if (!file_exists($importFilesDir.'/'.self::HTACCESS_FILE_NAME)) {
            $this->createDirectory($importFilesDir);
            file_put_contents($importFilesDir.'/'.self::HTACCESS_FILE_NAME, self::HTACCESS_FILE_CONTENT);
        }

        try {
            @chmod($importFilesDir.'/'.self::HTACCESS_FILE_NAME, 0664);
        } catch (\Throwable $exception) {}
    }

    public function setFolderPermissionRecursively(string $folder, int $permissions = 0755)
    {
        $dir = new \DirectoryIterator($folder);

        foreach ($dir as $item) {
            if ($item->isDir() && !$item->isDot()) {
                try {
                    @chmod($item->getPathname(), $permissions);
                } catch (\Throwable $exception) {}
            }
        }
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function fileRemoteExist(string $filename): bool
    {
        if (empty($filename) || \filter_var($filename, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $fileHeaders = false;

        try {
            $fileHeaders = @get_headers($filename);
        } catch (\Throwable $exception) {}

        return is_array($fileHeaders) && isset($fileHeaders[0]) && !mb_stripos($fileHeaders[0], StatusTypes::HTTP_NOT_FOUND);
    }

    /**
     * @param string $filesDir
     * @return \DirectoryIterator|null
     */
    public function getFilesByRequiredDir(string $filesDir): ?\DirectoryIterator
    {
        try {
            return new \DirectoryIterator($filesDir);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $filename
     * @param string|null $fileDir
     * @return bool
     */
    public function fileExist(string $filename, ?string $fileDir = null): bool
    {
        if ($fileDir) {
            $filename = $fileDir.'/'.$filename;
        }

        return file_exists($filename);
    }

    /**
     * @param string $filename
     * @param string|null $fileDir
     * @return string
     */
    public function getFileContent(string $filename, ?string $fileDir = null)
    {
        if ($fileDir) {
            $filename = $fileDir.'/'.$filename;
        }

        return file_get_contents($filename);
    }

    /**
     * @return bool|null
     */
    public function initializeWPFilesystem()
    {
        return WP_Filesystem();
    }

    /**
     * @param string $file
     * @param mixed $to
     * @return true|\WP_Error
     */
    public function unzipFile(string $file, string $to)
    {
        return unzip_file($file, $to);
    }

    /**
     * @param mixed $thing
     * @return bool
     */
    public function isWPError($thing): bool
    {
        return is_wp_error($thing);
    }

    public function getMyUidWithoutDependency(?string $path = null): ?int
    {
        if ($this->pluginFileOwner !== null) {
            return $this->pluginFileOwner;
        }

        try {
            $tmpFileResource = \tmpfile();
            $this->pluginFileOwner = (int)\fstat($tmpFileResource)["uid"];
            fclose($tmpFileResource);
        } catch (\Throwable $exception) {
            $this->pluginFileOwner = $this->getPathOwnerId($path ?? $this->getPluginsDir());
        }

        return $this->pluginFileOwner;
    }

    private function getPathOwnerId(string $path): ?int
    {
        if (\function_exists('fileowner')) {
            return (int)\fileowner($path);
        }

        $pathDir = $path;

        if (!\is_dir($pathDir)) {
            $pathDir = \dirname($pathDir);
        }

        $dir = new \DirectoryIterator($pathDir);
        $ownerId = null;

        foreach ($dir as $item) {
            $ownerId = $item->getOwner();

            break;
        }

        return $ownerId;
    }
}