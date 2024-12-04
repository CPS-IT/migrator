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

use function http_build_query;
use function implode;
use function sprintf;

/**
 * CliFormatter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CliFormatter implements Formatter
{
    private readonly Decorator\DiffDecorator $decorator;

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

                yield $this->decorateText($chunks->getHeaderLine(), foregroundColor: 'cyan');
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
                $this->decorateText($this->decorator->decorateOriginalPath(null), options: ['bold']),
                $this->decorateText($destPath, options: ['bold']),
                $this->decorateText(' ADDED ', 'green', 'black'),
            ],
            Diff\DiffMode::Copied => [
                $this->decorateText($srcPath, options: ['bold']),
                $this->decorateText($destPath, options: ['bold']),
                $this->decorateText(' COPIED ', 'gray', 'black'),
            ],
            Diff\DiffMode::Deleted => [
                $this->decorateText($srcPath, options: ['bold']),
                $this->decorateText($this->decorator->decorateDestinationPath(null), options: ['bold']),
                $this->decorateText(' DELETED ', 'red', 'black'),
            ],
            Diff\DiffMode::Ignored => [
                $this->decorateText($srcPath, options: ['bold']),
                $this->decorateText($this->decorator->decorateDestinationPath(null), options: ['bold']),
                $this->decorateText(' IGNORED ', 'gray', 'black'),
            ],
            Diff\DiffMode::Renamed => [
                $this->decorateText($srcPath, options: ['bold']),
                $this->decorateText($destPath, options: ['bold']),
                $this->decorateText(' RENAMED ', 'yellow', 'black'),
            ],
            default => [
                $this->decorateText($srcPath, options: ['bold']),
                $this->decorateText($destPath, options: ['bold']),
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

            if (null === $decoratedChunkLine) {
                continue;
            }

            switch ($chunkLine::class) {
                case Objects\Diff\DiffChunkLineUnchanged::class:
                    yield $decoratedChunkLine;
                    break;

                case Objects\Diff\DiffChunkLineAdded::class:
                    yield $this->decorateText($decoratedChunkLine, foregroundColor: 'green');
                    break;

                case Objects\Diff\DiffChunkLineDeleted::class:
                    yield $this->decorateText($decoratedChunkLine, foregroundColor: 'red');
                    break;
            }
        }
    }

    /**
     * @param list<string> $options
     */
    private function decorateText(
        string $text,
        ?string $backgroundColor = null,
        ?string $foregroundColor = null,
        array $options = [],
    ): string {
        $tagAttributes = [];

        if (null !== $backgroundColor) {
            $tagAttributes['bg'] = $backgroundColor;
        }
        if (null !== $foregroundColor) {
            $tagAttributes['fg'] = $foregroundColor;
        }
        if ([] !== $options) {
            $tagAttributes['options'] = implode(',', $options);
        }

        $tag = http_build_query($tagAttributes, '', ';');

        return sprintf('<%s>%s</>', $tag, $text);
    }
}
