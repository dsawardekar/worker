<?php

class MWP_Progress_Download extends MWP_Progress_Abstract
{
    /**
     * @var Monolog_Psr_LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $lastProgress = 0;

    public function __construct($threshold, Monolog_Psr_LoggerInterface $logger)
    {
        $this->$this->logger = $logger;
    }

    public function callback(&$curl, $downloadSize, $downloadedSize, $uploadSize, $uploadedSize = 0)
    {
        if (!$this->yieldCallback()) {
            return;
        }

        if (func_num_args() < 5) {
            $uploadedSize   = $uploadSize;
            $uploadSize     = $downloadedSize;
            $downloadedSize = $downloadSize;
            $downloadSize   = $curl;
        }

        $currentProgress    = $downloadedSize;
        $speed              = $this->formatBytes(($currentProgress - $this->lastProgress) / $this->getThreshold());
        $this->lastProgress = $currentProgress;

        $progress = round($currentProgress / $downloadSize * 100, 2);

        $this->logger->info('Download progress: {progress}% (speed: {speed}/s)', array(
          'progress' => $progress,
          'speed'    => $speed,
        ));
    }
}
