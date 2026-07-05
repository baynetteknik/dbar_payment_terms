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

class DateCalculator
{
	/**
	 * Calculate due date based on base date and date formula
	 *
	 * @param string $formula Date formula (e.g. "0", "+30", "EOM", "EOM+30", "15M", "N15", "EOM2M")
	 * @param string $baseDate Base date in Y-m-d format
	 * @return string Calculated date in Y-m-d format
	 * @throws Exception on invalid formula
	 */
	public static function calculate($formula, $baseDate)
	{
		$formula = strtoupper(str_replace(' ', '', $formula));
		$date = new DateTime($baseDate);

		if ($formula === '0') {
			return $date->format('Y-m-d');
		}

		// Pattern: +30
		if (preg_match('/^\+(\d+)$/', $formula, $matches)) {
			$days = (int) $matches[1];
			$date->modify("+$days days");
			return $date->format('Y-m-d');
		}

		// Pattern: EOM
		if ($formula === 'EOM') {
			$date->modify('last day of this month');
			return $date->format('Y-m-d');
		}

		// Pattern: EOM+30
		if (preg_match('/^EOM\+(\d+)$/', $formula, $matches)) {
			$days = (int) $matches[1];
			$date->modify('last day of this month');
			$date->modify("+$days days");
			return $date->format('Y-m-d');
		}

		// Pattern: EOM2M
		if (preg_match('/^EOM(\d+)M$/', $formula, $matches)) {
			$months = (int) $matches[1];
			$date->modify("+$months months");
			$date->modify('last day of this month');
			return $date->format('Y-m-d');
		}

		// Pattern: 15M (day 15 of next month)
		if (preg_match('/^(\d+)M$/', $formula, $matches)) {
			$targetDay = (int) $matches[1];
			$date->modify('+1 month');
			$year = (int) $date->format('Y');
			$month = (int) $date->format('m');
			$daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
			$day = min($targetDay, $daysInMonth);
			$date->setDate($year, $month, $day);
			return $date->format('Y-m-d');
		}

		// Pattern: N15 (Next month 15th)
		if (preg_match('/^N(\d+)$/', $formula, $matches)) {
			$targetDay = (int) $matches[1];
			$date->modify('+1 month');
			$year = (int) $date->format('Y');
			$month = (int) $date->format('m');
			$daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
			$day = min($targetDay, $daysInMonth);
			$date->setDate($year, $month, $day);
			return $date->format('Y-m-d');
		}

		throw new Exception("ERR_INVALID_SYNTAX: Invalid date formula syntax: " . $formula);
	}
}
