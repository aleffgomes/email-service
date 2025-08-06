<?php

use PHPUnit\Framework\TestCase;
use App\Services\MessageValidatorService;

class MessageValidatorServiceTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new MessageValidatorService();
    }

    public function testValidateEmptyMessage()
    {
        $result = $this->validator->validate([]);
        $this->assertFalse($result['status']);
        $this->assertEquals('Message is empty', $result['message']);
    }

    public function testValidateNonArrayMessage()
    {
        $this->expectException(TypeError::class);

        $result = $this->validator->validate('not_an_array');
    }

    public function testValidateMissingRequiredFields()
    {
        $message = ['to' => 'recipient@example.com'];
        $result = $this->validator->validate($message);
        $this->assertFalse($result['status']);
        $this->assertEquals('Required fields missing (to, subject, body)', $result['message']);
    }

    public function testValidateInvalidEmail()
    {
        $message = ['to' => 'invalid_email', 'subject' => 'Test Subject', 'body' => 'Test Body'];
        $result = $this->validator->validate($message);
        $this->assertFalse($result['status']);
        $this->assertEquals('Invalid email: invalid_email', $result['message']);
    }

    public function testValidateInvalidBCC()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'bcc' => 'invalid_bcc'
        ];
        $result = $this->validator->validate($message);
        $this->assertFalse($result['status']);
        $this->assertEquals('Invalid email: invalid_bcc', $result['message']);
    }

    public function testValidateInvalidAttachment()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'attachments' => 'invalid_attachment'
        ];
        $result = $this->validator->validate($message);
        $this->assertFalse($result['status']);
        $this->assertEquals('Invalid attachment: invalid_attachment', $result['message']);
    }

    public function testValidateValidMessage()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body'
        ];
        $result = $this->validator->validate($message);
        $this->assertTrue($result['status']);
        $this->assertEquals('Validation successful', $result['message']);
    }

    public function testValidateValidMessageWithBCC()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'bcc' => 'bcc@example.com'
        ];
        $result = $this->validator->validate($message);
        $this->assertTrue($result['status']);
        $this->assertEquals('Validation successful', $result['message']);
    }

    public function testValidateValidMessageWithAttachments()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'attachments' => 'http://example.com/file.jpg'
        ];
        $result = $this->validator->validate($message);
        $this->assertTrue($result['status']);
        $this->assertEquals('Validation successful', $result['message']);
    }

    public function testValidateValidMessageWithAttachmentsAndBCC()
    {
        $message = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'attachments' => 'http://example.com/file.jpg',
            'bcc' => 'bcc@example.com'
        ];
        $result = $this->validator->validate($message);
        $this->assertTrue($result['status']);
        $this->assertEquals('Validation successful', $result['message']);
    }
}
