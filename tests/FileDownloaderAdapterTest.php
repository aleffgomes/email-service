<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Adapters\FileDownloaderAdapter;

class FileDownloaderAdapterTest extends TestCase
{
    private FileDownloaderAdapter $fileDownloader;

    protected function setUp(): void
    {
        $this->fileDownloader = new FileDownloaderAdapter();
    }

    public function testDownloadHttp(): void
    {
        $URL = 'https://google.com/index.html';
        $this->fileDownloader->setPathDir(dirname(realpath(__DIR__)).'/tests/');
        $LOCAL_PATH = $this->fileDownloader->download($URL);
        $this->assertNotFalse($LOCAL_PATH, 'O download HTTP falhou.');
        $this->assertFileExists($LOCAL_PATH, 'O arquivo baixado não foi encontrado no caminho esperado.');

        // Limpeza
        $this->fileDownloader->delete($LOCAL_PATH);
    }

    public function testDownloadBase64(): void
    {
        $BASE_64 = 'data:text/plain;base64,' . base64_encode('Conteúdo do arquivo de teste');
        $LOCAL_PATH = $this->fileDownloader->download($BASE_64);

        $this->assertNotFalse($LOCAL_PATH, 'O download Base64 falhou.');
        $this->assertFileExists($LOCAL_PATH, 'O arquivo Base64 não foi salvo corretamente.');

        $CONTENT = file_get_contents($LOCAL_PATH);
        $this->assertEquals('Conteúdo do arquivo de teste', $CONTENT, 'O conteúdo do arquivo não corresponde.');

        // Limpeza
        $this->fileDownloader->delete($LOCAL_PATH);
    }

    public function testDownloadInvalidUrl(): void
    {
        $URL = 'https://invalid.url/file.txt';
        $LOCAL_PATH = $this->fileDownloader->download($URL);

        $this->assertFalse($LOCAL_PATH, 'O download de uma URL inválida não deveria ser bem-sucedido.');
    }

    public function testDownloadInvalidBase64(): void
    {
        $BASE_64 = 'data:invalid;base64,abc123';
        $LOCAL_PATH = $this->fileDownloader->download($BASE_64);

        $this->assertFalse($LOCAL_PATH, 'O download de Base64 inválido não deveria ser bem-sucedido.');
    }
}
