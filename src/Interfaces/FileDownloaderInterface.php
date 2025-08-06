<?php

namespace App\Interfaces;

interface FileDownloaderInterface
{
    public function download(string $remotePath): string|false;
    public function delete(string $path): void;
}
