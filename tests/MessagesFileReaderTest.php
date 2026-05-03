<?php

declare(strict_types=1);

namespace ProPresenter\Parser\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProPresenter\Parser\Message;
use ProPresenter\Parser\MessageLibrary;
use ProPresenter\Parser\MessagesFileReader;
use ProPresenter\Parser\MessagesFileWriter;

class MessagesFileReaderTest extends TestCase
{
    private const REFERENCE_PATH = __DIR__ . '/../doc/reference_samples/Messages';

    #[Test]
    public function readThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MessagesFileReader::read(__DIR__ . '/../doc/reference_samples/does-not-exist-messages');
    }

    #[Test]
    public function readReturnsLibraryWithExpectedCount(): void
    {
        $library = MessagesFileReader::read(self::REFERENCE_PATH);
        $this->assertInstanceOf(MessageLibrary::class, $library);
        $this->assertCount(2, $library->getMessages());
        $this->assertSame(2, $library->count());
    }

    #[Test]
    public function messagesExposeTitleAndUuid(): void
    {
        $first = MessagesFileReader::read(self::REFERENCE_PATH)->getMessages()[0];
        $this->assertInstanceOf(Message::class, $first);
        $this->assertSame('Gottesdienst Sonntag 10Uhr Timer', $first->getTitle());
        $this->assertSame('5D1DAC57-CD17-4AD0-A096-CCCB37FF425B', $first->getUuid());
    }

    #[Test]
    public function lookupByUuidIsCaseInsensitive(): void
    {
        $library = MessagesFileReader::read(self::REFERENCE_PATH);
        $upper = $library->getMessageByUuid('5D1DAC57-CD17-4AD0-A096-CCCB37FF425B');
        $lower = $library->getMessageByUuid('5d1dac57-cd17-4ad0-a096-cccb37ff425b');
        $this->assertNotNull($upper);
        $this->assertSame($upper, $lower);
    }

    #[Test]
    public function lookupByNameSucceeds(): void
    {
        $message = MessagesFileReader::read(self::REFERENCE_PATH)->getMessageByName('Neue Nachricht');
        $this->assertNotNull($message);
        $this->assertSame('68F5B8E9-7EA8-4259-A990-D1863BC56C78', $message->getUuid());
    }

    #[Test]
    public function addAndRemoveMessageRoundTrip(): void
    {
        $library = MessagesFileReader::read(self::REFERENCE_PATH);
        $library->addMessage('Test Message', '11111111-1111-1111-1111-111111111111');
        $this->assertSame(3, $library->count());
        $this->assertNotNull($library->getMessageByUuid('11111111-1111-1111-1111-111111111111'));
        $this->assertTrue($library->removeMessage('11111111-1111-1111-1111-111111111111'));
        $this->assertSame(2, $library->count());
    }

    #[Test]
    public function writerProducesByteIdenticalRoundTrip(): void
    {
        $library = MessagesFileReader::read(self::REFERENCE_PATH);
        $first = tempnam(sys_get_temp_dir(), 'messages_');
        $second = tempnam(sys_get_temp_dir(), 'messages_');
        try {
            MessagesFileWriter::write($library, $first);
            MessagesFileWriter::write(MessagesFileReader::read($first), $second);
            $this->assertSame(file_get_contents($first), file_get_contents($second));
        } finally {
            @unlink($first ?: '');
            @unlink($second ?: '');
        }
    }
}
