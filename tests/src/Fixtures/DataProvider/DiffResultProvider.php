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

namespace CPSIT\Migrator\Tests\Fixtures\DataProvider;

use CPSIT\Migrator\Diff;
use CPSIT\Migrator\Resource;

use function count;

/**
 * DiffResultProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DiffResultProvider
{
    public static function createFromFixtures(
        Diff\Differ\Differ $differ = new Diff\Differ\GitDiffer(),
    ): Diff\DiffResult {
        return $differ->generateDiff(
            new Resource\Collector\DirectoryCollector(self::getFixtureSource()),
            new Resource\Collector\DirectoryCollector(self::getFixtureTarget()),
            new Resource\Collector\DirectoryCollector(self::getFixtureBase()),
        );
    }

    public static function createSuccessful(int $numberOfDiffObjects = 3): Diff\DiffResult
    {
        $diffObjects = self::createDiffObjects($numberOfDiffObjects);

        return new Diff\DiffResult($diffObjects, '###patch string###', Diff\Outcome::successful());
    }

    public static function createFailed(int $numberOfDiffObjects = 3): Diff\DiffResult
    {
        $diffObjects = self::createDiffObjects($numberOfDiffObjects);

        return new Diff\DiffResult($diffObjects, '###patch string###', Diff\Outcome::failed('something went wrong'));
    }

    public static function getFixtureSource(): string
    {
        return dirname(__DIR__).'/TestFiles/Source';
    }

    public static function getFixtureTarget(): string
    {
        return dirname(__DIR__).'/TestFiles/Target';
    }

    public static function getFixtureBase(): string
    {
        return dirname(__DIR__).'/TestFiles/Base';
    }

    /**
     * @return list<Diff\DiffObject>
     */
    private static function createDiffObjects(int $numberOfDiffObjects): array
    {
        $diffObjects = [];
        $diffModes = Diff\DiffMode::cases();

        for ($i = 0; $i < $numberOfDiffObjects; ++$i) {
            $diffObjects[] = new Diff\DiffObject(
                $diffModes[$i % count($diffModes)],
                '/foo/'.$i,
                '/baz/'.$i,
                [],
            );
        }

        return $diffObjects;
    }
}
