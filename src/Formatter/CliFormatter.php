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
 * CliFormatter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CliFormatter implements Formatter
{
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
                yield '<fg=cyan>'.$chunks->getHeaderLine().'</>';
                yield from $this->formatDiffChunkLines($chunks->getLines());
            }

            yield '';
        }
    }

    /**
     * @return Generator<string>
     */
    private function formatDiffObjectHeader(Diff\DiffObject $diffObject): Generator
    {
        $srcPath = '<options=bold>--- a/'.$diffObject->getOriginalPath().'</>';
        $destPath = '<options=bold>+++ b/'.$diffObject->getDestinationPath().'</>';

        yield from match ($diffObject->getMode()) {
            Diff\DiffMode::Added => [
                '<options=bold>--- /dev/null</>',
                $destPath,
                '<fg=black;bg=green> ADDED </>',
            ],
            Diff\DiffMode::Copied => [
                $srcPath,
                $destPath,
                '<fg=black;bg=gray> COPIED </>',
            ],
            Diff\DiffMode::Deleted => [
                $srcPath,
                '<options=bold>+++ /dev/null</>',
                '<fg=black;bg=red> DELETED </>',
            ],
            Diff\DiffMode::Ignored => [
                $srcPath,
                '<options=bold>+++ /dev/null</>',
                '<fg=black;bg=gray> IGNORED </>',
            ],
            Diff\DiffMode::Renamed => [
                $srcPath,
                $destPath,
                '<fg=black;bg=yellow> RENAMED </>',
            ],
            default => [
                $srcPath,
                $destPath,
            ],
        };
    }

    /**
     * @param array<Objects\Diff\DiffChunkLine> $chunkLines
     *
     * @return Generator<string>
     */
    private function formatDiffChunkLines(array $chunkLines): Generator
    {
        foreach ($chunkLines as $chunkLine) {
            switch ($chunkLine::class) {
                case Objects\Diff\DiffChunkLineUnchanged::class:
                    yield ' '.$chunkLine->getContent();
                    break;

                case Objects\Diff\DiffChunkLineAdded::class:
                    yield '<fg=green>+'.$chunkLine->getContent().'</>';
                    break;

                case Objects\Diff\DiffChunkLineDeleted::class:
                    yield '<fg=red>-'.$chunkLine->getContent().'</>';
                    break;
            }
        }
    }
}
