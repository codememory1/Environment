<?php

namespace Codememory\Components\Environment\Exceptions;

use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class IncorrectPathToEnviException
 * @package Codememory\Components\Environment
 *
 * @author  Codememory
 */
class IncorrectPathToEnviException extends ErrorException
{

    /**
     * IncorrectPathToEnviException constructor.
     */
    #[Pure] public function __construct()
    {

        parent::__construct('Invalid path to the env file or such file does not exist at the given path');

    }

}