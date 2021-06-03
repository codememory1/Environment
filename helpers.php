<?php

use Codememory\Components\Environment\Environment;

if (!function_exists('env')) {
    /**
     * @param string $keys
     *
     * @return mixed
     */
    function env(string $keys): mixed
    {

        return Environment::get($keys);

    }
}

if (!function_exists('isDev')) {
    /**
     * @return bool
     */
    function isDev(): bool
    {

        if (preg_match('/^dev/', env('app.mode'))) {
            return true;
        }

        return false;

    }
}

if (!function_exists('isProd')) {
    /**
     * @return bool
     */
    function isProd(): bool
    {

        if (preg_match('/^prod/', env('app.mode'))) {
            return true;
        }

        return false;

    }
}