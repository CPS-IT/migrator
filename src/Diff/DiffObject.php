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

namespace CPSIT\Migrator\Diff;

use GitElephant\Objects;

/**
 * DiffObject.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DiffObject
{
    /**
     * @param array<Objects\Diff\DiffChunk> $chunks
     */
    public function __construct(
        private readonly DiffMode $mode,
        private readonly ?string $originalPath,
        private readonly ?string $destinationPath,
        private readonly array $chunks,
    ) {}

    public function getMode(): DiffMode
    {
        return $this->mode;
    }

    public function getOriginalPath(): ?string
    {
        return $this->originalPath;
    }

    public function getDestinationPath(): ?string
    {
        return $this->destinationPath;
    }

    /**
     * @return array<Objects\Diff\DiffChunk>
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }
}
