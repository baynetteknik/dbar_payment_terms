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

class FormulaLexer
{
	private $input;
	private $pos;
	private $length;

	/**
	 * Constructor
	 *
	 * @param string $input Input formula string
	 */
	public function __construct($input)
	{
		$this->input = str_replace(' ', '', $input); // Remove all spaces
		$this->pos = 0;
		$this->length = strlen($this->input);
	}

	/**
	 * Tokenize the input string
	 *
	 * @return array Array of tokens
	 * @throws Exception on invalid character
	 */
	public function tokenize()
	{
		$tokens = array();
		while ($this->pos < $this->length) {
			$char = $this->input[$this->pos];

			if ($char === 'G') {
				$tokens[] = array('type' => 'VARIABLE', 'value' => 'G');
				$this->pos++;
			} elseif ($char === '+') {
				$tokens[] = array('type' => 'OPERATOR', 'value' => '+');
				$this->pos++;
			} elseif ($char === '-') {
				$tokens[] = array('type' => 'OPERATOR', 'value' => '-');
				$this->pos++;
			} elseif ($char === '*') {
				$tokens[] = array('type' => 'OPERATOR', 'value' => '*');
				$this->pos++;
			} elseif ($char === '/') {
				$tokens[] = array('type' => 'OPERATOR', 'value' => '/');
				$this->pos++;
			} elseif ($char === '(') {
				$tokens[] = array('type' => 'LPAREN', 'value' => '(');
				$this->pos++;
			} elseif ($char === ')') {
				$tokens[] = array('type' => 'RPAREN', 'value' => ')');
				$this->pos++;
			} elseif ($char === '%') {
				$tokens[] = array('type' => 'PERCENT', 'value' => '%');
				$this->pos++;
			} elseif (is_numeric($char) || $char === '.') {
				$value = '';
				while ($this->pos < $this->length && (is_numeric($this->input[$this->pos]) || $this->input[$this->pos] === '.')) {
					$value .= $this->input[$this->pos];
					$this->pos++;
				}
				$tokens[] = array('type' => 'NUMBER', 'value' => (float)$value);
			} else {
				throw new Exception("ERR_INVALID_SYNTAX: Unknown character '" . $char . "' at position " . $this->pos);
			}
		}
		$tokens[] = array('type' => 'EOF', 'value' => '');
		return $tokens;
	}
}
