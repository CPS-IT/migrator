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

namespace CPSIT\Migrator\Tests\Command;

use CPSIT\Migrator as Src;
use CPSIT\Migrator\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;
use Symfony\Component\Filesystem\Path;

use function dirname;
use function getcwd;
use function implode;
use function preg_match;
use function unlink;

/**
 * MigrateCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MigrateCommandTest extends Framework\TestCase
{
    private Src\Tests\Fixtures\Classes\DummyDiffer $differ;
    private Console\Tester\CommandTester $commandTester;
    private string $source;
    private string $target;
    private string $base;

    protected function setUp(): void
    {
        $this->differ = new Tests\Fixtures\Classes\DummyDiffer();
        $this->commandTester = new Console\Tester\CommandTester(new Src\Command\MigrateCommand(new Src\Migrator($this->differ)));
        $this->source = dirname(__DIR__).'/Fixtures/TestFiles/Source';
        $this->target = dirname(__DIR__).'/Fixtures/TestFiles/Target';
        $this->base = dirname(__DIR__).'/Fixtures/TestFiles/Base';
    }

    #[Framework\Attributes\Test]
    public function executeCanHandleRelativePaths(): void
    {
        $this->commandTester->execute([
            'base-directory' => Path::makeRelative($this->base, (string) getcwd()),
            'source-directory' => Path::makeRelative($this->source, (string) getcwd()),
            'target-directory' => Path::makeRelative($this->target, (string) getcwd()),
            '--dry-run' => true,
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());
    }

    #[Framework\Attributes\Test]
    public function executeCanHandleFiles(): void
    {
        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source.'/composer.json',
            'target-directory' => $this->target.'/composer.json',
            '--dry-run' => true,
        ]);

        self::assertSame(0, $this->commandTester->getStatusCode());
    }

    #[Framework\Attributes\Test]
    public function executeThrowsExceptionIfResourceDoesNotExist(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidResourceException::create('/foo'));

        $this->commandTester->execute([
            'base-directory' => '/foo',
            'source-directory' => $this->source,
            'target-directory' => $this->target,
            '--dry-run' => true,
        ]);
    }

    #[Framework\Attributes\Test]
    public function executeDoesNotPerformMigrationsOnDryRun(): void
    {
        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source,
            'target-directory' => $this->target,
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertFalse($this->differ->diffWasApplied);
        self::assertStringContainsString('Migration was successful.', $output);
        self::assertStringContainsString('Omit the --dry-run parameter to apply migrations.', $output);
    }

    #[Framework\Attributes\Test]
    public function executeFailsOnFailedMigration(): void
    {
        $this->differ->expectedResult = new Src\Diff\DiffResult([], 'foo', Src\Diff\Outcome::failed('something went wrong'));

        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source,
            'target-directory' => $this->target,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertFalse($this->differ->diffWasApplied);
        self::assertStringContainsString('Migration failed, no patches were applied.', $output);
        self::assertStringContainsString('something went wrong', $output);
    }

    #[Framework\Attributes\Test]
    public function executeFailsOnPatchFailure(): void
    {
        $diffResult = new Src\Diff\DiffResult([], 'foo', Src\Diff\Outcome::failed('something went wrong'));

        $this->differ->expectedException = Src\Exception\PatchFailureException::forConflictedDiff($diffResult);

        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source,
            'target-directory' => $this->target,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertFalse($this->differ->diffWasApplied);
        self::assertStringContainsString('Migration failed, no patches were applied.', $output);
        self::assertStringContainsString('something went wrong', $output);
    }

    #[Framework\Attributes\Test]
    public function executePrintsDiffObjects(): void
    {
        $diffResult = $this->createDiffResult();

        $this->differ->expectedResult = $diffResult;

        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source,
            'target-directory' => $this->target,
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            implode(PHP_EOL, [
                '--- a/composer.json',
                '+++ b/composer.json',
                '@@ -4,6 +4,6 @@',
            ]),
            $output,
        );
        self::assertStringContainsString(
            implode(PHP_EOL, [
                '--- a/.gitignore',
                '+++ /dev/null',
                ' DELETED ',
            ]),
            $output,
        );
    }

    #[Framework\Attributes\Test]
    public function executePrintsPatchFileOnFailedMigration(): void
    {
        $this->differ->expectedResult = new Src\Diff\DiffResult([], 'foo', Src\Diff\Outcome::failed('error'));

        $this->commandTester->setInputs(['yes']);

        $this->commandTester->execute([
            'base-directory' => $this->base,
            'source-directory' => $this->source,
            'target-directory' => $this->target,
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Do you want to save a patch file to the current working directory?', $output);
        self::assertStringContainsString('Patch file written', $output);
        self::assertSame(1, preg_match('/Patch file written to (\\S+)/', $output, $matches));
        self::assertFileExists($matches[1]);
        self::assertStringEqualsFile($matches[1], 'foo');

        unlink($matches[1]);
    }

    private function createDiffResult(): Src\Diff\DiffResult
    {
        $gitDiffer = new Src\Diff\Differ\GitDiffer();
        $source = new Src\Resource\Collector\DirectoryCollector($this->source);
        $target = new Src\Resource\Collector\DirectoryCollector($this->target);
        $base = new Src\Resource\Collector\DirectoryCollector($this->base);

        return $gitDiffer->generateDiff($source, $target, $base);
    }
}
