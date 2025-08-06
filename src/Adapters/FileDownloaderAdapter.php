<?php

declare(strict_types=1);

namespace App\Adapters;

use App\Interfaces\FileDownloaderInterface;
use Exception;

class FileDownloaderAdapter implements FileDownloaderInterface
{
    private $tempDir;
    private $localPath;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir();
        $this->localPath = $this->tempDir . '/';
    }

    public function setPathDir(string $newPath): void 
    {
        $this->localPath = $newPath;
    }

    public function download(string $remotePathOrBase64): string|false
    {
        if ($this->isNotHttp($remotePathOrBase64)) {
            return $this->handleBase64Download($remotePathOrBase64);
        }
        
        return $this->handleHttpDownload($remotePathOrBase64);
    }

    private function isNotHttp($string): bool
    {
        return !filter_var($string, FILTER_VALIDATE_URL);
    }

    private function getExtensionFromBase64(string $base64String): string|false
    {
        if (!preg_match('/^data:([a-zA-Z0-9\/\-\+]+);base64,/', $base64String, $matches)) return false;

        $mimeType = $matches[1];

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/msword' => 'doc',
        ];

        return $extensions[$mimeType] ?? false;
    }

    private function handleBase64Download(string $base64, string $fileName = null): string|false
    {
        $base64Data = preg_replace('/^data:[a-zA-Z0-9\/\-\+]+;base64,/', '', $base64);
        $fileContents = base64_decode($base64Data);
        if ($fileContents === false) return false;

        $extension = $this->getExtensionFromBase64($base64);
        if (!$extension) return false;

        $fileName = $fileName ?? uniqid('file_', true) . '.' . $extension;
        $localPath = $this->localPath . $fileName;

        file_put_contents($localPath, $fileContents);

        return $localPath;
    }

    private function handleHttpDownload(string $url): string|false
    {
        $fileName = hash('sha256', $url) . '.' . pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $localPath = $this->localPath . $fileName;

        try {
            // Configura o contexto para ignorar a verificação de SSL
            $context = stream_context_create([
                'http' => [
                    'method' => "GET",
                    'timeout' => 30,
                    'follow_location' => true,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $fileContents = file_get_contents($url, false, $context);
            if ($fileContents === false) return false;

            file_put_contents($localPath, $fileContents);
        } catch (Exception $e) {
            return false;
        }

        return $localPath;
    }
    
    public function delete(string $localPath): void
    {
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }
}
