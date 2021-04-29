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
use Codememory\FileSystem\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteEnvCacheCommand
 * @package Codememory\Components\Environment\Commands
 *
 * @author  Codememory
 */
class DeleteEnvCacheCommand extends Command
{

    /**
     * @var string|null
     */
    protected ?string $command = 'cache:delete:env';

    /**
     * @return void
     */
    protected function overrideConfig(): void
    {

        $this
            ->setDescription(sprintf(
                'Delete entire environment configuration cache (%s)',
                GlobalConfig::get('environment.filename')
            ));

        return;

    }

    /**
     * {@inheritdoc}
     *
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

        Environment::__constructStatic($filesystem);
        $statusRemovedEnvCache = Environment::$cache->remove(Environment::CACHE_TYPE, Environment::CACHE_NAME);

        if ($statusRemovedEnvCache) {
            $this->io->success('Environment cache deleted');
        } else {
            $this->io->warning('The environment cache has not been deleted. Due to his absence');
        }

        return Command::SUCCESS;

    }

}