<?php

use AwsTranscribeToWebVTT\Transcriber;
use AwsTranscribeToWebVTT\Exception\NotJsonException;
use PHPUnit\Framework\TestCase;

class TranscriberTest extends TestCase
{
    /**
     * @throws NotJsonException
     */
    public function testInvalidJsonThrowsException()
    {
        $this->expectException(NotJsonException::class);
        $new = new Transcriber();

        $new->setAwsTranscription("{abcd)");
    }

    /**
     * @throws NotJsonException
     */
    public function testValidJson()
    {
        $file = file_get_contents("./tests/stub/aws_transcription_stub.json");

        $new = new Transcriber();
        $result = $new->setAwsTranscription($file)->getOutputAsString();

        $this->assertContains('The bottom line is we have to improve our', $result);
        $this->assertContains('00:00:00.000', $result, 'Transcript starts at 0');
    }

    /**
     *
     * @throws NotJsonException
     */
    public function testPostponement()
    {
        $file = file_get_contents("./tests/stub/aws_transcription_stub.json");

        $new = new Transcriber();
        $result = $new->setAwsTranscription($file)
            ->setSecondPostponement(3)
            ->getOutputAsString();

        $this->assertContains(
            '00:00:03.000',
            $result,
            'Transcription should start at 3 seconds'
        );
        $this->assertNotContains(
            '00:00:00.000',
            $result,
            'Transcription should not start at 0'
        );
    }

    /**
     *
     * @throws NotJsonException
     */
    public function testMaxCueTimeLength()
    {
        $file = file_get_contents("./tests/stub/aws_transcription_stub.json");

        $new = new Transcriber();
        $result = $new->setAwsTranscription($file)
            ->setMaxCueTimeLength(1)
            ->getOutputAsString();

        $this->assertContains(
            '00:00:00.000 --> 00:00:01.900',
            $result,
            'Cue is under 2 seconds (rounded down)'
        );
    }

    /**
     *
     * @throws NotJsonException
     */
    public function testMaxCueStringLength()
    {
        $file = file_get_contents("./tests/stub/aws_transcription_stub.json");

        $new = new Transcriber();
        $result = $new->setAwsTranscription($file)
            ->setMaxCueStringLength(4)
            ->getOutputAsString();

        $this->assertContains(
            '- bottom',
            $result,
            'First word is broken immediately'
        );
    }
}
