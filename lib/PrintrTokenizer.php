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

use Iterator;

/**
 * print_r regex Tokenizer
 */
class PrintrTokenizer implements Iterator
{
    /**
     * @psalm-var array{array-close: string, array-open: string, key: string, leading-whitespace: string, map: string, object-open: string, value: string}
     * @var string[]
     */
    private $tokens = [
        'array-open' => 'Array\s*\(\s?$',
        'object-open' => 'stdClass\s+Object\s*\($',
        'anonymous-open' => 'class\@anonymous\s+Object\s*\($',
        'closure-open' => 'Closure\s+Object\s*\($',
        'key' => '\s*\[[^\]]+\]',
        'map' => ' => ',
        'array-close' => '\s*\)\s?$',
        'value' => '(?<= => )[^\n]*$',
        'leading-whitespace' => '^\s+',
    ];

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var ?int
     */
    private $offset;

    /**
     * @var ?int
     */
    private $index;

    /**
     * @var ?array{0: string, 1: int, 2: int, 3: string}
     */
    private $current;

    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @return array{0: string, 1: int, 2: int, 3: string}
     */
    public function current(): array
    {
        assert(is_array($this->current));
        return $this->current;
    }

    public function key(): int
    {
        assert(is_int($this->index));
        return $this->index;
    }

    public function valid(): bool
    {
        return !(null === $this->current);
    }

    public function rewind(): void
    {
        $this->offset = 0;
        $this->index = - 1;
        $this->next();
    }

    public function next(): void
    {
        assert(is_int($this->offset));
        $current = $this->matchLargest($this->offset);
        ($current)
        && (array_push($current, substr($this->buffer, $this->offset, $current[2])))
        && ($this->offset += $current[2]);
        /** @var ?array{0: string, 1: int, 2: int, 3: string} $current */
        $this->current = $current;
        assert(is_int($this->index));
        $this->index ++;
    }

    /**
     * @param int $at
     * @return ?array{0: string, 1: int, 2: int} array{0: string token-name, 1: int offset, 2: int length}
     */
    private function matchLargest(int $at): ?array
    {
        $match = $max = 0;
        foreach ($this->tokens as $name => $def) {
            $len = $this->match($def, $at);
            if ($len > $max) {
                $max = $len;
                $match = $name;
            }
        }
        return $match ? [$match, $at, $max] : null;
    }

    private function match(string $def, int $at): int
    {
        $found = preg_match(
            "~$def~im", $this->buffer, $match, PREG_OFFSET_CAPTURE, $at
        );
        if (false === $found) {
            throw new \RuntimeException('Regex error.');
        }

        $return = 0;
        if (0 === $found) {
            return $return;
        }

        assert(is_array($match[0]));
        assert(is_string($match[0][0]));
        if ($at === $match[0][1]) {
            $return = strlen($match[0][0]);
        }

        return $return;
    }
}
