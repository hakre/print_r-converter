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
 * Class StringLines
 */
class StringLines
{
    /**
     * @var array
     */
    private $lines;
    /**
     * @var string
     */
    private $lineSeperator = "\n";

    public function __construct(array $lines = array(), $lineSeparator = "\n") {
        $this->lines = $lines;
        $this->lineSeperator = (string) $lineSeparator;
    }

    public function setLineSeperator($lineSeperator) {
        $this->lineSeperator = $lineSeperator;
    }

    public function setString($string) {
        $this->string = $string;
    }

    public function getString() {
        return implode($this->lineSeperator, $this->lines);
    }

    public function indent($by) {
        foreach($this->lines as &$line) {
            $line = $by . $line;
        }
        return $this;
    }

    public function addLine($line) {
        $this->lines[] = $line;
    }

    public function wrapLines($first, $last)
    {
        array_unshift($this->lines, $first);
        $this->lines[] = $last;
    }

    function __toString() {
        return implode($this->lineSeperator, $this->lines);
    }

    /**
     * @return array
     */
    public function getLines() {
        return $this->lines;
    }

    public static function createFromString($string, $lineSeparator = "\n")
    {
        return new self(explode($lineSeparator, $string), $lineSeparator);
    }
}
