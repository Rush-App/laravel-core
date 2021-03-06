<?php

namespace RushApp\Core\Services;

use Illuminate\Support\Facades\File;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggingService
{
    /**
     * @param mixed $message
     * @param int $level
     */
    public static function auth(mixed $message, int $level): void
    {
        self::getLogger(config('rushapp_core.log_groups.auth'), $level)->log($level, $message);
    }

    /** @param mixed $message */
    public static function debug(mixed $message): void
    {
        self::logCore($message, Logger::DEBUG);
    }

    /** @param mixed $message */
    public static function info(mixed $message): void
    {
        self::logCore($message, Logger::INFO);
    }

    /** @param mixed $message */
    public static function notice(mixed $message): void
    {
        self::logCore($message, Logger::NOTICE);
    }

    /** @param mixed $message */
    public static function warning(mixed $message): void
    {
        self::logCore($message, Logger::WARNING);
    }

    /** @param mixed $message */
    public static function error(mixed $message): void
    {
        self::logCore($message, Logger::ERROR);
    }

    /** @param mixed $message */
    public static function critical(mixed $message): void
    {
        self::logCore($message, Logger::CRITICAL);
    }

    /** @param mixed $message */
    public static function alert(mixed $message): void
    {
        self::logCore($message, Logger::ALERT);
    }

    /** @param mixed $message */
    public static function emergency(mixed $message): void
    {
        self::logCore($message, Logger::EMERGENCY);
    }

    /**
     * @param mixed $message
     * @param int $level
     */
    private static function logCore(mixed $message, int $level)
    {
        $levelName = strtolower(Logger::getLevelName($level));
        $logger = self::getLogger(config('rushapp_core.log_groups.core'), $level);

        $logger->$levelName($message);
    }

    /**
     * @param string $logGroupName
     * @param int $level
     * @return Logger
     */
    private static function getLogger(string $logGroupName, int $level): Logger
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


