<?php

namespace Kaliop\QueueingBundle\Helper;

use Symfony\Component\Process\Process as BaseProcess;

/**
 * Allow to force Symfony Process objects to trust that php has been compiled with --enable-sigchild even when the
 * options used to compile php are not visible to phpinfo, such as on Debian/Ubuntu
 */
class Process extends BaseProcess
{
    static $forceSigchildEnabled = null;

    /**
     * @param null|bool $force
     * @return null|bool previous state
     */
    public static function forceSigchildEnabled($force)
    {
        $prev = self::$forceSigchildEnabled;
        self::$forceSigchildEnabled = $force === null ? null : (bool) $force;
        return $prev;
    }

    protected function isSigchildEnabled()
    {
        if (null !== self::$forceSigchildEnabled) {
            return self::$forceSigchildEnabled;
        }

        return parent::isSigchildEnabled();
    }
}
