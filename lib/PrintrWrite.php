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
 * print_r Writer
 *
 * Wraps the ad-hoc "exporting" of the parse result
 *
 * @param null|array|object $var
 * @param string $open
 * @param string $close
 * @return string
 * @see PrintrParse()
 */
function PrintrWrite($var): string
{
    if (is_array($var)) {
        $exporter = new ArrayExporter();
        $buffer = $exporter->export($var);
    } else {
        $buffer = var_export($var, true);
    }

    // polish var_export() output
    $buffer = str_replace(
        ['array (', 'stdClass::__set_state(array('],
        ['array(', '(object) (array('],
        $buffer
    );
    $buffer = preg_replace('~(=> )\n\s*(array\()~', '$1$2', $buffer);

    // variable prefix
    $buffer = '$' . (is_array($var) ? 'data' : 'object') . ' = ' . $buffer . ';';

    return $buffer;
}
