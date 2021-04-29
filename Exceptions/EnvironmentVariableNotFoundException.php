<?php

namespace Codememory\Components\Environment\Exceptions;

use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class EnvironmentVariableNotFoundException
 * @package Codememory\Components\Environment\Exceptions
 *
 * @author  Codememory
 */
class EnvironmentVariableNotFoundException extends ErrorException
{

    /**
     * EnvironmentVariableNotFoundException constructor.
     *
     * @param string $env
     */
    #[Pure] public function __construct(string $env)
    {

        parent::__construct(sprintf(
            'Environment variable %s not found',
            $env
        ));

    }

}