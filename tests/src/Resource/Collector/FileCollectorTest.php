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

use function basename;
use function file_get_contents;
use function iterator_to_array;

/**
 * FileCollectorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FileCollectorTest extends Framework\TestCase
{
    private Src\Resource\Collector\FileCollector $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Resource\Collector\FileCollector(__FILE__);
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidResourceException::create('foo'));

        new Src\Resource\Collector\FileCollector('foo');
    }

    #[Framework\Attributes\Test]
    public function collectReturnsCollection(): void
    {
        $expected = [
            basename(__FILE__) => file_get_contents(__FILE__),
        ];

        self::assertSame($expected, $this->subject->collect());
    }

    #[Framework\Attributes\Test]
    public function getBaseDirectoryReturnsBaseDirectory(): void
    {
        self::assertSame(__DIR__, $this->subject->getBaseDirectory());
    }

    #[Framework\Attributes\Test]
    public function subjectIsIterable(): void
    {
        $expected = [
            basename(__FILE__) => file_get_contents(__FILE__),
        ];

        self::assertSame($expected, iterator_to_array($this->subject));
    }
}
