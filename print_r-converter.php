<?php declare(strict_types=1);
/*
 * print_r converter - convert print_r() to php variable code
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
 * @license AGPL-3.0-or-later <https://spdx.org/licenses/AGPL-3.0-or-later>
 *
 * @version 0.2.2
 * @date 2021-07-23
 *
 * known:
 *   - string values over multiple lines not supported.
 *   - limited support for objects, stdClass/anonymous classes and closure only.
 *
 * CHANGES:
 *
 * 0.2.2 - composer project
 * 0.2.1 - improve html/css and add closure & anonymous class support
 * 0.2.0 - host yourself
 * 0.1.5 - move into github repository
 * 0.1.4 - remove viper code-pad specific stuff
 * 0.1.3 - ignore leading whitespace at the beginning of the string
 * 0.1.2 - experimental compacted output (join numeric indexes)
 * 0.1.1 - allow more whitespace in array-open.
 *       - remove , at the end of values.
 * 0.1.0 - version 0.1.0, fixed some minor issues.
 * 0.0.9 - support for stdClass objects.
 * 0.0.8 - form was closed too early, fixed.
 * 0.0.7 - textarea for output.
 *       - clear / undo clear.
 * 0.0.6 - deal with empty values in parser via state.
 * 0.0.5 - button tooltips.
 *       - input sanitization now upfront.
 *       - html and css updates.
 *       - change output variable-name from $var to $array
 * 0.0.3 - github link opened in frameset eventually, fixed.
 * 0.0.2 - tokenizer less-strict whitespace dealing for array open and close
 *       - cache last input value into cookie
 *       - typo in tokenizer class name, fixed
 */

namespace Hakre\PrintrConverter;

require_once(__DIR__ . '/vendor/autoload.php');

header('Content-Type: text/html; charset=utf-8');

