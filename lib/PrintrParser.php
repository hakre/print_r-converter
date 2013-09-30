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
 * print_r Parser
 */
function PrintrParser($buffer) {
    $result = null;
    $rP     = & $result;
    $rS     = array();
    $level  = 0;
    $len    = strlen($buffer);
    $offset = 0;
    $tokens = new PrintrTokenizer($buffer);
    $state  = 0; // 1: map
    foreach ($tokens as $index => $tokenData) {
        list($token, $offset, $length, $text) = $tokenData;
        switch ($token) {
            case 'array-open':
                $rP    = array();
                $state = 0;
                break;
            case 'object-open':
                $rP    = new stdClass();
                $state = 0;
                break;
            case 'key':
                if (1 === $state) { // empty value
                    $rP  = '';
                    $rSi = count($rS) - 1;
                    $rP  = & $rS[$rSi];
                    unset($rS[$rSi]);
                    $state = 0;
                }
                $key = preg_replace('~\[(.*)\]~', '$1', trim($text));
                ((string)(int)$key === $key) && $key = (int)$key;
                if (is_object($rP))
                    $rP->$key = null;
                else
                    $rP[$key] = null;
                break;
            case 'map':
                $rS[count($rS)] = & $rP;
                if (is_object($rP))
                    $rP = & $rP->$key;
                else
                    $rP = & $rP[$key];
                $state = 1;
                break;
            case 'value':
                if (is_string($text) && ($text = rtrim($text)) && ',' === substr($text, -1))
                    $text = substr($text, 0, -1);
                ((string)(int)$text === $text) && $text = (int)$text;
                ((string)(float)$text === $text) && $text = (float)$text;
                $rP = $text;
            # fall-through intended
            case 'array-close':
                $rSi = count($rS) - 1;
                $rP  = & $rS[$rSi];
                unset($rS[$rSi]);
                $state = 0;
                break;

            case 'leadws':
                # ignore leading whitespace
               break;

            default:
                throw new Exception(sprintf('Unexpected token %s in state %d at index %d', $token, $state, $index));
        }
    }
    return $result;
}
