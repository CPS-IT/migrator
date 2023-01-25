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
use Traversable;

use function array_map;
use function array_merge;

/**
 * ChainedCollector.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ChainedCollector implements CollectorInterface
{
    /**
     * @param non-empty-list<CollectorInterface> $collectors
     *
     * @throws Exception\InvalidCollectorException
     */
    public function __construct(
        private readonly array $collectors,
    ) {
        /* @phpstan-ignore-next-line */
        if ([] === $this->collectors) {
            throw Exception\InvalidCollectorException::forEmptyCollectorChain();
        }
    }

    public function collect(): array
    {
        return array_merge(
            ...array_map(
                static fn (CollectorInterface $collector) => $collector->collect(),
                $this->collectors,
            ),
        );
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collect());
    }
}
