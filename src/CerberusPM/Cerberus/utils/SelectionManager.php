<?php

/**
 * Cerberus - an advanced land protection plugin for PocketMine-MP 5.
 * Copyright (C) 2025 CerberusPM
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace CerberusPM\Cerberus\utils;

use pocketmine\world\Position;

use function array_key_exists;

/**
 * A class for selection management
 */
class SelectionManager {
    /** @var array $selectingFirstPosition Stores first positions and who've set them. */
    private static array $selectingFirstPosition = [];
    /** @var array $selectingFirstPosition Stores second positions and who've set them. */
    private static array $selectingSecondPosition = [];
    
    private function __construct() { }
    
    /**
     * Select the first position
     * 
     * @param string   $selector Exact name of who selects the position
     * @param Position $position Position in the world to be set as the first position
     * 
     */
    public static function selectFirstPosition(string $selector, Position $position): void {
        self::$selectingFirstPosition[$selector] = [$position->floor(), $position->getWorld()->getFolderName()];
    }
    
    /**
     * Select the second position
     * 
     * @param string   $selector Exact name of who selects the position
     * @param Position $position Position in the world to be set as the second position
     */
    public static function selectSecondPosition(string $selector, Position $position): void {
        self::$selectingSecondPosition[$selector] = [$position->floor(), $position->getWorld()->getFolderName()];
    }
    
    /**
     * Unset previously selected first position
     * 
     * @param string $selector Exact name of whose first position selection has to be unset
     */
    public static function deselectFirstPosition(string $selector): void {
        unset(self::$selectingFirstPosition[$selector]);
    }
    
    /**
     * Unset previously selected second position
     * 
     * @param string $selector Exact name of whose first position selection has to be unset
     */
    public static function deselectSecondPosition(string $selector): void {
        unset(self::$selectingSecondPosition[$selector]);
    }
    
    /**
     * Unset all selected positions of $selector
     * 
     * @param string $selector Exact name of whose first and seconf position selection has to be cleared
     */
    public static function deselectAll(string $selector): void {
        self::deselectFirstPosition($selector);
        self::deselectSecondPosition($selector);
    }
    
    /**
     * Check by name if has selected any positions
     * 
     * @param string $selector Exact name of whose presence of any position selection has to be checked 
     * 
     * @return bool True if any position has been selected by the given name, false if not
     */
    public static function hasSelected(string $selector): bool {
        return array_key_exists($selector, self::$selectingFirstPosition) || array_key_exists($selector, self::selectingSecondPosition);
    }
    
    /**
     * Check by name if has selected first position
     * 
     * @param string $selector Exact name of whose first position selection has to be checked
     * 
     * @return bool True if first position has been selected, false if not.
     */
    public static function hasSelectedFirst(string $selector): bool {
        return array_key_exists($selector, self::$selectingFirstPosition);
    }
    
    /**
     * Check by name if has selected second position
     * 
     * @param string $selector Exact name of whose second position selection has to be checked
     * 
     * @return bool True if second position has been selected, false if not.
     */
    public static function hasSelectedSecond(string $selector): bool {
        return array_key_exists($selector, self::$selectingSecondPosition);
    }
    
    /**
     * Get the first position selected by $selector
     * 
     * @param string $selector Exact name of whose first selection position is to be get
     * 
     * @return Position|null Returns pocketmine\World\Position or null if position is not selected
     */
    public static function getSelectedFirstPosition(string $selector): array | null {
        if (array_key_exists($selector, self::$selectingFirstPosition))
            return self::$selectingFirstPosition[$selector];
        else
            return null;
    }
    
    /**
     * Get the second position selected by $selector
     * 
     * @param string $selector Exact name of whose second selection position is to be get
     * 
     * @return Position|null Returns pocketmine\World\Position or null if position is not selected
     */
    public static function getSelectedSecondPosition(string $selector): array | null {
        if (array_key_exists($selector, self::$selectingSecondPosition))
            return self::$selectingSecondPosition[$selector];
        else
            return null;
    }
    
}
