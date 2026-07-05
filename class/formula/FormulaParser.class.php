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

class FormulaParser
{
	private $tokens;
	private $pos;
	private $currentToken;

	/**
	 * Constructor
	 *
	 * @param array $tokens Array of tokens from FormulaLexer
	 */
	public function __construct($tokens)
	{
		$this->tokens = $tokens;
		$this->pos = 0;
		$this->currentToken = $this->tokens[0];
	}

	/**
	 * Consume current token if it matches expected type
	 *
	 * @param string $type Expected token type
	 * @return void
	 * @throws Exception if token doesn't match
	 */
	private function consume($type = null)
	{
		if ($type && $this->currentToken['type'] !== $type) {
			if ($this->currentToken['type'] === 'RPAREN' && $type === 'EOF') {
				throw new Exception("ERR_UNMATCHED_PAREN: Unmatched closing parenthesis");
			}
			throw new Exception("ERR_INVALID_SYNTAX: Expected token " . $type . " but got " . $this->currentToken['type']);
		}
		$this->pos++;
		if ($this->pos < count($this->tokens)) {
			$this->currentToken = $this->tokens[$this->pos];
		}
	}

	/**
	 * Parse token stream into AST representation
	 *
	 * @return array AST Root Node
	 * @throws Exception on parser syntax error
	 */
	public function parse()
	{
		if (empty($this->tokens) || $this->tokens[0]['type'] === 'EOF') {
			throw new Exception("ERR_EMPTY_FORMULA: Formula cannot be empty");
		}
		// Validate that there are no consecutive operators
		for ($i = 0; $i < count($this->tokens) - 1; $i++) {
			if ($this->tokens[$i]['type'] === 'OPERATOR' && $this->tokens[$i+1]['type'] === 'OPERATOR') {
				throw new Exception("ERR_INVALID_SYNTAX: Consecutive operators are not allowed");
			}
		}
		$node = $this->expression();
		if ($this->currentToken['type'] !== 'EOF') {
			if ($this->currentToken['type'] === 'RPAREN') {
				throw new Exception("ERR_UNMATCHED_PAREN: Unmatched closing parenthesis");
			}
			throw new Exception("ERR_INCOMPLETE_EXPR: Unexpected token " . $this->currentToken['value'] . " at end of expression");
		}
		return $node;
	}

	private function expression()
	{
		$node = $this->term();
		while ($this->currentToken['type'] === 'OPERATOR' && ($this->currentToken['value'] === '+' || $this->currentToken['value'] === '-')) {
			$op = $this->currentToken['value'];
			$this->consume('OPERATOR');
			$right = $this->term();
			$node = array('type' => 'BinaryOp', 'op' => $op, 'left' => $node, 'right' => $right);
		}
		return $node;
	}

	private function term()
	{
		$node = $this->factor();
		while ($this->currentToken['type'] === 'OPERATOR' && ($this->currentToken['value'] === '*' || $this->currentToken['value'] === '/')) {
			$op = $this->currentToken['value'];
			$this->consume('OPERATOR');
			$right = $this->factor();
			$node = array('type' => 'BinaryOp', 'op' => $op, 'left' => $node, 'right' => $right);
		}
		return $node;
	}

	private function factor()
	{
		if ($this->currentToken['type'] === 'OPERATOR' && ($this->currentToken['value'] === '+' || $this->currentToken['value'] === '-')) {
			$op = $this->currentToken['value'];
			$this->consume('OPERATOR');
			$right = $this->factor();
			return array('type' => 'UnaryOp', 'op' => $op, 'right' => $right);
		}
		return $this->primary();
	}

	private function primary()
	{
		$token = $this->currentToken;
		if ($token['type'] === 'VARIABLE') {
			$this->consume('VARIABLE');
			return array('type' => 'Variable', 'value' => $token['value']);
		} elseif ($token['type'] === 'NUMBER') {
			$this->consume('NUMBER');
			if ($this->currentToken['type'] === 'PERCENT') {
				$this->consume('PERCENT');
				return array('type' => 'Number', 'value' => (float)$token['value'] / 100.0);
			}
			return array('type' => 'Number', 'value' => (float)$token['value']);
		} elseif ($token['type'] === 'LPAREN') {
			$this->consume('LPAREN');
			$node = $this->expression();
			if ($this->currentToken['type'] !== 'RPAREN') {
				throw new Exception("ERR_UNMATCHED_PAREN: Missing closing parenthesis");
			}
			$this->consume('RPAREN');
			return $node;
		} else {
			if ($token['type'] === 'EOF') {
				throw new Exception("ERR_INCOMPLETE_EXPR: Incomplete formula expression");
			}
			throw new Exception("ERR_INVALID_SYNTAX: Unexpected token " . $token['value']);
		}
	}
}
