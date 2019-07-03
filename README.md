# AWS Transcribe to WebVTT

This PHP package lets you take a JSON output from Amazon Transcribe and convert it into a valid WebVTT file to use as video subtitles.

### Installation

You can install this library via Composer. Run the following command:
`composer require kitbrennan90/aws-transcribe-to-webvtt`

### Usage

Getting started could not be easier. Simply initialise the transcriber, set your Amazon Transcribe string, and request an output:


```php
use AwsTranscribeToWebVTT\Transcriber;

$transcriber = new Transcriber();
$transcriber->setAwsTranscription($jsonString);
$result = $transcriber->getOutputAsString();
``` 

### Advanced Options

#### Set max string length of cues

By default, cues will be cut when they reach 30 characters long. You can set your own cutoff with the `setMaxCueStringLength(int $value)` option.

Example (setting cue length at 40 characters):

```php
use AwsTranscribeToWebVTT\Transcriber;

$transcriber = new Transcriber();
$transcriber->setAwsTranscription($jsonString)->setMaxCueStringLength(40);
$result = $transcriber->getOutputAsString();
``` 

#### Set max second length of cues

By default, cues will be cut if they will span a period longer than 30 seconds. You can set a custom length in seconds using `setMaxCueTimeLength(int $value)`.

Example (setting cue length at 50 seconds):

```php
use AwsTranscribeToWebVTT\Transcriber;

$transcriber = new Transcriber();
$transcriber->setAwsTranscription($jsonString)->setMaxCueTimeLength(50);
$result = $transcriber->getOutputAsString();
``` 

_Note: the length of a cue is worked out to the nearest second, so a value of 30 will still include cues 30.9 seconds long._

#### Delay all timings by _n_ seconds

Sometimes it is useful to postpone all the timings (eg. when you are stitching videos together). Use the `setSecondPostponement(int $value)` to set this option (default is no delay).

Example (delaying all timings by 10 seconds):

```php
use AwsTranscribeToWebVTT\Transcriber;

$transcriber = new Transcriber();
$transcriber->setAwsTranscription($jsonString)->setSecondPostponement(10);
$result = $transcriber->getOutputAsString();
``` 

### Help

This library is a small labour of love. If you have any questions or if you think something is missing, please option an issue and I will answer as quickly as possible.
