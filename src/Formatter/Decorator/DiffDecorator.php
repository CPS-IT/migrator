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

namespace CPSIT\Migrator\Formatter\Decorator;

use GitElephant\Objects;

use function str_repeat;

/**
 * DiffDecorator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DiffDecorator
{
    public function decorateOriginalPath(?string $originalPath): string
    {
        if (null === $originalPath) {
            $originalPath = '/dev/null';
        } else {
            $originalPath = 'a/'.$originalPath;
        }

        return $this->decoratePath($originalPath, '-');
    }

    public function decorateDestinationPath(?string $destinationPath): string
    {
        if (null === $destinationPath) {
            $destinationPath = '/dev/null';
        } else {
            $destinationPath = 'b/'.$destinationPath;
        }

        return $this->decoratePath($destinationPath, '+');
    }

    public function decorateChunkLine(Objects\Diff\DiffChunkLine $chunkLine): ?string
    {
        return match ($chunkLine::class) {
            Objects\Diff\DiffChunkLineUnchanged::class => ' '.$chunkLine->getContent(),
            Objects\Diff\DiffChunkLineAdded::class => '+'.$chunkLine->getContent(),
            Objects\Diff\DiffChunkLineDeleted::class => '-'.$chunkLine->getContent(),
            default => null,
        };
    }

    private function decoratePath(string $path, string $diffSign): string
    {
        return str_repeat($diffSign, 3).' '.$path;
    }
}
