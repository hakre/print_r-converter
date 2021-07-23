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
 * print_r Parser
 *
 * @throws \Exception
 *
 * @return null|array|object
 */
function PrintrParse(string $buffer)
{
    $result = null;
    $resultPointer = &$result;
    $resultStack = [];
    $tokens = new PrintrTokenizer($buffer);
    $state = 0; // 1: map
    foreach ($tokens as $index => [$token, , , $text]) {
        switch ($token) {
            case 'array-open':
                $resultPointer = [];
                $state = 0;
                break;
            case 'object-open':
            case 'anonymous-open':
            case 'closure-open':
                $resultPointer = new \stdClass();
                $state = 0;
                break;
            case 'key':
                if (1 === $state) { // empty value
                    $resultPointer = '';
                    $resultStackIndex = count($resultStack) - 1;
                    $resultPointer = &$resultStack[$resultStackIndex];
                    unset($resultStack[$resultStackIndex]);
                    $state = 0;
                }
                $key = \preg_replace('~\[(.*)]~', '$1', trim($text));
                ((string)(int)$key === $key) && $key = (int)$key;
                if (is_object($resultPointer)) {
                    $resultPointer->$key = null;
                } else {
                    assert(is_array($resultPointer));
                    $resultPointer[$key] = null;
                }
                break;
            case 'map':
                assert(isset($key));
                /** @noinspection ArrayPushMissUseInspection */
                $resultStack[count($resultStack)] = &$resultPointer;
                if (is_object($resultPointer)) {
                    $resultPointer = &$resultPointer->$key;
                } else {
                    assert(is_array($resultPointer));
                    assert(is_string($key) || is_int($key));
                    $resultPointer = &$resultPointer[$key];
                }
                $state = 1;
                break;
            case 'value':
                if (($text = rtrim($text)) && ',' === substr($text, - 1)) {
                    $text = substr($text, 0, - 1);
                }
                ((string)(int)$text === $text) && $text = (int)$text;
                ((string)(float)$text === $text) && $text = (float)$text;
                assert(is_string($text) || is_int($text) || is_float($text));
                $resultPointer = $text;
            // fall-through intended
            case 'array-close':
                $resultStackIndex = count($resultStack) - 1;
                $resultPointer = &$resultStack[$resultStackIndex];
                unset($resultStack[$resultStackIndex]);
                $state = 0;
                break;

            case 'leading-whitespace':
                # ignore leading whitespace
                break;

            default:
                throw new \Exception(sprintf('Unexpected token %s in state %d at index %d', $token, $state, $index));
        }
    }
    return $result;
}
