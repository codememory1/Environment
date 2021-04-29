<?php

namespace Codememory\Components\Environment\Exceptions;

use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class VariableParsingErrorException
 * @package Codememory\Components\Environment\Exceptions
 *
 * @author  Codememory
 */
class VariableParsingErrorException extends ErrorException
{

    /**
     * VariableParsingErrorException constructor.
     */
    #[Pure] public function __construct()
    {

        parent::__construct('The naming conventions should consist of a group and a name. Example: GROUP_NAME');

    }

}