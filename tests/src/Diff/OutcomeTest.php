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
 * OutcomeTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class OutcomeTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function successfulReturnsSuccessfulOutcome(): void
    {
        $actual = Src\Diff\Outcome::successful();

        self::assertTrue($actual->isSuccessful());
        self::assertNull($actual->getMessage());
    }

    #[Framework\Attributes\Test]
    public function failedReturnsFailedOutcome(): void
    {
        $actual = Src\Diff\Outcome::failed('error');

        self::assertFalse($actual->isSuccessful());
        self::assertSame('error', $actual->getMessage());
    }
}
