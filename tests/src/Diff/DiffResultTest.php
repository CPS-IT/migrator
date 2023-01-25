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
use PHPUnit\Framework;

/**
 * DiffResultTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DiffResultTest extends Framework\TestCase
{
    /**
     * @var list<Src\Diff\DiffObject>
     */
    private array $diffObjects;
    private Src\Diff\DiffResult $subject;

    protected function setUp(): void
    {
        $this->diffObjects = [
            new Src\Diff\DiffObject(Src\Diff\DiffMode::Added, 'foo', 'foo', []),
            new Src\Diff\DiffObject(Src\Diff\DiffMode::Deleted, 'baz', 'baz', []),
        ];
        $this->subject = new Src\Diff\DiffResult(
            $this->diffObjects,
            'foo',
            Src\Diff\Outcome::failed('error'),
        );
    }

    #[Framework\Attributes\Test]
    public function getDiffObjectsReturnsDiffObjects(): void
    {
        self::assertSame($this->diffObjects, $this->subject->getDiffObjects());
    }

    #[Framework\Attributes\Test]
    public function getPatchReturnsPatch(): void
    {
        self::assertSame('foo', $this->subject->getPatch());
    }

    #[Framework\Attributes\Test]
    public function getOutcomeReturnsOutcome(): void
    {
        self::assertEquals(Src\Diff\Outcome::failed('error'), $this->subject->getOutcome());
    }
}
