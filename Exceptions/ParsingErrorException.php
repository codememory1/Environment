<?php

namespace Codememory\Components\Environment\Exceptions;

use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class ParsingErrorException
 * @package Codememory\Components\Environment\Exceptions
 *
 * @author  Codememory
 */
class ParsingErrorException extends ErrorException
{

    /**
     * ParsingErrorException constructor.
     *
     * @param string $envString
     * @param string $expression
     */
    #[Pure] public function __construct(string $envString, string $expression)
    {

        parent::__construct(sprintf(
            'Error parsing environment variables %s must have a group name, variable name and value. Example: GROUP_NAME=value(optional) regex: %s',
            $envString,
            $expression
        ));

    }

}