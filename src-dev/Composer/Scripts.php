<?php

namespace Sweetchuck\Robo\ScssLint\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;

class Scripts
{
    /**
     * @var \Composer\Script\Event
     */
    protected static $event;

    /**
     * @var string
     */
    protected static $bundlerVersion = '1.13.6';

    /**
     * @var \Closure
     */
    protected static $processCallbackWrapper;

    public static function postInstallCmd(Event $event): bool
    {
        static::init($event);

        GitHooks::deploy($event);
        static::bundleCheckAndInstall($event);

        return true;
    }

    public static function postUpdateCmd(Event $event): bool
    {
        static::init($event);

        GitHooks::deploy($event);

        return true;
    }

    public static function bundleCheckAndInstall(Event $event): bool
    {
        static::init($event);

        $cmdPattern = 'gem install bundler:%s --no-document; bundle check || bundle install';
        $cmdArgs = [static::$bundlerVersion];

        $process = new Process(vsprintf($cmdPattern, $cmdArgs));
        $exitCode = $process->run(static::$processCallbackWrapper);

        return !$exitCode;
    }

    protected static function init(Event $event)
    {
        if (static::$event) {
            return;
        }

        static::$event = $event;
        static::$processCallbackWrapper = function (string $type, string $text) {
            static::processCallback($type, $text);
        };
    }

    protected static function processCallback(string $type, string $text)
    {
        if ($type === Process::OUT) {
            static::$event->getIO()->write($text);
        } else {
            static::$event->getIO()->writeError($text);
        }
    }
}
