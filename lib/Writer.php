<?php

namespace AwsTranscribeToWebVTT;

use DateTime;

/**
 * Class Writer
 * A writer writes a webvtt string that may be converted into a document
 *
 * @package AwsTranscribeToWebVTT
 */
class Writer
{
    /**
     * @var string
     */
    const END_OF_LINE = PHP_EOL;

    /**
     * String that will contain our output document
     * @var string
     */
    protected $outputString = '';

    /**
     * Time format that must be used in WebVTT documents
     * @var string
     */
    protected $outputTimeFormat = 'H:i:s\.v';

    /**
     * Writer constructor.
     */
    public function __construct()
    {
        $this->writeHeader();
    }

    /**
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @param array|string[] $tracks
     */
    public function writeCue(DateTime $startTime, DateTime $endTime, array $tracks)
    {
        $this->writeEmptyLine();

        $this->writeCueTime($startTime, $endTime);
        $this->writeCueTracks($tracks);
    }

    /**
     * Writes the header for the webvtt document
     */
    protected function writeHeader()
    {
        $this->writeLine('WEBVTT');
        $this->writeEmptyLine();
    }

    /**
     * Writes a new line to the file contents
     * @param string $contents
     */
    protected function writeLine(string $contents)
    {
        $this->outputString .= $contents . self::END_OF_LINE;
    }

    /**
     * Writes an empty line to the file contents
     */
    protected function writeEmptyLine()
    {
        $this->outputString .= self::END_OF_LINE;
    }

    /**
     * @param DateTime $startTime
     * @param DateTime $endTime
     */
    protected function writeCueTime(DateTime $startTime, DateTime $endTime)
    {
        $string = $startTime->format($this->outputTimeFormat) . ' --> ' . $endTime->format($this->outputTimeFormat);
        $this->writeLine($string);
    }

    /**
     * @param array $tracks
     */
    protected function writeCueTracks(array $tracks)
    {
        foreach ($tracks as $track) {
            $this->writeCueTrack($track);
        }
    }

    /**
     * @param string $track
     */
    protected function writeCueTrack(string $track)
    {
        $this->writeLine('- ' . $track);
    }

    /**
     * @return string
     */
    public function getOutputString(): string
    {
        return $this->outputString;
    }
}
