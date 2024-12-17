<?php

namespace KobeniFramework\Log;

class Logger
{
    protected array $levels = [
        'emergency' => ['41;37', 'EMERGENCY'],
        'alert'     => ['43;37', 'ALERT'],
        'critical'  => ['41;37', 'CRITICAL'],
        'error'     => ['31', 'ERROR'],
        'warning'   => ['33', 'WARNING'],
        'notice'    => ['36', 'NOTICE'],
        'info'      => ['32', 'INFO'],
        'debug'     => ['90', 'DEBUG']
    ];

    protected string $logsPath;
    protected bool $displayInTerminal;
    protected ?string $currentChannel = null;

    public function __construct(string $basePath, bool $displayInTerminal = true)
    {
        $this->logsPath = $basePath . '/storage/logs';
        $this->displayInTerminal = $displayInTerminal;

        if (!is_dir($this->logsPath)) {
            mkdir($this->logsPath, 0755, true);
        }
    }

    public function channel(string $channel): self
    {
        $this->currentChannel = $channel;
        return $this;
    }

    protected function write(string $level, string $message, array $context = []): void
    {
        // Write to log file
        $timestamp = date('Y-m-d H:i:s');
        $channel = $this->currentChannel ?? 'kobeni';

        $logMessage = "[{$timestamp}] {$channel}.{$level}: {$message}";
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context);
        }
        $logMessage .= PHP_EOL;

        $filename = $this->logsPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND);

        // Display in terminal if enabled
        if ($this->displayInTerminal) {
            $this->displayInTerminal($level, $channel, $message, $context);
        }
    }

    protected function displayInTerminal(string $level, string $channel, string $message, array $context = []): void
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }

        $timestamp = date('H:i:s');
        [$color, $levelName] = $this->levels[$level];

        // Format level and channel
        $levelTag = str_pad($levelName, 9);
        $channelTag = str_pad($channel, 10);

        // Output using direct echo like Command class
        echo sprintf(
            "\033[90m[%s]\033[0m \033[%sm%s\033[0m \033[36m%s\033[0m %s",
            $timestamp,
            $color,
            $levelTag,
            $channelTag,
            $message
        );

        if (!empty($context)) {
            echo " " . json_encode($context);
        }

        echo "\n";
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->write('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->write('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->write('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }
}
