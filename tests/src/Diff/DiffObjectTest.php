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

namespace CPSIT\Migrator\Tests\Diff;

use CPSIT\Migrator as Src;
use GitElephant\Objects;
use PHPUnit\Framework;

/**
 * DiffObjectTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DiffObjectTest extends Framework\TestCase
{
    /**
     * @var array<string>
     */
    private array $lines;
    private Src\Diff\DiffObject $subject;

    protected function setUp(): void
    {
        $this->lines = [
            '@@ -1,6 +1,6 @@',
            ' {',
            '     "name": "cpsit/migrator-test",',
            '     "require-dev": {',
            '-        "phpunit/phpunit": "^9.5"',
            '+        "phpunit/phpunit": "^10.0"',
            '     }',
            ' }',
        ];
        $this->subject = new Src\Diff\DiffObject(
            Src\Diff\DiffMode::Added,
            'foo',
            'baz',
            [
                new Objects\Diff\DiffChunk($this->lines),
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function getModeReturnsDiffMode(): void
    {
        self::assertSame(Src\Diff\DiffMode::Added, $this->subject->getMode());
    }

    #[Framework\Attributes\Test]
    public function getOriginalPathReturnsOriginalPath(): void
    {
        self::assertSame('foo', $this->subject->getOriginalPath());
    }

    #[Framework\Attributes\Test]
    public function getDestinationPathReturnsDestinationPath(): void
    {
        self::assertSame('baz', $this->subject->getDestinationPath());
    }

    #[Framework\Attributes\Test]
    public function getChunksReturnsDiffChunks(): void
    {
        $expected = [
            new Objects\Diff\DiffChunk($this->lines),
        ];

        self::assertEquals($expected, $this->subject->getChunks());
    }
}
