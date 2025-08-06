<?php

use PHPUnit\Framework\TestCase;
use App\Services\EmailService;
use App\Interfaces\MailerInterface;
use App\Interfaces\FileDownloaderInterface;

class EmailServiceTest extends TestCase
{
    private $config;
    private $mailerMock;
    private $fileDownloaderMock;

    protected function setUp(): void
    {
        $this->config = [
            'host' => 'smtp.example.com',
            'user' => 'user@example.com',
            'pass' => 'secret',
            'port' => 587,
        ];

        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->fileDownloaderMock = $this->createMock(FileDownloaderInterface::class);
    }

    public function testSendEmailSuccess()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body');

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailFailure()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(false);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body');

        $this->assertFalse($result['status']);
        $this->assertEquals('Email could not be sent', $result['message']);
    }

    public function testSendEmailMultipleRecipients()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);
        $this->mailerMock->expects($this->exactly(2))->method('addAddress');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail(['recipient1@example.com', 'recipient2@example.com'], 'Test Subject', 'Test Body');

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailNullRecipients()
    {
        $this->expectException(TypeError::class);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail(null, 'Test Subject', 'Test Body');
    }

    public function testSendEmailEmptyRecipients()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(false);
        
        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('', 'Test Subject', 'Test Body');

        $this->assertFalse($result['status']);
        $this->assertEquals('Email could not be sent', $result['message']);
    }

    public function testSendEmailNullSubject()
    {
        $this->expectException(TypeError::class);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', null, 'Test Body');
    }

    public function testSendEmailEmptySubject()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(false);
        
        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', '', 'Test Body');

        $this->assertFalse($result['status']);
        $this->assertEquals('Email could not be sent', $result['message']);
    }

    public function testSendEmailNullBody()
    {
        $this->expectException(TypeError::class);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', null);
    }

    public function testSendEmailEmptyBody()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(false);
        
        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', '');

        $this->assertFalse($result['status']);
        $this->assertEquals('Email could not be sent', $result['message']);
    }

    public function testSendEmailWithMultipleRecipients()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);
        $this->mailerMock->expects($this->exactly(2))->method('addAddress');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail(['recipient1@example.com', 'recipient2@example.com'], 'Test Subject', 'Test Body');

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailWithBCC()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);
        $this->mailerMock->expects($this->once())->method('addBCC')->with('bcc@example.com');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body', null, 'bcc@example.com');

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailWithMultipleBCC()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);
        $this->mailerMock->expects($this->exactly(2))->method('addBCC');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body', null, ['bcc1@example.com', 'bcc2@example.com']);

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailWithAttachments()
    {
        $this->mailerMock->expects($this->once())->method('send')->willReturn(true);
        $this->fileDownloaderMock->expects($this->once())->method('download')->with('http://example.com/file.jpg')->willReturn('/tmp/213.png');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body', ['http://example.com/file.jpg']);

        $this->assertTrue($result['status']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testSendEmailAttachmentDownloadFailure()
    {
        $this->mailerMock->expects($this->never())->method('send');
        $this->fileDownloaderMock->expects($this->once())->method('download')->with('http://example.com/file.jpg')->willReturn(false);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body', 'http://example.com/file.jpg');

        $this->assertFalse($result['status']);
    }

    public function testSendEmailThrowsException()
    {
        $this->mailerMock->expects($this->once())->method('send')->will($this->throwException(new Exception('SMTP Error')));

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $result = $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body');

        $this->assertFalse($result['status']);
        $this->assertEquals('SMTP Error', $result['message']);
    }

    public function testSetupMailer()
    {
        $this->mailerMock->expects($this->once())->method('isSMTP');
        $this->mailerMock->expects($this->once())->method('setSMTPAuth')->with(true);
        $this->mailerMock->expects($this->once())->method('setHost')->with($this->config['host']);
        $this->mailerMock->expects($this->once())->method('setUsername')->with($this->config['user']);
        $this->mailerMock->expects($this->once())->method('setPassword')->with($this->config['pass']);
        $this->mailerMock->expects($this->once())->method('setPort')->with((int) $this->config['port']);
        $this->mailerMock->expects($this->once())->method('setFrom')->with($this->config['user']);
        $this->mailerMock->expects($this->once())->method('setSMTPSecure')->with('');
        $this->mailerMock->expects($this->once())->method('setSMTPDebug')->with(0);
        $this->mailerMock->expects($this->once())->method('setSMTPTimeout')->with(5);
        $this->mailerMock->expects($this->once())->method('isHTML')->with(true);

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
    }

    public function testPrepareEmail()
    {
        $this->mailerMock->expects($this->once())->method('setSubject')->with('Test Subject');
        $this->mailerMock->expects($this->once())->method('setBody')->with('Test Body');

        $emailService = new EmailService($this->config, $this->mailerMock, $this->fileDownloaderMock);
        $emailService->sendEmail('recipient@example.com', 'Test Subject', 'Test Body');
    }
}
