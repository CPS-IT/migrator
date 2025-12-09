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

namespace CPSIT\Migrator\Resource\Collector;

use ArrayIterator;
use CPSIT\Migrator\Exception;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;
use Traversable;

use function dirname;
use function file_exists;

/**
 * FileCollector.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class FileCollector implements CollectorInterface
{
    private string $baseDirectory;
    private Finder\SplFileInfo $file;

    /**
     * @throws Exception\InvalidResourceException
     */
    public function __construct(string $file, ?string $baseDirectory = null)
    {
        if (!file_exists($file)) {
            throw Exception\InvalidResourceException::create($file);
        }

        $this->baseDirectory = $baseDirectory ?? dirname($file);

        $relativePathname = Filesystem\Path::makeRelative($file, $this->baseDirectory);

        $this->file = new Finder\SplFileInfo($file, dirname($relativePathname), $relativePathname);
    }

    public function collect(): array
    {
        return [
            $this->file->getRelativePathname() => $this->file->getContents(),
        ];
    }

    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collect());
    }
}