/* input: either post or cached in cookie */
$input = isset($_POST['input']) ? (string)$_POST['input'] : '';
if (($iLength = strlen($input)) > ($iMaxLength = 4096)) {
    die(sprintf('Maximum input length (post) of %d exceeded: %d.', $iMaxLength, $iLength));
}
$requestHasCookieData = false;
if (!empty($input)) {
    assert(false !== ini_get('session.cookie_lifetime'));
    assert(false !== ini_get('session.cookie_secure'));
    assert(false !== ini_get('session.cookie_httponly'));
    setcookie('i', $input, (int)ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'), (bool)ini_get('session.cookie_secure'), (bool)ini_get('session.cookie_httponly'));
    $requestHasCookieData = true;
} elseif (!empty($_COOKIE['i'])) {
    $input = (string)$_COOKIE['i'];
    if (($iLength = strlen($input)) > $iMaxLength) {
        die(sprintf('Maximum input (cookie) length of %d exceeded: %d.', $iMaxLength, $iLength));
    }
    $requestHasCookieData = true;
}

isset($_POST['clear']) && $input = '';
$canUndo = $requestHasCookieData && isset($_POST['clear']);
$buffer = null;
if ($input) {
    $buffer = str_replace("\r\n", "\n", $input);

    $var = PrintrParse($buffer);
    $buffer = PrintrWrite($var);
}

$title = 'print_r converter';
$project = [
    'rel' => 'vcs-git',
    'url' => 'https://github.com/hakre/print_r-converter.git',
    'title' => $title . ' source code on github',
];
$links = [
    'license' => [
        'rel' => 'license',
        'caption' => 'license',
        'url' => 'https://spdx.org/licenses/AGPL-3.0-or-later',
        'title' => $title . ' license',
        'text' => 'AGPL-3.0 or later',
    ],
    'vcs-git' => [
        'rel' => 'vcs-git',
        'caption' => 'git repository',
        'url' => 'https://github.com/hakre/print_r-converter.git',
        'title' => $title . ' git repository',
        'text' => 'https://github.com/hakre/print_r-converter.git',
    ],
];
?>
<html lang="en">

<head>
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <?php foreach ($links as $link): ?>
        <link rel="<?= htmlspecialchars($link['rel'], ENT_QUOTES, 'UTF-8') ?>"
              href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"
              title="<?= htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <style>
        body {
            font-family: helvetica, arial, freesans, clean, sans-serif;
        }

        h1 {
            font-weight: normal;
            letter-spacing: -1px;
        }

        a[target="_top"] {
            position: absolute;
            top: 0;
            right: 0;
            outline-offset: 0;
        }

        footer {
            display: block;
            text-align: center;
        }

        footer ul {
            display: inline-block;
            margin: 0;
            padding: 0;
            text-align: left;
            list-style-type: none;
        }

        footer ul li {
            display: inline-block;
        }

        footer ul li:not(:last-child) {
            margin: 0 1em 0 0;
        }

        footer ul li:first-child:last-child {
            margin: 0;
        }
    </style>
</head>
<body>

<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>

<form action="" method="post" accept-charset="UTF-8">
    <?php if ($buffer): ?>
        Output: <input type="submit" name="clear" value="clear"/>
        <div>
            <label>
                <textarea tabindex="1" rows="16" wrap="soft" cols="80"
                          style="overflow:auto; width:99%; white-space: pre;"
                ><?php echo htmlspecialchars($buffer, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </div>
    <?php endif; ?>
    <label for="i">input (array/stdClass object):</label>
    <input tabindex="3" type="submit" value="convert"/>
    <?php if ($canUndo) : ?> <a href="">undo clear</a><?php endif; ?>
    <div>
        <textarea tabindex="2" rows="16" wrap="soft" cols="80" style="overflow:auto; width:99%; white-space: pre;"
                  id="i"
                  name="input"><?php echo htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
</form>

<footer>
    <ul>
        <?php foreach ($links as $link): ?>
            <li>
                <?= htmlspecialchars($link['caption'], ENT_QUOTES, 'UTF-8') ?>:
                <a rel="<?= htmlspecialchars($link['rel'], ENT_QUOTES, 'UTF-8') ?> noopener"
                   href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"
                   title="<?= htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8') ?>"
                ><?= htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8') ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</footer>

<a href="<?= htmlspecialchars($project['url'], ENT_QUOTES, 'UTF-8') ?>" target="_top"
   tabindex="6" rel="<?= htmlspecialchars($project['rel'], ENT_QUOTES, 'UTF-8') ?> noopener"
   title="<?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>"><img
            src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJUAAACVCAYAAABRorhPAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAHplJREFUeNrsXXlUlde131wGGRyYcQBkUlGIGqMxmmqMZjJxSByS9jXJW/HG5q2+Jq/NUJK13nr/vVXN2LRdq00TeH0vzaBN0qZpkhq1jjFOOIHGERQQQRDBAMp0eft3ON/t4eO7TPdyucDZa33rgztw7+X73d/+nb332duvtbWVrMzPz8/5ozzb5M/+8mf/3xH9V9yECS/EzZtHtYWFVL5tm/PBPT172kZMmkTRt99O1y9dopKtW8+82dj42D6iK3xXkzyaTUcLHw55tMqDWl39g7S5NL9egso4An5D9J8x0dHPJj3yCDVUVlL5P/5BDRUV7cDSX8AKGD6coubMaXtfhw+feZPoiYNEFQqwzADTwPKA2dz9Az8hWn+5puZXlXv30vCkJIq9445/Xg2TtXbxu6etubaWvjt5ksKnTaPkZcsm/Dwq6n9nEMXxXcP4CJJHIPAnD4OFjS+Qn/yC+Wmo9A1T+Sn/cIOpAuUR9AZRVlxY2E8SH3yQrpeVUeXXX1PLjRuWbOTXxe+ettDERAqfPp1usCssPXDgLL/XJw8TXea7GuXRJM+asbwEKvW625TD+HY7gfUa0QsTp0378fgVK6j2wgUq/ugjarp2rd/1FSxw5EiKXbSImmpqqPzIkYJ1VVVrDre5wgYNrP51f63K4TAfzxH98tujR986/fbb1NrUJNhBdXPdPfeVK7x24gSFjBtHcTffnPIzonf43cVKVzjM+GJoV+hdplLFuqULlMew9UQ/S0tIWDv+4Yep8coVKv3iC7px+bJPMJZt2DCKYd2H91Wel3fu1cbGJ4/80xU2aMbqX6YyjhZ5OJfnWUS/OldWln1p82YKHDWKIm+91WcYy9HQQLVnz1L4zTdT0r33pmaNHJktGStIM5Z3maortgowMZZwKb8geiZp+HB74sqVYllfsWsXNbPGIh9grOC4OAqfMYNu8KKi7PDhs68TrdWM1X8hBbOmMjOVcTEaXyL69fna2uzqY8coauZMiueVoS04uN9DDbAb5eV09eBBESRNWrw47aXoaM1Y/cBUZhIxDn9Xq0GFsZ5OCQ+3j128mK5fvEiXd+70CX3lZ7NRWGoqRTBj4X2V7N6tGasfQOUWsCZPmGCHeG9glij+9FNqrKjod2DB/ENCKGbBAmq6epXK8/PPvVxbq8V7P7g/shDrDpMLbFIuSgNc4clz57KLPvqIX9VGEdOm+YRwh7Vcv051BQU0MjOTEubPT30hOPgd7Qq9y1Tuu8KICLsRbkASGue+YqyeADIIK9XZs6nh8mUqO3JEu0IvMpXbjFV47VrOlQMHaMTEiTT6rruoVQLYk4zVavEGzdFac46ysaZGBEhHTZ1KKUuXpr0UGanFu5eZyi3GWseMNT401B6/fLnQWAg3OJqaPMJY6idymM6tFvERao8IkRhHNgDivXT/fs1Y3gYVnq98M3sMrIyMDHviqlXiAhZ//DE1slh2J/lsRZ8O5WymaZViVIAFjBhBsQsXton3Y8cK1lVX2zWw+t79qcDqlSt8kV3h8ePHs8/94Q+CpVCi0p24VWs3AWUVRFMRYBxWblaUzZw6RWEpKTRm1qyU52y2t7Ur9CJTKc/pNWOljRljF4V+LNrLNm3qUa6wK0A5FPCk/+hHFHPLLbT7qafaVRuq5Rfq30a4IXrePFF4yIx19rWWFu0KvcFUnmCsgsrKnLLt2yk4JkZUa/ZEuLeawGVmKOMFJzCg5r/1Fk3m8+18VkHnajGAcEPtmTMUcfPNlPzAA2kvhodr8e5NpvIEY40PC7MnrlghmAqFfnBD3RXs6srO7OoApIUMJFhNQQGNYrd24ve/pz3MWGY0WOm2kLFjRRIaucJLublavHuLqTzBWBfq6rKv5uVRzNy5lLh6NQWEhXWLqVpNTKUeKqC+YhC9N3MmXc7NpSl8+9SsrA5lF1aMdb20lK7s2UNhycmobtC5Qm8zlScYKzUmxj7mnnvoenFxu1yheQXYKn9WkauiFhrqTgmoC1u20Ia77xZvIDQigu7m26GtHLzCC1D0lZ8LtjJyhZGzZgmQFW/frhnL26ByF1jpycn2RETeKyvp4mefiUi3GVhmtlJ11EQG1AIJqGp2eeHs8o6wy9uiiPRA5fDvwgU66X3YMIpdsIAaq6sh3s+9cv26zhX2tfvzlCs8VVSUAzD5BQUJodyTUMNkBVAA0bvs8srY5U3n2xMQxe8iym7lEp3aDYV+587RqMxMSly0KPXnoaE6V+hNpvIEYyWPHNlW3cDLerhCBCSpE6aKuOUWWnrwoLh9GwPqKLMTkBvELm88AwpuEODC8yHW4f4ClauvslVnEfxhkZHCFWJRoQv9+gFU7gBrvc329E3Tp9vH3n8/1RUW0oUNG/gSNXdgKYcCLIQPAhhEuevXt7uiiQyq5Rs3UjDfB7vBgPqUddZVZjHjDdhcaCvLVeHo0c5dOqV79xb8orJyyEfevQoqdxkrKSzMHr9smdi/B8Yih8Mp1B2dxKmMcxQz2KrNmwWgdrz4otBZ97KLLGbm2sRMGOCCrToDl43Fe2hSkrPQ7+I33wx5xvI6qNwF1pT0dPv41atFvKj4k09EBN6sqdRYVatyJZcxoMBUm9glQrDjvn9jbQSQvcWuzN8CVGpBvp+FiDfOCH3E3Hln277Co0cL1l+7NmQZy9YfL+qOeD9x8mR24XvvUWtLi8gVWoUajAuvujKbdH3QU0cloO5jlsKK8NSf/iRePJKZzMYAs2qwYIkAxZrr6kTkfcSECTRu7tyU5wMDh6x47xemcltjIY4VGysYq6GqisoYKGpTELO+ckiQ/IBZCWJ9MzMVVoAQ69BUv0PsicH1MDMZou0f8ErRAKSIZ7EGy4W7ZN3lb8FaqvmHhopuM9g9NFTFe7+Cyh1gvRIY+MyUzMw1Y1lkf8dgQdkMmYKiDsX94cqFY1XIwBkmRTpCDF8ywPCYf5FaywiSfs4aC29gBd8ey887yey2V6Z0bCaKMRt6N0C8o6K1ZNeugnVXrw4pV9jvoHKXsVDol/DQQ85cYUt9vUt9hcOfgZPMDHedGepbdnuoWHhEAmobs1EIn5OYxTYyWHF7HN9/nAH1NQMq0IXfsrKQ+HgRW0N/rEsHDw4pxvIJULkLrIypU0USur64mIoZKI2yKYjDAlgtptXgCgmovzNoDjF4YPgdzDWa78/j23ZIQKk9h7oCFSwwPFwU+mEDbdmhQwW/qKoaEoxl85U30lvxnoVCv2PHss+glKWhQZQBG0FRc6mwup16OANniYxXIcl8jMFjMwEKgh76ayTrra4KA60MYLp2/DgFjxlDcdOnpzxLNCQK/XyGqTzBWJMSE0WusMFoClJe3g6lZvGOyHukBA8QG8iAekQCygiMAmQNfP4bu8NqGSBVE8/mb6XVf83ZFIQXFZfz8s690tAwqHOFPgcqd4D1sr//MxlTpqxBshf7+C5+/nm7v+uwEPCq1lohNRQEPICFUEMF/4y0UBWfryqgslkERzsD1nBeYSKOJTbS7tgxqONYPgkqdxlLNAVZtUpUN1zevZuaqqvbMRZZ+NW72BWmsYCHGwRD3ZqVRTkMhFoGZ3eCombWsvrvDYuJoYiZMwd9UxCfBZW7wJo6Y4Z97JIlVH/+vMgVorKATKJNBRaYCrVXB9avp+/LZPSHDACbKYRg6yTS7ira3g5YsbEUh1wh663SffsGZa7Q5suId0e8Hzt0KPvUm29SS1MTRc+e3U5Qq+g0Iu+oVMhjQEWxKEdcqmTLFucqz09xn0a8S426m696ZyIe7Fl14EBbWic9fVCKd58GlbvAKqypyYYWwm6YtLVrKYjdj9k9OZvCK1exkp9znvWUv/IPciji/gHWXtg40Uzt0zlqXZbLz+NwiKZrpZ99JvqQjps7Ny1rxIhBVZrs86ByF1gnT5/OLmKA4E9Eyn2F6oc3hxqus4baxitIgNFmAhVWistlRH7cXXfRTay7mqhjfrCzWnfDxC4dBtfIyZMpft68lMHUFMSnNZXHVoUo9IuMdDYFKUNTEHZD5pIZ8wrRYQqUGoDaz24SxX5YKW596in6lsW9GhxVk9hdrQrBVpG33TaomoIMCKZyl7F+jo5+NTU5V/bvF0v70QsXOp9sc4FQlRJiFUAhUIp0DlI8sJnMVihdVjWWowcaC4IdTUFQcZGybNmgaAoyoJjKE4yVGBJiT3jwQbGsr9izR6wKzTud1UK/EBbuy3k1CEBtkakc5At/wCAzAqQonznI7LWXwWbFWF2GG/jjIAktcoWlpQO+KciABJW7wMrIzBRxLJErRFOQ6up2F1qNvOPqZTIb1TF4EHmHrnpY5gs/ZneKioZnq6raSmgiIzvkCM2lMq7cIMzZFMQYIDBAm4IMKPfnKVd4PD8/+1xOTltTkKlTO+7vU/45OI4xC6H0BTrKABRKZuAC42RK5zyDy9VYru7212qpqxOzdMKSkmjMzJkpz/r5Dchww4AFlbvAOl1UlF22dasIESQ9/jgFx8a2u+g2E7Bw9ZC6MRLQYK2x/PvKjRvF4/Yw8EawG5zBrIbSGlcVo9RFuAGdZoo++EC0sBwzY0baQKwgHbDuzxOu8FUU+mVkrIlbsEDs40PsSN1IYV4FNsk4VWlubjs3+DmDDM9ZuG6ds9AP2+q/uPtu5+5ntVymMxdoGLbXxyJXyKvUkp07B5QrHBSgcldjIVeYgKYg5eVUyeK9+bvvLMdaGLQXitJjFu+GG6xn4BiMtZcZC0nopfw76rDOyHCDVbsi6gJgKJmJMJqCHDo0YMT7gHZ/Hgs3YIBAfj5Fz5lDAJctNLTDJgo14l5fUECHGDxYDeYzaBYxQ8GMHdBGuTLq4S0nQ5F1T1KzYSvalX37aHha2oBqCjJomMoTjJUaHW0fbTQF2bWr3Q5oq3IZ4/gp/w9QJoOtX0YJMmqwNs6cKSL0/i7iX90pm3E2BeG/VV9Sgpp3n2esQQcqd4GVnpraVujHrrCENRYGCJjr3c3tHtfKkAJaFOGFVjKwwGAnZTWpFaBc9Rt15Q7FAAEU+vHroNDv5fp6ny30G5SgcgdYr9hsYsNqzPz5VMfiHZPAzIylAgvnSbIZCLZ3fcmAhKayqrWymsRpxV6uwIUW4dH8vuAWi7dt81lgDVpQuctYmKWTsHq1YKpyNAVhNrIClnFO4sdGs9tDVN0qNYN9hojEA3ClW7a0c4mdtTAyXwXotIhZs3w6VzioQeUWY/n7P3PT9Olrxtx3nwg3FGF1J3s3kEXIwXzVjN/RJGQuC/lMZjPD8uUOnQBlAWBTzl0V+qFVpGgKUl2N3g0+V+g3aFZ/nl4VvtDS8qu83NzsU7/8pcjNxbLb8QsIaBdtV1eEVmkZmAGocmaoz9g1olwZv0fIQj+rrfVdlc5gLyH2OPoFBlJ0WprPFfoNelC5BSzZgxTzCmNZJKc88QQNi4pq3/VFcV9mNxbPLs8AFDannuAVIgKnJO/Di2KMSlNPo+/8cbCxo3zTJlH3Hj9vnk91TR4SoHIXWCeOH88ufPddkSscpRT6qdFxK+EdI/OCu1hnYUc0SmgWyJgWAIZepA/yQiDJIq1jFccyx7Oa6+vpu9OnRRxr7OzZPtMUZMiAyl1gnSooyMZewvCMDEp69FFnrtDPxQoPh9HxbyKDZhIfP5Bpna0MstlZWTRN6qxbZL6wpYtAaYc3zx/n2qlTVPzhhyJvGJuZmeoLkykGvVD3pHh/TeYK4bLQ0a9ENgVRV3pquAHIRC17hiLS/8GAQv3VDL4NEXgETQEwgO391NR2wVKrchlXXf3Cxo8XZTO+0BRkSILKrVUhCv2Cg0U9FpqCXMEAgbo654qQFDdmCHEkn8cyEFEqM4sBNF0C6kPWWWCz2/i2eewWP+Pfy2S4obPYlQo21dWExseLbf+IY/XnAIEhCyp3gYWmIMgT1hcVUfHGjdRUW+v8u66mTkxn8NzO4DGEO6Lw6DLz2MGDYgLFH5mpkFe0uQCNVfDUHH5AUxBjXyEDt1+aggwpTeVJjSWagvz2t+RobBT9Ps3/VDXkYKDy6Pr1orHtJwyoZgYUmoSsZp0FQOH2awwoc2DVVTc/V4lpNAWpyc9v27Q6bVq/hBuGNFN5grEmjR9vT8T0r8pKKv3b30Sk2yry3mICBpqBLJcN1RAM3f7UU5buLl5G4lH5oO5H9O+CsYymIGJeIZqC3LjhtZSOBpWbwHrV3/+ZKZMnr0EcC30XLn35pWWu0MxAs9gNwh2ekIAii3TMvexWkd5RrYBBhQZsLQwWqzCG6ioRasAUe283BdGg8hBjYYBAgtEUBLnC6uouZzhjM+phdnvmKapBCouhhAZMhpxhhpxWcYSfc4hXka4qINRVomgKwn8HBYjeagqiQeUpxrLZns7EAIElS0S0u2jDBmqVM6HVcIMrgBlXMkABFEqS0XsUWssQ6AiWwh2+GxnZIRntKm8o9BUi9xgg4IWmIENaqHtSvD/vcPw6D01B3nhDtOOOnjtX5AzJRdTdFRDARgAUymg+QqM1Pqtv5Cy7P1RCmMHYWTtu0RRk3762zskTJ/a5eNdM1QeucPKkSXbMKoTLufjnPwsR79cJUtXarCeqqoQo/5gBhT2F6rffXN6MGi4U7aEddwB1XpsFCwgJaQuQotAvP7/PNJZmqj4IN3x76lQ23B8Sv8YAgVYTY1mldbDSA6Dg9gxA4Xm4zcxMCKZiuz16ahmg7Iqxmo2mIOnpbQMEgoL6JFeoQdVXwDpzJrvkL38R1ZoJjzxCwdHRlj2yVHaplBUMAJEBRLjCRw8eFDrKoQCqkEH3NTMUpoSZ5/CoSWlzxQP2FaKDs8gVTpmS2heuUIOqD4FVWFOTU8laBnv40O/Typ+aE9BYDSIYijr3+evWOaPt2B2NF0QpDYY2oU5rHz+2UO6MbnFxWCWjsQUNO6GR0umLpiBaU3lBYzkHCJSVUeXu3c5WkVYTv5plDAs7nQ37Sg5oQlUDpn4hvYOxvcghjparxO3KtFW1mrQznRWWmNg2bBxNQQ4c8Fi4QTOVFxjrQn29KPSLmTePxj/2GAWOGtWubEZdEeLYz24tOzJSgGWL7PGuAup9BhSGN+G2OOkW/ZVBTXP5caP49s5qtGB1RUVUsW2bmE6RuHBh2osRER5hLA0qLwErPy8v+9w774hc4Sh2O8YfsRocgAO5QbAPIu7TTYCqKiigdF5dGhYnAYQXvYMfhyn2izZuFEDrCljNtbWiPxaANXrGDI+EG7T787IrnBQfb09AR7+qKrr0xRciV+gwuUIVoaJnA+sqAAqVDVcYUN9nTTWGb0evLKPnO8ag3M+AMiaD/QXgk1O/zMMxreq0nAMErlwRucJXGxt7nSvUoPIysFDol4FcIQt35ArRFMRVQxDj6mFf4SVs7eIDIh0DmQ4zoD6XE8DwfABqhgQUwIe0Dl44DFu6WOhXm8b5Wu2ODkNn5oULBdCLt2/vdRxLg6qfGCsZo3tXrRJzCiHeUbJiHnVi3gmNUAKmUiCG9QEDx1jdGYCCfSFFPd6EWlrzpTIGpbPRcsFxcRQ+Y4ZbAwS0puqvcENdXXYNi/eoW2+leF4Z2oKDLUW7ispmWfOOcuRkBhge94DCUDjQzggxLZTWrJQ5RCLX7bjNGgtZgKsHDoj4WtLixb1qCqKZqp8ZKzUiwj76vvvoxsWLorpBHQRgNVJuIgMolUU69hCiinSaBNS7zES4Eo/JXqTIGUJ34b4/S5Yy3oB5K1mHz2OziYa7qG7AsPGeNgXRoPIBYE1GUxAU+rGWKfn0U2dTELPGUqPm07KyRAAUoEGt+0UGjdh+zwz2uNLkVoh7vi9IeXErUFnmCpk9xbBxFPrl5597uba2W+Jduz8fcIWnCguzSz75hK+0P0XIcINVDEtF5inWTcWsrVCaXClXeWLes9xXCIO4R/gB7rCFOvZ/MIshc0qn5cYN0aRkZGYmJdxxR+oLISHdyhVqpvIRxnoV4j08XLQxwrL+8vbt4uyq/srcIwt1WKvkrEIw1BbZ6x3uEBrsExliMDNVZ9PAjA+CYG3k7NndbgqimcpHGOt5dPT77rucqoMHaUR6Oo1mEKAeS73Q/hboDJBhg9UKoN6TW+wflbdBX13lo4Wsm7aZt9qbzyjuu5afT6NuuolSli415wpVzyrekgaVDwHrOTQFkYV+oinIggVkCwzssAvaHH0Pk1UNhobCFrDVGzcKoY54FjQX+sBPkY1B1Befw6vHVFk+01kfB9EUZPdu0RQkasIENfI+zASuAO3+fNQVZmRkiJp3sfpivYUIvIpSs3i3MbDQ4BYBUmyjv4O1VaGMZ8EQdrhPNmZ7f+ZMIb4Xyd3T0GSbEPfi21Q2JCvxPnx42wCB6moqP3asYH119ZrDba6wQX5BGjWofBhYk5KS7NilU3/hgtBY5tiSOfJu7Ij+VxbXCHi+ERkpGoPczMBZLHOHYC0kqgGwqXy7qINXWnN31oBNfiARw4qaM0eMPCnasuXs6w7HkwyscoNxNah8HFhpo0e3iXdmqvKvvhJb7cnkU81aaQlrKVQx7JC17HfK0AO0FlaES2SOEG4SAr6V7zMv4fxdMJVh/sHBolUk6t8h3t9wONYcksDSmsrHxXvBlSs5CIpiRwzYwYxOq5ADNkYwgwgXqAIKsSwj6Wy8GWz5aqXOB19aGcINtadPi0K/5CVL0rLCw/9nJhGmdAZqphoo4YawMPu4FSvExlAxQED2bnBYBEgNdC6TaZoNElD3yZQO2Ookrw7nyG4z/5eaSjdYawVRx2qGrgytIsPlAIGy3NwzvyF6LEBfau8ylgRWuxijPDe7eh4Y69W6OhqRn28f9+CD4kKWMCia+DYrV2P88U3sNlF5UKEwFAAF8d4gdziDzRDHKmdQ9cagqzBlFT1IE6OiJjyfm/tHzVQDTWPFxtoRw0K3mcpduzrsblZzhYZfRUrnezLSDqGOiV+Ivv+IBT2Y6j3ZFyuQrPuWdvl5kCtMSxNdk7Fa1aAagMBKT05uK/SrqKCLSlOQ7gALTdYg0G+Vrm8P66/89evbBVNtPQSVYSj0Q2xNg2oAAus1f/9nMtLT12D1VX/+vGgKQp0wlgGupayx1IYfmK+zX25EDexOOKEbBsbSoBrAjJUycqQo9MOyvoJdYaOst7IS78YxWQ5kQveYWtlgzRyf6u74OJefSYNqADOWzfb0TdOn28fcf79grOING6hFNgVxUOdNaa3SPn5k3TlGg2qoAUvOKxy3dGlbfm7nTnI4HE5gtZJ1aYt5pK+rCoXemA5+DvAA6XNyXmHV4cNiX2Gy3U7DoqOdF9cKmebxJa6GW2pQDXFgfXvyZPaF998nR3OzGDauXmCr2veuOiC7xbra/Q0uV5gaE9M2rxD797ZuFbt1uv26mqk0Y1kxVmF1dU7lN99QaGKimKfjqhmI1eGxL4VmqkEq3kND7eMeeqgtV/j11yKV4i3TTDVYxXt9fXZNXh5Ff+97lPjDH4o6c699GTRTDW7Gmjh2rB2VmvXFxVSxY4cGlTbPAGtSQoI9Xu7SMZqCaPenzS1XeKasLKd882YKHDmSImfN0kylzXOMlTx8eFtTEGYqiHdsvdJMpc29cENtrWgKgrqnscuWiVIVzVTa3Gas1+VO6DGLF1N9SYko9NNMpc0txnq2LUCafTU3l2Lmz6eUtWuduUINKm1uAevk2bPZqHOHhcumINr9afOIK0yJjBSlycgVolMxRp5optLmFmOJpiD794syYARJNVNp8xhjjQ8JEblC5wCBxkbNVNrcY6wL16+LcAPE+/jHHxcDvjWotLkNrOP5+dmFOTmCpdwR79r9aVfYwRWmjRljx+QvNAUp+/vfe5wr1EylGasDYxVUVuZgJTgsKoqibrtNM5U2zzFWEpqCrFzpzBVipJtmKm3uhRswQCAvT7BVPIMLM5g1U2nzCGOlRkfb4+65h66XlFDFzp0aVNo8A6xJKSltkffKSir99NNOd+lo96ddYbdc4ekLF3JK//pX0S0ZTc40U2nzGGMljxplj1+9uq0pyI4dzqYgmqk0Y/VevNfW5lQfOiRGi4xevJj8/P01U2lzm7GC3mDGSgwNfXLc8uUiVwjx3trSoplKW+8Z62dEbxbV179dfeSIaHudvGYNBUVFaVBpcxtYb5z69tu3Lrz7boemINr9aeupK2zXkeg3RD9NiI39sZErxAABDSptPQVWh/ZWbwUGPp+cmfnvcYsWiZaPGlTaegosqxZXtreIno8ODf2P+BUrNKi09RhY5rOzTWg2UVbCtGnPug2q/x6o/7hOfu/ufVY/9/S23jzGG+dHO45X9iPrgabtWq7/geglt0GlbXCTlgWwuuo926pBpa03wOqsGZ8GlbZeAYsswOQEjAaVtt7IUD/qpIe/BpU2j69vNKi0eQJcmqm09S3INKi0eR5hGlTaNKi0DVxQadOmQaVNg0qbBpU2bRpU2vrP/l+AAQBZTVVRokJ0LQAAAABJRU5ErkJggg=="
            alt="Fork me on GitHub"></a>

</body>
</html>
