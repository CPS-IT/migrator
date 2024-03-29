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

namespace CPSIT\Migrator\Tests\Exception;

use CPSIT\Migrator as Src;
use CPSIT\Migrator\Tests;
use PHPUnit\Framework;

/**
 * PatchFailureExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PatchFailureExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function createReturnsExceptionForInvalidType(): void
    {
        $diffResult = Tests\Fixtures\DataProvider\DiffResultProvider::createFailed();

        $actual = Src\Exception\PatchFailureException::forConflictedDiff($diffResult);

        self::assertSame('Unable to apply patch on a conflicted diff.', $actual->getMessage());
        self::assertSame(1678361343, $actual->getCode());
        self::assertSame($diffResult, $actual->getDiffResult());
    }

    #[Framework\Attributes\Test]
    public function createModifiesDiffResultIfGivenOutcomeIsSuccessful(): void
    {
        $diffResult = Tests\Fixtures\DataProvider\DiffResultProvider::createSuccessful();

        $actual = Src\Exception\PatchFailureException::forConflictedDiff($diffResult);

        self::assertNotSame($diffResult, $actual->getDiffResult());
        self::assertSame('Unable to apply patch on a conflicted diff.', $actual->getDiffResult()->getOutcome()->getMessage());
    }
}
