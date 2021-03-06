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
 * Class ArrayExportObject
 */
class ArrayExportObject extends ArrayObject
{
    public function offsetGet($index) {
        $value = parent::offsetGet($index);

        if (is_array($value)) {
            return new self($value);
        }
        return $value;
    }

    public function hasChildren() {
        foreach ($this as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * an array has no string keys but only zero based indexes from 0 ... count() - 1
     *
     * @return bool
     */
    public function isArray() {

        $keys = array_keys($this->getArrayCopy());
        if (!$keys) {
            return true;
        }
        $count   = count($keys);
        $compare = range(0, $count - 1);
        return $keys === $compare;
    }
}
