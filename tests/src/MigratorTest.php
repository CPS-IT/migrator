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

namespace CPSIT\Migrator\Tests;

use CPSIT\Migrator as Src;
use PHPUnit\Framework;

/**
 * MigratorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MigratorTest extends Framework\TestCase
{
    private Fixtures\Classes\DummyDiffer $differ;
    private Src\Diff\DiffResult $diffResult;
    private Src\Resource\Collector\CollectorInterface $source;
    private Src\Resource\Collector\CollectorInterface $target;
    private Src\Resource\Collector\DirectoryCollector $base;
    private Src\Migrator $subject;

    protected function setUp(): void
    {
        $this->differ = new Fixtures\Classes\DummyDiffer();
        $this->diffResult = Fixtures\DataProvider\DiffResultProvider::createSuccessful();
        $this->source = new Src\Resource\Collector\ArrayCollector([]);
        $this->target = new Src\Resource\Collector\ArrayCollector([]);
        $this->base = new Src\Resource\Collector\DirectoryCollector(__DIR__);
        $this->subject = new Src\Migrator($this->differ);
    }

    #[Framework\Attributes\Test]
    public function migrateReturnsDiffResult(): void
    {
        $this->differ->expectedResult = $this->diffResult;

        self::assertFalse($this->differ->diffWasApplied);
        self::assertSame($this->diffResult, $this->subject->migrate($this->source, $this->target, $this->base));
        self::assertTrue($this->differ->diffWasApplied);
    }

    #[Framework\Attributes\Test]
    public function migrateDoesNotApplyDiffIfPerformMigrationsIsTurnedOff(): void
    {
        $this->subject->performMigrations(false);

        self::assertFalse($this->differ->diffWasApplied);

        $this->subject->migrate($this->source, $this->target, $this->base);

        self::assertFalse($this->differ->diffWasApplied);
    }
}
