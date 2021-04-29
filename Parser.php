<?php

namespace Codememory\Components\Environment;

use Codememory\Components\Environment\Exceptions\EnvironmentVariableNotFoundException;
use Codememory\Components\Environment\Exceptions\ParsingErrorException;
use Codememory\Components\Environment\Exceptions\VariableParsingErrorException;
use Codememory\Support\Arr;
use Codememory\Support\ConvertType;

/**
 * Class Parser
 * @package Codememory\Components\Environment
 *
 * @author  Codememory
 */
class Parser
{

    private const GROUP_EXPRESSION = '[A-Z]*';
    private const NAME_EXPRESSION = '[A-Z\-\.]*';

    /**
     * @var string|null
     */
    private ?string $env;

    /**
     * Parser constructor.
     *
     * @param string|null $env
     */
    public function __construct(?string $env)
    {

        $this->env = $env;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Splits the text from the environment file into lines
     * and returns an array of lines, all empty lines will
     * be removed from the array
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     */
    public function splitIntoLines(): array
    {

        $strings = explode(PHP_EOL, $this->env);

        foreach ($strings as $index => $string) {
            if (empty($string)) {
                unset($strings[$index]);
            }
        }

        return $strings;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns an array of values and variables if the matches
     * regex, otherwise throws a ParsingErrorException
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws ParsingErrorException
     */
    public function variableDecoder(): array
    {

        $meaningsAndEnv = [];
        $expression = sprintf('/^(?<variable>%s_%s)=(?<value>.*)$/', self::GROUP_EXPRESSION, self::NAME_EXPRESSION);

        foreach ($this->splitIntoLines() as $envString) {
            if (!preg_match(
                sprintf($expression, self::GROUP_EXPRESSION, self::NAME_EXPRESSION),
                $envString,
                $match
            )) {
                throw new ParsingErrorException($envString, $expression);
            }

            $meaningsAndEnv['variables'][] = $match['variable'];
            $meaningsAndEnv['values'][] = $match['value'];
        }

        return $meaningsAndEnv;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns an array of all existing environment groups
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    public function groups(): array
    {

        return array_keys($this->variables());

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns a multidimensional array consisting of a group,
     * part of the variable name and their value. At the same
     * time, there is a search for environment variables and
     * transformation into the correct type.
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param bool $parsingValue
     *
     * @return array
     * @throws EnvironmentVariableNotFoundException
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    public function getVariables(bool $parsingValue = true): array
    {

        $variables = $this->variables();

        foreach ($variables as &$group) {
            foreach ($group as $variable => &$value) {
                if($parsingValue) {
                    $value = $this->parsingValue($value);
                }
            }
        }

        return $variables;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Search for environment variables in values, if a variable,
     * then it is replaced by the values of this variable
     * and converted to the correct type, if this variable is not an
     * EnvironmentVariableNotFoundException exception is thrown
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed $value
     *
     * @return Parser
     * @throws EnvironmentVariableNotFoundException
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    private function findVariables(mixed &$value): Parser
    {

        $convertType = new ConvertType();

        preg_match_all(sprintf(
            '/\${(?<variable>%s_%s)}/',
            self::GROUP_EXPRESSION,
            self::NAME_EXPRESSION,
        ), $value, $match);

        foreach ($match['variable'] ?? [] as $variable) {
            $variableKeyForArray = str_replace('_', '.', $variable);

            if (!Arr::exists($this->variables(), $variableKeyForArray)) {
                throw new EnvironmentVariableNotFoundException($variable);
            }

            $substitute = Arr::set($this->variables())->get($variableKeyForArray);
            $replace = str_replace(sprintf('${%s}', $variable), $substitute, $value);
            $value = $convertType->auto($replace);

            $this->findVariables($value);

        }

        return $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Loop through all lines and try to find an environment that
     * matches regex; if the line does not match regex, a
     * VariableParsingErrorException is thrown; otherwise, the value
     * is converted to a specific type and whips up the array of
     * all environments
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    private function variables(): array
    {

        $convertType = new ConvertType();
        $variables = [];

        foreach ($this->variableDecoder()['variables'] ?? [] as $index => $envString) {
            if (!$this->match('/^(?<group>%s)_(?<name>%s)$/', $envString, $match, self::GROUP_EXPRESSION, self::NAME_EXPRESSION)) {
                throw new VariableParsingErrorException();
            }

            $variables[$match['group']][$match['name']] = $convertType->auto($this->variableDecoder()['values'][$index]);
        }

        return $variables;

    }

    /**
     * @param string     $regex
     * @param mixed      $value
     * @param array|null $match
     * @param mixed      ...$args
     *
     * @return bool|int
     */
    private function match(string $regex, mixed $value, ?array &$match, ...$args): bool|int
    {

        return preg_match(sprintf($regex, ...$args), $value, $match);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method in which all methods are called that look for something
     * in the values of the environments and return the value itself
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed $value
     *
     * @return mixed
     * @throws EnvironmentVariableNotFoundException
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    private function parsingValue(mixed $value): mixed
    {

        $this
            ->findVariables($value);

        return $value;

    }

}