<?php

namespace RushApp\Core\Services;

use Illuminate\Support\Facades\File;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggingService
{
    public static function auth(mixed $message, int $level): void
    {
        self::getLogger(config('boilerplate.log_groups.auth'), $level)->log($level, $message);
    }

    public static function debug(mixed $message): void
    {
        self::logCore($message, Logger::DEBUG);
    }

    public static function info(mixed $message): void
    {
        self::logCore($message, Logger::INFO);
    }

    public static function notice(mixed $message): void
    {
        self::logCore($message, Logger::NOTICE);
    }

    public static function warning(mixed $message): void
    {
        self::logCore($message, Logger::WARNING);
    }

    public static function error(mixed $message): void
    {
        self::logCore($message, Logger::ERROR);
    }

    public static function critical(mixed $message): void
    {
        self::logCore($message, Logger::CRITICAL);
    }

    public static function alert(mixed $message): void
    {
        self::logCore($message, Logger::ALERT);
    }

    public static function emergency(mixed $message): void
    {
        self::logCore($message, Logger::EMERGENCY);
    }

    protected static function logCore(mixed $message, int $level)
    {
        $levelName = strtolower(Logger::getLevelName($level));
        $logger = self::getLogger(config('boilerplate.log_groups.core'), $level);

        $logger->$levelName($message);
    }

    protected static function getLogger(string $logGroupName, int $level): Logger
    {
        $log = new Logger(config('app.env'));

        $logDestinationPath = storage_path('logs/'.$logGroupName);
        if (!File::isDirectory($logDestinationPath)) {
            File::makeDirectory($logDestinationPath, 0755, true);
        }
        $logFileName = now()->format('Y-m-d').'.log';

        $log->pushHandler(new StreamHandler($logDestinationPath.'/'.$logFileName, $level));

        return $log;
    }
}


