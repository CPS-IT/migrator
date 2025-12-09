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
use Symfony\Component\Finder;
use Traversable;

use function file_exists;
use function is_dir;

/**
 * DirectoryCollector.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class DirectoryCollector implements CollectorInterface
{
    /**
     * @throws Exception\InvalidResourceException
     */
    public function __construct(
        private string $baseDirectory,
    ) {
        if (!file_exists($this->baseDirectory) || !is_dir($this->baseDirectory)) {
            throw Exception\InvalidResourceException::create($this->baseDirectory);
        }
    }

    public function collect(): array
    {
        $collection = [];

        foreach ($this->createFinder() as $file) {
            $collection[$file->getRelativePathname()] = $file->getContents();
        }

        return $collection;
    }

    /**
     * @return Traversable<Finder\SplFileInfo>
     */
    public function collectFiles(): Traversable
    {
        return $this->createFinder();
    }

    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collect());
    }

    private function createFinder(): Finder\Finder
    {
        return Finder\Finder::create()
            ->files()
            ->in($this->baseDirectory)
            ->name('/.*/')
            ->ignoreVCSIgnored(true)
            ->ignoreDotFiles(false)
        ;
    }
}
