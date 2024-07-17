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

namespace CPSIT\Migrator\Tests\Diff\Differ;

use CPSIT\Migrator as Src;
use CPSIT\Migrator\Tests;
use GitElephant\Repository;
use PHPUnit\Framework;
use ReflectionProperty;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function iterator_to_array;
use function sort;

/**
 * GitDifferTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GitDifferTest extends Framework\TestCase
{
    private Src\Diff\Differ\GitDiffer $subject;
    private Src\Resource\Collector\DirectoryCollector $source;
    private Src\Resource\Collector\DirectoryCollector $target;
    private Src\Resource\Collector\DirectoryCollector $base;
    private Src\Resource\Collector\DirectoryCollector $expected;

    protected function setUp(): void
    {
        $this->subject = new Src\Diff\Differ\GitDiffer();
        $this->source = new Src\Resource\Collector\DirectoryCollector(dirname(__DIR__, 2).'/Fixtures/TestFiles/Source');
        $this->target = new Src\Resource\Collector\DirectoryCollector(dirname(__DIR__, 2).'/Fixtures/TestFiles/Target');
        $this->base = new Src\Resource\Collector\DirectoryCollector(dirname(__DIR__, 2).'/Fixtures/TestFiles/Base');
        $this->expected = new Src\Resource\Collector\DirectoryCollector(dirname(__DIR__, 2).'/Fixtures/TestFiles/Expected');
    }

    #[Framework\Attributes\Test]
    public function constructorInitializesNewRepository(): void
    {
        $repositoryPath = $this->getRepositoryPathFromReflection();

        self::assertDirectoryExists($repositoryPath.'/.git');
    }

    #[Framework\Attributes\Test]
    public function generateDiffGeneratesDiffBetweenGivenCodeBases(): void
    {
        $actual = $this->subject->generateDiff($this->source, $this->target, $this->base);

        self::assertCount(2, $actual->getDiffObjects());
        self::assertSame('.gitignore', $actual->getDiffObjects()[0]->getOriginalPath());
        self::assertSame(Src\Diff\DiffMode::Deleted, $actual->getDiffObjects()[0]->getMode());
        self::assertSame('composer.json', $actual->getDiffObjects()[1]->getOriginalPath());
        self::assertSame(Src\Diff\DiffMode::Modified, $actual->getDiffObjects()[1]->getMode());
    }

    #[Framework\Attributes\Test]
    public function generateDiffReturnsDiffObjectWithFailedOutcomeOnFailedMerge(): void
    {
        $base = new Src\Resource\Collector\DirectoryCollector(__DIR__);

        $actual = $this->subject->generateDiff($this->source, $this->target, $base);

        self::assertFalse($actual->getOutcome()->isSuccessful());
    }

    #[Framework\Attributes\Test]
    public function applyDiffThrowsExceptionIfDiffResultIsNotSuccessful(): void
    {
        $diffResult = Tests\Fixtures\DataProvider\DiffResultProvider::createFailed();

        $this->expectExceptionObject(Src\Exception\PatchFailureException::forConflictedDiff($diffResult));

        $this->subject->applyDiff($diffResult, $this->base);
    }

    #[Framework\Attributes\Test]
    public function applyDiffMirrorsDiffResultToBaseDirectory(): void
    {
        $tempDir = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        // Copy original base to temporary base
        $filesystem = new Filesystem\Filesystem();
        $filesystem->mirror($this->base->getBaseDirectory(), $tempDir);
        $tempBase = new Src\Resource\Collector\DirectoryCollector($tempDir);

        // Generate diff result
        $diffResult = $this->subject->generateDiff($this->source, $this->target, $tempBase);

        $this->subject->applyDiff($diffResult, $tempBase);

        try {
            self::assertDirectoriesAreEqual($this->expected, $tempBase);
        } finally {
            $filesystem->remove($tempDir);
        }
    }

    #[Framework\Attributes\Test]
    public function destructorRemovesRepository(): void
    {
        $repositoryPath = $this->getRepositoryPathFromReflection();

        self::assertDirectoryExists($repositoryPath);

        // Trigger __destruct() on subject
        unset($this->subject);

        self::assertDirectoryDoesNotExist($repositoryPath);
    }

    private function getRepositoryPathFromReflection(): string
    {
        $reflectionProperty = new ReflectionProperty($this->subject, 'repository');
        $repository = $reflectionProperty->getValue($this->subject);

        self::assertInstanceOf(Repository::class, $repository);

        return $repository->getPath();
    }

    private static function assertDirectoriesAreEqual(
        Src\Resource\Collector\DirectoryCollector $expected,
        Src\Resource\Collector\DirectoryCollector $actual,
    ): void {
        $expectedFiles = iterator_to_array($expected->collectFiles(), false);
        $actualFiles = iterator_to_array($actual->collectFiles(), false);

        self::assertSame(
            self::mapSplFileInfos($expectedFiles),
            self::mapSplFileInfos($actualFiles),
            'Directories are not equal',
        );
    }

    /**
     * @param list<Finder\SplFileInfo> $fileInfos
     *
     * @return list<string>
     */
    private static function mapSplFileInfos(array $fileInfos): array
    {
        $files = [];

        foreach ($fileInfos as $fileObject) {
            $files[] = $fileObject->getRelativePathname();
        }

        sort($files);

        return $files;
    }
}
