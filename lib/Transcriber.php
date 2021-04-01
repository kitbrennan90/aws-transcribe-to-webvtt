<?php

namespace AwsTranscribeToWebVTT;

use AwsTranscribeToWebVTT\Exception\NotJsonException;
use Exception;
use stdClass;

class Transcriber
{
    /**
     * Number of seconds to postpone each time by (useful if your output video has a spliced video at the start)
     * @var int
     */
    private $secondPostponement = 0;

    /**
     * @var stdClass
     */
    private $awsTranscription;

    /**
     * How long, in characters, should a cue reach before it is separated into another cue
     * @var int
     */
    private $maxCueStringLength = 50;

    /**
     * How long, in seconds, should a cue reach before it is separated into another cue
     * @var int
     */
    private $maxCueTimeLength = 30;

    /**
     * @var Writer|null
     */
    private $writer = null;

    /**
     * @var CueBuffer|null
     */
    private $cueBuffer = null;

    /**
     * @var int
     */
    private $currentSpeakerSegment = 0;

    /**
     * @var stdClass[]|null
     */
    private $speakerSegments = null;

    /**
     * Create a new transcriber.
     *
     * @param Writer $writer
     */
    public function __construct(Writer $writer = null)
    {
        $this->writer = $writer ? $writer : new Writer;
    }

    /**
     * Accepts a string of encoded json and converts it into a buffered webVTT file
     *
     * @return string
     * @throws Exception
     */
    public function getOutputAsString(): string
    {
        $this->cueBuffer = new CueBuffer($this->secondPostponement);
        $this->speakerSegments = $this->awsTranscription->results->speaker_labels->segments ?? null;

        foreach ($this->awsTranscription->results->items as $item) {
            $this->processItem($item);
        }

        // Output final buffer segment
        if ($this->cueBuffer->getBufferStringLength()) {
            $this->writeBuffer();
        }

        return $this->writer->getOutputString();
    }

    /**
     * Writes the buffer to the output document
     */
    private function writeBuffer(): void
    {
        $this->writer->writeCue(
            $this->cueBuffer->getBufferStartTime(),
            $this->cueBuffer->getBufferEndTime(),
            [$this->cueBuffer->getBufferString()]
        );
        $this->cueBuffer->resetBuffer();
    }

    /**
     * @param stdClass $item
     *
     * @return bool
     */
    private function hasSpeakerSegmentChanged(stdClass $item): bool
    {
        if (!$this->speakerSegments || !isset($item->start_time) || !isset($item->end_time)) {
            return false;
        }

        $activeSegment = $this->speakerSegments[$this->currentSpeakerSegment];
        if (($item->start_time >= $activeSegment->start_time) && ($item->end_time <= $activeSegment->end_time)) {
            return false;
        }

        $this->setCurrentSpeakerSegment($item);
        return true;
    }

    /**
     * @param stdClass $item
     */
    private function setCurrentSpeakerSegment(stdClass $item)
    {
        if (!$this->speakerSegments || !isset($item->start_time) || !isset($item->end_time)) {
            return;
        }

        foreach ($this->speakerSegments as $i => $segment) {
            if (($item->start_time >= $segment->start_time) && ($item->end_time <= $segment->end_time)) {
                $this->currentSpeakerSegment = $i;
            }
        }
    }

    /**
     * @param string $awsTranscription
     *
     * @return Transcriber
     * @throws NotJsonException
     */
    public function setAwsTranscription(string $awsTranscription): Transcriber
    {
        $awsTranscription = json_decode($awsTranscription);

        if (!$awsTranscription) {
            throw new NotJsonException("Invalid json string provided");
        }
        $this->awsTranscription = $awsTranscription;

        // TODO validate JSON has correct structure to be converted

        return $this;
    }

    /**
     * @param int $maxCueStringLength
     *
     * @return Transcriber
     */
    public function setMaxCueStringLength(int $maxCueStringLength): Transcriber
    {
        $this->maxCueStringLength = $maxCueStringLength;
        return $this;
    }

    /**
     * @param int $maxCueTimeLength
     *
     * @return Transcriber
     */
    public function setMaxCueTimeLength(int $maxCueTimeLength): Transcriber
    {
        $this->maxCueTimeLength = $maxCueTimeLength;
        return $this;
    }

    /**
     * @param int $secondPostponement
     *
     * @return Transcriber
     */
    public function setSecondPostponement(int $secondPostponement): Transcriber
    {
        $this->secondPostponement = $secondPostponement;
        return $this;
    }

    /**
     * @param $item
     *
     * @throws Exception
     */
    private function processItem($item): void
    {
        $itemContent = $item->alternatives[0]->content;

        /** Actions before writing to buffer */
        // If this item has a different speaker
        if ($this->hasSpeakerSegmentChanged($item) && $this->cueBuffer->getBufferStringLength()) {
            $this->writeBuffer();
        }

        // If new string will be longer than max length
        if (($this->cueBuffer->getBufferStringLength() + strlen($itemContent)) > $this->maxCueStringLength) {
            $this->writeBuffer();
        }

        // If new length will be longer than max length
        if ($this->cueBuffer->getBufferSecondLength($item->end_time ?? null) > $this->maxCueTimeLength) {
            $this->writeBuffer();
        }

        /** Write to buffer */
        $this->processItemToBuffer($item, $itemContent);

        /** Actions after writing to buffer */
        // Output buffer if we have hit punctuation
        if ($item->type === 'punctuation') {
            $this->writeBuffer();
        }
    }

    /**
     * @param $item
     * @param $itemContent
     *
     * @throws Exception
     */
    private function processItemToBuffer($item, $itemContent): void
    {
        $this->setCurrentSpeakerSegment($item);
        // AWS output does not include spaces, so we should add one before each word
        if (($item->type === 'pronunciation') && $this->cueBuffer->getBufferStringLength()) {
            $this->cueBuffer->appendBufferString(' ');
        }
        $this->cueBuffer->appendBufferString($itemContent);
        // Not all items have an end time (eg. punctuation).
        if (isset($item->end_time)) {
            $this->cueBuffer->setBufferEndTime($item->end_time);
        }
    }
}
