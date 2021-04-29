<?php

namespace Codememory\Components\Environment\Commands;

use Codememory\Components\Caching\Exceptions\ConfigPathNotExistException;
use Codememory\Components\Console\Command;
use Codememory\Components\Environment\Environment;
use Codememory\Components\Environment\Exceptions\EnvironmentVariableNotFoundException;
use Codememory\Components\Environment\Exceptions\IncorrectPathToEnviException;
use Codememory\Components\Environment\Exceptions\ParsingErrorException;
use Codememory\Components\Environment\Exceptions\VariableParsingErrorException;
use Codememory\Components\GlobalConfig\GlobalConfig;
use Codememory\Components\JsonParser\JsonParser;
use Codememory\FileSystem\File;
use Codememory\FileSystem\Interfaces\FileInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateEnvCacheCommand
 * @package Codememory\Components\Environment\Commands
 *
 * @author  Codememory
 */
class UpdateEnvCacheCommand extends Command
{

    /**
     * @var string|null
     */
    protected ?string $command = 'cache:update:env';

    /**
     * @return void
     */
    protected function overrideConfig(): void
    {

        $this->setDescription(sprintf(
            'Refresh the environment configuration cache (%s)',
            GlobalConfig::get('environment.filename')
        ));

        return;

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ConfigPathNotExistException
     * @throws EnvironmentVariableNotFoundException
     * @throws IncorrectPathToEnviException
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    protected function handler(InputInterface $input, OutputInterface $output): int
    {

        $filesystem = new File();
        $json = new JsonParser();

        Environment::__constructStatic($filesystem);
        Environment::$cache->create(
            Environment::CACHE_TYPE,
            Environment::CACHE_NAME,
            Environment::all(),
            function (FileInterface $fs, string $path, mixed $data) use ($json) {
                $fs->writer
                    ->open($path.'.json', 'w', true)
                    ->put($json->setData($data)->encode());
            }
        );

        $this->io->success('Environment cache updated');

        return Command::SUCCESS;

    }

}