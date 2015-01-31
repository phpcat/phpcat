<?php

/**
 * Phpcat Phing Task
 * @package Phing
 */
namespace Phpcat\Phing;

/**
 * Task
 * Implementa Phing Task
 *
 * @package Phing
 */
abstract class Task extends \Task
{
    protected $logLag = 0;

    /**
     * The init method: Do init steps.
     */
    public function init()
    {
        // nothing to do here
    }

    /**
     * shift the log output
     */
    protected function logShift()
    {
        $this->logLag++;
    }

    /**
     * unshift the log output
     */
    protected function logUnshift()
    {
        if ($this->logLag > 0) {
            $this->logLag--;
        }
    }

    /**
     * Log a line in the execution.
     *
     * @param string $sentence
     * @param int    $level    default \Project::MSG_INFO. MSG_ERR | MSG_WARN | MSG_INFO | MSG_VERBOSE | MSG_DEBUG
     */
    protected function logLine($sentence, $level = \Project::MSG_INFO)
    {
        if ($this->logLag > 0) {
            $sentence = str_repeat('  ', $this->logLag).'- '.$sentence;
        }
        $this->log($sentence, $level);
    }
}
