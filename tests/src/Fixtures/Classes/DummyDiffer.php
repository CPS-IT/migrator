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

namespace CPSIT\Migrator\Tests\Fixtures\Classes;

use CPSIT\Migrator\Diff;
use CPSIT\Migrator\Exception;
use CPSIT\Migrator\Resource;

/**
 * DummyDiffer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyDiffer implements Diff\Differ\Differ
{
    public ?Diff\DiffResult $expectedResult = null;
    public ?Exception\PatchFailureException $expectedException = null;
    public bool $diffWasApplied = false;

    public function generateDiff(
        Resource\Collector\CollectorInterface $source,
        Resource\Collector\CollectorInterface $target,
        Resource\Collector\DirectoryCollector $base,
    ): Diff\DiffResult {
        $this->diffWasApplied = false;

        if (null !== $this->expectedResult) {
            return $this->expectedResult;
        }

        return new Diff\DiffResult([], 'dummy', Diff\Outcome::successful());
    }

    public function applyDiff(
        Diff\DiffResult $diffResult,
        Resource\Collector\DirectoryCollector $base,
    ): bool {
        if (null !== $this->expectedException) {
            throw $this->expectedException;
        }

        $this->diffWasApplied = true;

        return true;
    }
}
