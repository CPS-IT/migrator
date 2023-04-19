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

namespace CPSIT\Migrator\Tests\Formatter;

use CPSIT\Migrator as Src;
use CPSIT\Migrator\Tests;
use PHPUnit\Framework;

/**
 * CliFormatterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CliFormatterTest extends Framework\TestCase
{
    private Src\Formatter\CliFormatter $subject;
    private Src\Diff\DiffResult $diffResult;

    protected function setUp(): void
    {
        $this->subject = new Src\Formatter\CliFormatter();
        $this->diffResult = Tests\Fixtures\DataProvider\DiffResultProvider::createFromFixtures();
    }

    #[Framework\Attributes\Test]
    public function formatReturnsNullIfNoDiffObjectsAreAvailable(): void
    {
        $diffResult = Tests\Fixtures\DataProvider\DiffResultProvider::createSuccessful(0);

        self::assertNull($this->subject->format($diffResult));
    }

    #[Framework\Attributes\Test]
    public function formatFormatsDiffObjectHeader(): void
    {
        $actual = $this->subject->format($this->diffResult);

        self::assertIsString($actual);
        self::assertStringContainsString(
            implode(PHP_EOL, [
                '<fg=black;options=bold>--- a/composer.json</>',
                '<fg=black;options=bold>+++ b/composer.json</>',
            ]),
            $actual,
        );
        self::assertStringContainsString(
            implode(PHP_EOL, [
                '<fg=black;options=bold>--- a/.gitignore</>',
                '<fg=black;options=bold>+++ /dev/null</>',
                '<bg=red;fg=black> DELETED </>',
            ]),
            $actual,
        );
    }

    #[Framework\Attributes\Test]
    public function formatFormatsDiffChunk(): void
    {
        $actual = $this->subject->format($this->diffResult);

        self::assertIsString($actual);
        self::assertStringContainsString('<fg=cyan>@@ -4,6 +4,6 @@</>', $actual);
        self::assertMatchesRegularExpression('#<fg=red>-\s+"phpunit/phpunit": "\^9\.5"</>#', $actual);
        self::assertMatchesRegularExpression('#<fg=green>\+\s+"phpunit/phpunit": "\^10\.0"</>#', $actual);
    }
}
