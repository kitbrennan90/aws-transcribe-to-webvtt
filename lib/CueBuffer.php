<?php

namespace AwsTranscribeToWebVTT;

use DateInterval;
use DateTime;
use Exception;

/**
 * Class CueBuffer
 * Holds a buffer for a current webvtt cue
 * Note: transcriptions come from AWS as single words, so we need to convert these into sentences, this buffer
 *       holds that sentence
 *
 * @package AwsTranscribeToWebVTT
 */
class CueBuffer
{
    /**
     * @var DateTime
     */
    private $bufferStartTime;

    /**
     * @var DateTime
     */
    private $bufferEndTime;

    /**
     * @var string
     */
    private $bufferString = '';

    /**
     * Number of seconds to postpone each time by (useful if your output video has a spliced video at the start)
     * @var int
     */
    private $secondPostponement = 0;

    /**
     * CueBuffer constructor.
     *
     * @param int $secondPostponement
     *
     * @throws Exception
     */
    public function __construct(int $secondPostponement)
    {
        $this->secondPostponement = $secondPostponement;
        $this->bufferStartTime = $this->getTime();
        $this->bufferEndTime = $this->getTime();
    }

    /**
     * @return DateTime
     */
    public function getBufferStartTime(): DateTime
    {
        return $this->bufferStartTime;
    }

    /**
     * @param string $startTime
     *
     * @return CueBuffer
     * @throws Exception
     */
    public function setBufferStartTime(string $startTime): CueBuffer
    {
        $this->bufferStartTime = $this->getTime($startTime);
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getBufferEndTime(): DateTime
    {
        return $this->bufferEndTime;
    }

    /**
     * @param string $endTime
     *
     * @return CueBuffer
     * @throws Exception
     */
    public function setBufferEndTime(string $endTime): CueBuffer
    {
        $this->bufferEndTime = $this->getTime($endTime);
        return $this;
    }

    /**
     * @return string
     */
    public function getBufferString(): string
    {
        return $this->bufferString;
    }

    /**
     * @param string $bufferString
     *
     * @return CueBuffer
     */
    public function appendBufferString(string $bufferString): CueBuffer
    {
        $this->bufferString .= $bufferString;
        return $this;
    }

    /**
     * @return int
     */
    public function getBufferStringLength(): int
    {
        return strlen($this->bufferString);
    }

    /**
     * Gets the length of the interval between the buffer's start and end time in seconds
     * Note: can provide an optional end time to override current
     *
     * @param string|null $endTime
     *
     * @return int
     * @throws Exception
     */
    public function getBufferSecondLength(string $endTime = null): int
    {
        if (!$endTime) {
            $endTime = $this->bufferEndTime;
        } else {
            $endTime = $this->getTime($endTime);
        }

        return $this->bufferStartTime->diff($endTime)->s;
    }

    /**
     *
     */
    public function resetBuffer()
    {
        $this->bufferStartTime = $this->bufferEndTime;
        $this->bufferString = '';
    }

    /**
     * AWS timestamps are horrible and inconsistent (would it have been so hard to output an ISO valid time format?)
     *
     * @param string $time
     *
     * @return DateTime
     * @throws Exception
     */
    private function getTime(string $time = '00.000'): DateTime
    {
        $hourString = '00';
        $minString = '00';
        $microSeconds = '000000';

        $microMacroParts = explode('.', $time);

        if (count($microMacroParts) > 1) {
            // Set micro part of string to be microseconds
            $microSeconds = $this->padTimePart($microMacroParts[count($microMacroParts) - 1], 6, false);
        }

        $timeParts = explode(':', $microMacroParts[0]);

        $secString = $this->padTimePart($timeParts[count($timeParts) - 1], 2);

        if (count($timeParts) > 1) {
            $minString = $this->padTimePart($timeParts[count($timeParts) - 2], 2);
        }

        if (count($timeParts) > 2) {
            $minString = $this->padTimePart($timeParts[count($timeParts) - 3], 2);
        }

        // Handle postponement
        $datetime = new DateTime();
        $datetime->setTime($hourString, $minString, $secString, $microSeconds)
            ->add(DateInterval::createFromDateString($this->secondPostponement . ' seconds'));

        return $datetime;
    }

    /**
     * Pads a time part so that it behaves properly when passed into DateInterval
     * (eg. microseconds becomes 111000 rather than 000111)
     *
     * @param string $timePart
     * @param int $length
     * @param bool $padStart
     *
     * @return string
     */
    private function padTimePart(string $timePart, int $length, bool $padStart = true): string
    {
        $missingCharacters = '';
        if (strlen($timePart) < $length) {
            $missingCharacterCount = $length - strlen($timePart);
            $missingCharacters = str_repeat('0', $missingCharacterCount);
        }

        if ($padStart) {
            $timePart = $missingCharacters . $timePart;
        } else {
            $timePart = $timePart . $missingCharacters;
        }

        return $timePart;
    }
}
