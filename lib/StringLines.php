<?php declare(strict_types=1);
/*
 * This file is part of Print_r Converter
 *
 * Copyright (C) 2011, 2012, 2013, 2021 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: AGLP-3.0-or-later
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 */

namespace Hakre\PrintrConverter;

/**
 * Class StringLines
 */
class StringLines
{
    /**
     * @var string[]
     */
    private $lines;

    /**
     * @var string
     */
    private $lineSeparator;

    public static function createFromString(string $string, string $lineSeparator = "\n"): StringLines
    {
        return new self(explode($lineSeparator, $string), $lineSeparator);
    }

    /**
     * @param string[] $lines
     * @param string $lineSeparator
     */
    public function __construct(array $lines = [], string $lineSeparator = "\n")
    {
        $this->lines = $lines;
        $this->lineSeparator = $lineSeparator;
    }

    public function getString(): string
    {
        return implode($this->lineSeparator, $this->lines);
    }

    /**
     * @param string $by
     * @return StringLines
     */
    public function indent(string $by): StringLines
    {
        foreach ($this->lines as &$line) {
            $line = $by . $line;
        }
        return $this;
    }

    /**
     * @param string $first
     * @param string $last
     */
    public function wrapLines(string $first, string $last): void
    {
        array_unshift($this->lines, $first);
        $this->lines[] = $last;
    }

    public function __toString()
    {
        return implode($this->lineSeparator, $this->lines);
    }

    /**
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }
}
