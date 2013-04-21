<?php
/*
 * This file is part of Print_r Converter
 *
 * Copyright (C) 2011, 2012, 2013 hakre <http://hakre.wordpress.com>
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
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 */

/**
 * print_r regex Tokenizer
 */
class PrintrTokenizer implements Iterator
{
    private $tokens = array(
        'array-open'  => 'Array\s*\(\s?$',
        'object-open' => 'stdClass Object\s*\($',
        'key'         => '\s*\[[^\]]+\]',
        'map'         => ' => ',
        'array-close' => '\s*\)\s?$',
        'value'       => '(?<= => )[^\n]*$',
        'leadws'      => '^\s+',
    );
    private $buffer;
    private $offset;
    private $index;
    private $current;

    public function __construct($buffer) {
        $this->buffer = $buffer;
    }

    private function match($def, $at) {
        $found = preg_match(
            "~$def~im", $this->buffer, $match, PREG_OFFSET_CAPTURE, $at
        );
        if (false === $found) {
            throw new RuntimeException('Regex error.');
        }

        $return = 0;
        if ($found && $at === $match[0][1])
            $return = strlen($match[0][0]);

        return $return;
    }

    private function matchLargest($at) {
        $match = $max = 0;
        foreach ($this->tokens as $name => $def) {
            ($len = $this->match($def, $at))
                && $len > $max
                && ($max = $len)
                && ($match = $name);
        }
        return $match ? array($match, $at, $max) : null;
    }

    public function current() {
        return $this->current;
    }

    public function key() {
        return $this->index;
    }

    public function next() {
        $current = $this->matchLargest($this->offset);
        ($current)
            && ($current = array_merge($current, array(substr($this->buffer, $this->offset, $current[2]))))
            && ($this->offset += $current[2]);
        $this->current = $current;
        $this->index++;
    }

    public function valid() {
        return !(null === $this->current);
    }

    public function rewind() {
        $this->offset = 0;
        $this->next();
        $this->index = 0;
    }
}
