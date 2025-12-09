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

namespace CPSIT\Migrator\Formatter;

use CPSIT\Migrator\Diff;
use Generator;
use GitElephant\Objects;

use function implode;

/**
 * TextFormatter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class TextFormatter implements Formatter
{
    private Decorator\DiffDecorator $decorator;

    public function __construct()
    {
        $this->decorator = new Decorator\DiffDecorator();
    }

    public function format(Diff\DiffResult $diffResult): ?string
    {
        $diffObjects = $diffResult->getDiffObjects();
        $outputLines = [];

        // Early return if no files changed
        if ([] === $diffObjects) {
            return null;
        }

        foreach ($this->formatDiffObjects($diffObjects) as $diffObjectLine) {
            $outputLines[] = $diffObjectLine;
        }

        return implode(PHP_EOL, $outputLines);
    }

    /**
     * @param array<Diff\DiffObject> $diffObjects
     *
     * @return Generator<string>
     */
    private function formatDiffObjects(array $diffObjects): Generator
    {
        foreach ($diffObjects as $diffObject) {
            yield from $this->formatDiffObjectHeader($diffObject);

            foreach ($diffObject->getChunks() as $chunks) {
                /** @var list<Objects\Diff\DiffChunkLine> $chunkLines */
                $chunkLines = $chunks->getLines();

                yield $chunks->getHeaderLine();
                yield from $this->formatDiffChunkLines($chunkLines);
            }

            yield '';
        }
    }

    /**
     * @return Generator<string>
     */
    private function formatDiffObjectHeader(Diff\DiffObject $diffObject): Generator
    {
        $srcPath = $this->decorator->decorateOriginalPath($diffObject->getOriginalPath());
        $destPath = $this->decorator->decorateDestinationPath($diffObject->getDestinationPath());

        yield from match ($diffObject->getMode()) {
            Diff\DiffMode::Added => [
                $this->decorator->decorateOriginalPath(null),
                $destPath,
            ],
            Diff\DiffMode::Deleted,
            Diff\DiffMode::Ignored => [
                $srcPath,
                $this->decorator->decorateDestinationPath(null),
            ],
            default => [
                $srcPath,
                $destPath,
            ],
        };
    }

    /**
     * @param list<Objects\Diff\DiffChunkLine> $chunkLines
     *
     * @return Generator<string>
     */
    private function formatDiffChunkLines(array $chunkLines): Generator
    {
        foreach ($chunkLines as $chunkLine) {
            $decoratedChunkLine = $this->decorator->decorateChunkLine($chunkLine);

            if (null !== $decoratedChunkLine) {
                yield $decoratedChunkLine;
            }
        }
    }
}
