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

namespace CPSIT\Migrator\Diff\Differ;

use CPSIT\Migrator\Diff;
use CPSIT\Migrator\Exception;
use CPSIT\Migrator\Helper;
use CPSIT\Migrator\Resource;
use GitElephant\Objects;
use GitElephant\Repository;
use GitElephant\Status;
use Symfony\Component\Filesystem;

use function end;
use function explode;

/**
 * GitDiffer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GitDiffer implements Differ
{
    private const MAIN_BRANCH = 'main';
    private const BASE_BRANCH = 'base';

    private const DIFF_MODES = [
        Status\StatusFile::ADDED => Diff\DiffMode::Added,
        Status\StatusFile::COPIED => Diff\DiffMode::Copied,
        Status\StatusFile::DELETED => Diff\DiffMode::Deleted,
        Status\StatusFile::IGNORED => Diff\DiffMode::Ignored,
        Status\StatusFile::MODIFIED => Diff\DiffMode::Modified,
        Status\StatusFile::RENAMED => Diff\DiffMode::Renamed,
        Status\StatusFile::UNTRACKED => Diff\DiffMode::Untracked,
        Status\StatusFile::UPDATED_BUT_UNMERGED => Diff\DiffMode::Conflicted,
    ];

    private readonly Filesystem\Filesystem $filesystem;
    private readonly Repository $repository;

    public function __construct()
    {
        $this->filesystem = new Filesystem\Filesystem();
        $this->repository = $this->initializeRepository();
    }

    public function generateDiff(
        Resource\Collector\CollectorInterface $source,
        Resource\Collector\CollectorInterface $target,
        Resource\Collector\DirectoryCollector $base,
    ): Diff\DiffResult {
        // Generate Git tree
        $this->commitSourceFiles($source);
        $this->commitBaseFiles($base);
        $this->commitTargetFiles($target);

        // Perform three-way merge
        $outcome = $this->merge();

        // Calculate diff objects from current status
        $status = $this->repository->stage()->getStatus();
        $diffObjects = $this->calculateDiffObjects($status);

        // Generate applicable patch
        $patch = $this->repository->getCaller()->execute('diff --cached')->getOutput();

        return new Diff\DiffResult($diffObjects, $patch, $outcome);
    }

    public function applyDiff(
        Diff\DiffResult $diffResult,
        Resource\Collector\DirectoryCollector $base,
    ): bool {
        if (!$diffResult->getOutcome()->isSuccessful()) {
            throw Exception\PatchFailureException::forConflictedDiff($diffResult);
        }

        $repo = new Resource\Collector\DirectoryCollector($this->repository->getPath());

        $this->filesystem->remove($base->collectFiles());
        $this->filesystem->mirror($this->repository->getPath(), $base->getBaseDirectory(), $repo->collectFiles());

        return true;
    }

    private function commitSourceFiles(Resource\Collector\CollectorInterface $source): void
    {
        foreach ($source as $path => $contents) {
            $this->filesystem->dumpFile(
                Filesystem\Path::join($this->repository->getPath(), $path),
                $contents,
            );
        }

        $this->repository->commit('Add source files', true, allowEmpty: true);
    }

    private function commitBaseFiles(Resource\Collector\DirectoryCollector $base): void
    {
        $repo = new Resource\Collector\DirectoryCollector($this->repository->getPath());

        // Go to base branch
        $this->repository->checkout(self::BASE_BRANCH, true);

        // Add base files
        $this->filesystem->remove($repo->collectFiles());
        $this->filesystem->mirror($base->getBaseDirectory(), $this->repository->getPath(), $base->collectFiles());

        // Commit base files
        $this->repository->commit('Add base files', true, allowEmpty: true);

        // Go back to main branch
        $this->repository->checkout(self::MAIN_BRANCH);
    }

    private function commitTargetFiles(Resource\Collector\CollectorInterface $target): void
    {
        $repo = new Resource\Collector\DirectoryCollector($this->repository->getPath());

        // Clean up repository
        $this->filesystem->remove($repo->collectFiles());

        // Add target files
        foreach ($target as $path => $contents) {
            $this->filesystem->dumpFile(
                Filesystem\Path::join($this->repository->getPath(), $path),
                $contents,
            );
        }

        // Commit target files
        $this->repository->commit('Add target files', true, allowEmpty: true);
    }

    private function merge(): Diff\Outcome
    {
        $mainBranch = $this->repository->getBranch(self::MAIN_BRANCH) ?? $this->repository->getMainBranch();

        try {
            $this->repository->checkout(self::BASE_BRANCH);
            $this->repository->merge($mainBranch, 'Migrate base files', 'no-ff');

            // Enforce working directory to be dirty (we need this to correctly
            // determine the current Git status in the DiffResult class)
            $this->repository->reset('HEAD~1', []);

            $outcome = Diff\Outcome::successful();
        } catch (\Exception $exception) {
            $errorMessage = explode('with reason: ', $exception->getMessage());
            $outcome = Diff\Outcome::failed(end($errorMessage));
        }

        return $outcome;
    }

    /**
     * @return list<Diff\DiffObject>
     */
    private function calculateDiffObjects(Status\Status $status): array
    {
        $diff = $this->repository->commit('Add changed files', allowEmpty: true)->getDiff('HEAD', 'HEAD~1');
        $diffObjects = [];

        /** @var Status\StatusFile $statusFile */
        foreach ($status->all() as $statusFile) {
            $diffMode = self::DIFF_MODES[$statusFile->getWorkingTreeStatus()] ?? self::DIFF_MODES[$statusFile->getIndexStatus()] ?? null;
            $destinationPath = $statusFile->getRenamed() ?? $statusFile->getName();

            // Skip irrelevant statuses
            if (null === $diffMode) {
                continue;
            }

            /** @var Objects\Diff\DiffObject $diffObject */
            foreach ($diff as $diffObject) {
                if ($destinationPath === $diffObject->getDestinationPath()) {
                    $diffObjects[] = new Diff\DiffObject(
                        $diffMode,
                        $diffObject->getOriginalPath(),
                        $diffObject->getDestinationPath(),
                        $diffObject->getChunks(),
                    );
                }
            }
        }

        return $diffObjects;
    }

    private function initializeRepository(): Repository
    {
        $repoDir = Helper\FilesystemHelper::getNewTemporaryDirectory();

        // Create temporary directory for Git repository
        $this->filesystem->mkdir($repoDir);

        // Create repository
        $repository = new Repository($repoDir);

        // Initialize repository
        $repository->addGlobalConfig('commit.gpgsign', false);
        $repository->addGlobalConfig('user.name', 'CPS Migrator');
        $repository->addGlobalConfig('user.email', '');
        $repository->init(false, self::MAIN_BRANCH);

        return $repository;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        try {
            $this->filesystem->remove($this->repository->getPath());
        } catch (Filesystem\Exception\IOException) {
            // Ignore failures
        }
    }
}
