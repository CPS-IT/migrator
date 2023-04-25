<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/migrator".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace CPSIT\Migrator\Command;

use CPSIT\Migrator\Diff;
use CPSIT\Migrator\Exception;
use CPSIT\Migrator\Formatter;
use CPSIT\Migrator\Migrator;
use CPSIT\Migrator\Resource;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function getcwd;
use function is_dir;
use function is_file;
use function uniqid;

/**
 * MigrateCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MigrateCommand extends Console\Command\Command
{
    private readonly Filesystem\Filesystem $filesystem;
    private Console\Style\SymfonyStyle $io;
    private Formatter\Formatter $formatter;

    public function __construct(
        private readonly Migrator $migrator = new Migrator(),
    ) {
        parent::__construct('migrate');
        $this->filesystem = new Filesystem\Filesystem();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrate a given code base by generating a diff between two resources.');

        $this->addArgument(
            'base-directory',
            Console\Input\InputArgument::REQUIRED,
            'The base directory to be migrated',
        );
        $this->addArgument(
            'source-directory',
            Console\Input\InputArgument::REQUIRED,
            'The source directory',
        );
        $this->addArgument(
            'target-directory',
            Console\Input\InputArgument::REQUIRED,
            'The target directory',
        );
        $this->addOption(
            'dry-run',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Do not perform any migrations, only calculate diff',
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
        $this->formatter = new Formatter\CliFormatter();
    }

    /**
     * @throws Exception\InvalidResourceException
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $base = $this->createCollector($this->resolveResource($input->getArgument('base-directory')), false);
        $source = $this->createCollector($this->resolveResource($input->getArgument('source-directory')));
        $target = $this->createCollector($this->resolveResource($input->getArgument('target-directory')));
        $dryRun = $input->getOption('dry-run');

        // Handle dry-run mode
        if ($dryRun) {
            $this->migrator->performMigrations(false);
        }

        // Perform migration
        try {
            $diffResult = $this->migrator->migrate($source, $target, $base);
        } catch (Exception\PatchFailureException $exception) {
            $diffResult = $exception->getDiffResult();
        }

        // Decorate diff result
        if ($this->io->isVerbose() || $dryRun || !$diffResult->getOutcome()->isSuccessful()) {
            $this->decorateDiffResult($diffResult);
        }

        // Early return if migration was successful
        if ($diffResult->getOutcome()->isSuccessful()) {
            $this->io->success('Migration was successful.');

            if ($dryRun) {
                $this->io->writeln('💡 No migrations were performed. Omit the <comment>--dry-run</comment> parameter to apply migrations.');
                $this->io->newLine();
            }

            return self::SUCCESS;
        }

        $this->io->error('Migration failed, no patches were applied.');
        $this->io->writeln(trim((string) $diffResult->getOutcome()->getMessage()));

        // Write patch file
        if ($this->io->confirm('Do you want to save a patch file to the current working directory?', false)) {
            $this->writePatchFile($diffResult->getPatch());
        }

        return self::FAILURE;
    }

    private function decorateDiffResult(Diff\DiffResult $diffResult): void
    {
        $formattedDiffResult = $this->formatter->format($diffResult);

        if (null !== $formattedDiffResult) {
            $this->io->section('Changed files');
            $this->io->writeln($formattedDiffResult);
        }
    }

    private function writePatchFile(string $patch): void
    {
        do {
            $filename = $this->resolveResource(uniqid('failed_migration_').'.diff');
        } while ($this->filesystem->exists($filename));

        $this->filesystem->dumpFile($filename, $patch);

        $this->io->writeln('💡 Patch file written to '.$filename);
    }

    private function resolveResource(string $resource): string
    {
        if (Filesystem\Path::isAbsolute($resource)) {
            return $resource;
        }

        $cwd = getcwd();

        // @codeCoverageIgnoreStart
        if (false === $cwd) {
            throw new Console\Exception\RuntimeException('Unable to determine current working directory.', 1674654976);
        }
        // @codeCoverageIgnoreEnd

        return Filesystem\Path::makeAbsolute($resource, $cwd);
    }

    /**
     * @throws Exception\InvalidResourceException
     *
     * @phpstan-return ($allowFiles is true ? Resource\Collector\CollectorInterface : Resource\Collector\DirectoryCollector)
     */
    private function createCollector(string $resource, bool $allowFiles = true): Resource\Collector\CollectorInterface
    {
        if (is_dir($resource)) {
            return new Resource\Collector\DirectoryCollector($resource);
        }

        if (is_file($resource) && $allowFiles) {
            return new Resource\Collector\FileCollector($resource);
        }

        throw Exception\InvalidResourceException::create($resource);
    }
}
