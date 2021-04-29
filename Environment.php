<?php

namespace Codememory\Components\Environment;

use Codememory\Components\Caching\Cache;
use Codememory\Components\Console\ResourcesCommand;
use Codememory\Components\Console\Running;
use Codememory\Components\Environment\Commands\UpdateEnvCacheCommand;
use Codememory\Components\Environment\Exceptions\IncorrectPathToEnviException;
use Codememory\Components\GlobalConfig\GlobalConfig;
use Codememory\Components\Markup\Types\YamlType;
use Codememory\FileSystem\Interfaces\FileInterface;
use Codememory\Support\Arr;
use Codememory\Support\Str;
use Exception;

/**
 * Class Environment
 * @package Codememory\Components\Environment
 *
 * @author  Codememory
 */
class Environment
{

    public const CACHE_TYPE = 'configs';
    public const CACHE_NAME = 'envi';

    /**
     * @var FileInterface
     */
    public static FileInterface $filesystem;

    /**
     * @var array|Cache
     */
    public static array|Cache $cache = [];

    /**
     * @var string
     */
    protected static string $filename;

    /**
     * @var string|null
     */
    protected static ?string $pathToEnv = null;

    /**
     * @var string|null
     */
    protected static ?string $envString = null;

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Static constructor, this method must be called in
     * the file to which requests are sent
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param FileInterface $filesystem
     *
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     * @throws IncorrectPathToEnviException
     */
    public static function __constructStatic(FileInterface $filesystem)
    {

        self::$filename = GlobalConfig::get('environment.filename');
        self::$pathToEnv = GlobalConfig::get('environment.pathWithFile');
        self::$filesystem = $filesystem;

        self::readingEnv()->run();

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Create env file if it doesn't exist
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return bool
     */
    public static function create(): bool
    {

        if (!self::$filesystem->exist(self::$pathToEnv . self::$filename)) {
            self::$filesystem->writer
                ->open(self::$pathToEnv . self::$filename)
                ->put('null', 0);
        }

        return true;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Convert env array of data to string, second argument says
     * how much indentation to add after each group
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param array $env
     * @param int   $blankLinesAfterGroups
     *
     * @return string|null
     */
    public static function dump(array $env, int $blankLinesAfterGroups = 1): ?string
    {

        $envString = null;
        ++$blankLinesAfterGroups;

        foreach ($env as $group => $variables) {
            if (is_array($variables) && [] !== $variables) {
                foreach ($variables as $name => $value) {
                    $envString .= sprintf("%s_%s=%s\n", Str::toUppercase($group), Str::toUppercase($name), $value);
                }

                $envString = mb_substr($envString, 0, -1);
                $envString .= Str::repeat("\n", $blankLinesAfterGroups);
            }
        }

        return mb_substr($envString, 0, -$blankLinesAfterGroups);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Parsing an env string into an array of data
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string $env
     *
     * @return array
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     */
    public static function parse(string $env): array
    {

        $parser = new Parser($env);

        return $parser->getVariables();

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Checking for the existence of an env key in
     * an environment file
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string $group
     * @param string $name
     *
     * @return bool
     */
    public static function exist(string $group, string $name): bool
    {

        return Arr::exists($_ENV, Str::toUppercase($group . '.' . $name));

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Get value from environment file by default data
     * is taken from cache.
     * An example of the first argument to app.mode is APP_MODE
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string|null $keys
     * @param mixed|null  $default
     *
     * @return mixed
     */
    public static function get(?string $keys = null, mixed $default = null): mixed
    {

        $env = self::all();

        if (self::$cache->exist(self::CACHE_TYPE, self::CACHE_NAME)) {
            $env = self::$cache->get(self::CACHE_TYPE, self::CACHE_NAME);
        }

        if (null === $keys) {
            return $env;
        }

        return Arr::set($env)::get(Str::toUppercase($keys)) ?? $default;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Remove environment variable from env file using
     * third argument can update cache too
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string $group
     * @param string $name
     * @param bool   $updateCache
     *
     * @return bool
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     * @throws Exception
     */
    public static function remove(string $group, string $name, bool $updateCache = false): bool
    {

        $envs = self::getWithoutParsingValues();

        if (self::exist($group, $name)) {
            unset($envs[Str::toUppercase($group)][Str::toUppercase($name)]);

            self::$filesystem->writer
                ->open(self::$pathToEnv . self::$filename)
                ->put(self::dump($envs));

            if ($updateCache) {
                self::updateCache();
            }

            return true;
        }

        return false;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Change the environment file by callback in which the
     * data array is passed by the link. The third argument
     * can be used to update the cache
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param callable $handler
     * @param bool     $updateCache
     *
     * @return bool
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     * @throws Exception
     */
    public static function change(callable $handler, bool $updateCache = false): bool
    {

        $data = self::getWithoutParsingValues();

        call_user_func_array($handler, [&$data]);

        self::$filesystem->writer
            ->open(self::$pathToEnv . self::$filename)
            ->put(self::dump($data));

        if ($updateCache) {
            self::updateCache();
        }

        return true;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns an array of all environments from an env file
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     */
    public static function all(): array
    {

        if (!array_key_exists('APP', $_ENV) || !array_key_exists('MODE', $_ENV['APP'])) {
            $_ENV['APP']['MODE'] = GlobalConfig::get('environment.defaultAppMode');
        }

        return $_ENV;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Updating the cache by matching the env file
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Environment
     * @throws Exception
     */
    public static function updateCache(): Environment
    {

        $running = new Running();

        $running
            ->addCommands([
                new UpdateEnvCacheCommand()
            ])
            ->addCommand(function (ResourcesCommand $resourcesCommand) {
                $resourcesCommand
                    ->commandToExecute('cache:update:env');
            })
            ->run();

        return new static();

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns parsing environment data without
     * parsing variable values
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     */
    private static function getWithoutParsingValues(): array
    {

        $parser = new Parser(self::$envString);

        return $parser->getVariables(false);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The main method for starting parsing before receiving data,
     * this method is called in the static constructor
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return void
     * @throws Exceptions\EnvironmentVariableNotFoundException
     * @throws Exceptions\ParsingErrorException
     * @throws Exceptions\VariableParsingErrorException
     * @throws IncorrectPathToEnviException
     */
    private static function run(): void
    {

        self::readingEnv();

        if (null === self::$envString) {
            $_ENV = [];
        } else {
            $_ENV = self::parse(self::$envString);
        }

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Reading a file with environments, if the file does not exist,
     * an exception will be thrown, otherwise the data from env will
     * be written to the property for parsing
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Environment
     * @throws IncorrectPathToEnviException
     */
    private static function readingEnv(): Environment
    {

        if (GlobalConfig::get('environment.useCache')) {
            $cache = new Cache(new YamlType(), self::$filesystem);

            self::$cache = $cache;
        }

        if (!self::$filesystem->exist(self::$pathToEnv . self::$filename)) {
            throw new IncorrectPathToEnviException();
        }

        self::$envString = self::$filesystem->reader
            ->open(self::$pathToEnv . self::$filename)
            ->read();

        return new static();

    }

}