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
use PHPUnit\Framework;

/**
 * InvalidResourceExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidResourceExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function createReturnsExceptionForInvalidResource(): void
    {
        $actual = Src\Exception\InvalidResourceException::create('foo');

        self::assertSame('The resource "foo" is invalid or does not exist.', $actual->getMessage());
        self::assertSame(1674633373, $actual->getCode());
    }
}
