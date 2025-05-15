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

namespace CPSIT\Migrator\Tests\Formatter\Decorator;

use CPSIT\Migrator as Src;
use CPSIT\Migrator\Tests;
use Generator;
use GitElephant\Objects;
use PHPUnit\Framework;

/**
 * DiffDecoratorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DiffDecoratorTest extends Framework\TestCase
{
    private Src\Formatter\Decorator\DiffDecorator $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Formatter\Decorator\DiffDecorator();
    }

    #[Framework\Attributes\Test]
    public function decorateOriginalPathReturnsDevNullOnNullishOriginalPath(): void
    {
        self::assertSame('--- /dev/null', $this->subject->decorateOriginalPath(null));
    }

    #[Framework\Attributes\Test]
    public function decorateOriginalPathReturnsDecoratedOriginalPath(): void
    {
        self::assertSame('--- a/foo/baz', $this->subject->decorateOriginalPath('foo/baz'));
    }

    #[Framework\Attributes\Test]
    public function decorateDestinationPathReturnsDevNullOnNullishDestinationPath(): void
    {
        self::assertSame('+++ /dev/null', $this->subject->decorateDestinationPath(null));
    }

    #[Framework\Attributes\Test]
    public function decorateDestinationPathReturnsDecoratedDestinationPath(): void
    {
        self::assertSame('+++ b/foo/baz', $this->subject->decorateDestinationPath('foo/baz'));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('decorateChunkLineReturnsDecoratedDiffChunkLineDataProvider')]
    public function decorateChunkLineReturnsDecoratedDiffChunkLine(
        Objects\Diff\DiffChunkLine $chunkLine,
        ?string $expected,
    ): void {
        self::assertSame($expected, $this->subject->decorateChunkLine($chunkLine));
    }

    /**
     * @return Generator<string, array{Objects\Diff\DiffChunkLine, string|null}>
     */
    public static function decorateChunkLineReturnsDecoratedDiffChunkLineDataProvider(): Generator
    {
        yield 'added' => [
            new Objects\Diff\DiffChunkLineAdded(123, 'foo'),
            '+foo',
        ];
        yield 'deleted' => [
            new Objects\Diff\DiffChunkLineDeleted(123, 'foo'),
            '-foo',
        ];
        yield 'unchanged' => [
            /* @phpstan-ignore method.internal */
            new Objects\Diff\DiffChunkLineUnchanged(123, 123, 'foo'),
            ' foo',
        ];
        yield 'dummy' => [
            new Tests\Fixtures\Classes\DummyDiffChunkLine('foo'),
            null,
        ];
    }
}
