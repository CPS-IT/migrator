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

use function iterator_to_array;

/**
 * ChainedCollectorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ChainedCollectorTest extends Framework\TestCase
{
    private Src\Resource\Collector\ChainedCollector $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Resource\Collector\ChainedCollector([
            new Src\Resource\Collector\ArrayCollector(['foo' => 'baz']),
            new Src\Resource\Collector\CallbackCollector(static fn () => ['baz' => 'foo']),
        ]);
    }

    #[Framework\Attributes\Test]
    public function constructorThrowsExceptionIfCollectorChainIsEmpty(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidCollectorException::forEmptyCollectorChain());

        /* @phpstan-ignore argument.type, new.resultUnused */
        new Src\Resource\Collector\ChainedCollector([]);
    }

    #[Framework\Attributes\Test]
    public function collectReturnsCollection(): void
    {
        $expected = [
            'foo' => 'baz',
            'baz' => 'foo',
        ];

        self::assertSame($expected, $this->subject->collect());
    }

    #[Framework\Attributes\Test]
    public function subjectIsIterable(): void
    {
        $expected = [
            'foo' => 'baz',
            'baz' => 'foo',
        ];

        self::assertSame($expected, iterator_to_array($this->subject));
    }
}
