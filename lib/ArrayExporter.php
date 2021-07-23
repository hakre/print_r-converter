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
 * Class ArrayExporter
 */
class ArrayExporter
{
    public function export(array $array): string
    {
        $export = new ArrayExportObject($array);
        $omitNewLines = !$export->hasChildren();
        $buffer = [];

        if ($omitNewLines) {
            $buffer[] = 'array(';
            $count = count($array);
            $virtualKeyPointer = 0;
            foreach ($array as $key => $value) {
                $count --;
                $virtualKey = $virtualKeyPointer === $key;
                is_int($key) && $virtualKeyPointer ++;

                $buffer[] = ($virtualKey ? '' : var_export($key, true) . ' => ')
                    . var_export($value, true)
                    . ($count ? ', ' : '');
            }
            $buffer[] = ')';
            return implode('', $buffer);
        }


        $buffer = [];
        $virtualKeyPointer = 0;
        foreach ($array as $key => $value) {
            $virtualKey = $virtualKeyPointer === $key;
            is_int($key) && $virtualKeyPointer ++;

            $buffer[] = ($virtualKey ? '' : var_export($key, true) . ' => ')
                .
                (
                is_array($value)
                    ? ltrim((string)StringLines::createFromString($this->export($value))->indent('    '))
                    : var_export($value, true)
                )
                . ',';
        }

        $buffer = (new StringLines($buffer))->indent('    ');
        $buffer->wrapLines('array(', ')');
        return $buffer->getString();
    }
}
