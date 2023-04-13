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

namespace CPSIT\Migrator\Tests\Resource\Collector;

use CPSIT\Migrator as Src;
use PHPUnit\Framework;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function dirname;
use function iterator_to_array;
use function scandir;
use function sort;

/**
 * DirectoryCollectorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DirectoryCollectorTest extends Framework\TestCase
{
    private string $baseDirectory;
    private Src\Resource\Collector\DirectoryCollector $subject;

    /**
     * @var list<string>
     */
    private array $expected;

    protected function setUp(): void
    {
        $this->baseDirectory = dirname(__DIR__, 2).'/Fixtures/TestFiles/Source';
        $this->subject = new Src\Resource\Collector\DirectoryCollector($this->baseDirectory);

        $files = scandir($this->baseDirectory);

        self::assertNotFalse($files);

        $this->expected = array_values(array_diff($files, ['..', '.']));

        sort($this->expected);
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfBaseDirectoryIsMissingOrInvalid(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidResourceException::create('foo'));

        new Src\Resource\Collector\DirectoryCollector('foo');
    }

    #[Framework\Attributes\Test]
    public function collectReturnsCollection(): void
    {
        $actual = array_keys($this->subject->collect());

        sort($actual);

        self::assertSame($this->expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function collectFilesReturnsFilesWithinBaseDirectory(): void
    {
        $fileObjects = array_map(
            fn (string $file) => new SplFileInfo(
                Path::join($this->baseDirectory, $file),
                '',
                $file,
            ),
            $this->expected,
        );

        $actual = array_values(iterator_to_array($this->subject->collectFiles()));

        sort($actual);

        self::assertEquals($fileObjects, $actual);
    }

    #[Framework\Attributes\Test]
    public function getBaseDirectoryReturnsBaseDirectory(): void
    {
        self::assertSame($this->baseDirectory, $this->subject->getBaseDirectory());
    }

    #[Framework\Attributes\Test]
    public function subjectIsIterable(): void
    {
        $actual = array_keys(iterator_to_array($this->subject));

        sort($actual);

        self::assertSame($this->expected, $actual);
    }
}
