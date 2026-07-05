<?php
/* Copyright (C) 2026	Baynet Bilişim	<your@email.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

class FormulaEvaluator
{
	/**
	 * Evaluate the AST node to calculate the amount
	 *
	 * @param array $node AST node
	 * @param float $gValue The value of G (Grand Total)
	 * @return float Calculated amount
	 * @throws Exception on calculation error (e.g. division by zero)
	 */
	public static function evaluate($node, $gValue)
	{
		if ($node['type'] === 'Variable') {
			if ($node['value'] === 'G') {
				return (double) $gValue;
			}
			throw new Exception("ERR_UNKNOWN_VAR: Unknown variable: " . $node['value']);
		}

		if ($node['type'] === 'Number') {
			return (double) $node['value'];
		}

		if ($node['type'] === 'UnaryOp') {
			$right = self::evaluate($node['right'], $gValue);
			if ($node['op'] === '-') {
				return -$right;
			}
			return $right;
		}

		if ($node['type'] === 'BinaryOp') {
			$left = self::evaluate($node['left'], $gValue);
			$right = self::evaluate($node['right'], $gValue);
			switch ($node['op']) {
				case '+':
					return $left + $right;
				case '-':
					$res = $left - $right;
					if ($res < 0.0) {
						throw new Exception("ERR_NEGATIVE_RESULT: Formula results in a negative amount");
					}
					return $res;
				case '*':
					return $left * $right;
				case '/':
					if (abs($right) < 1e-9) {
						throw new Exception("ERR_DIV_ZERO: Division by zero is not allowed");
					}
					return $left / $right;
			}
		}

		throw new Exception("ERR_INVALID_SYNTAX: Invalid node type " . $node['type']);
	}
}
