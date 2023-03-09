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

namespace CPSIT\Migrator\Exception;

use CPSIT\Migrator\Diff;

/**
 * PatchFailureException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PatchFailureException extends Exception
{
    private function __construct(
        private readonly Diff\DiffResult $diffResult,
        string $message,
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }

    public static function forConflictedDiff(Diff\DiffResult $diffResult): self
    {
        $message = 'Unable to apply patch on a conflicted diff.';

        if ($diffResult->getOutcome()->isSuccessful()) {
            $diffResult = new Diff\DiffResult(
                $diffResult->getDiffObjects(),
                $diffResult->getPatch(),
                Diff\Outcome::failed($message),
            );
        }

        return new self($diffResult, $message, 1678361343);
    }

    public function getDiffResult(): Diff\DiffResult
    {
        return $this->diffResult;
    }
}
