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

declare(strict_types=1);

namespace CerberusPM\Cerberus\utils;

use CerberusPM\Cerberus\Landclaim;
use CerberusPM\Cerberus\exception\LandExistsException;

use function array_key_exists;

/**
 * Class for landclaim management
 */
class LandManager {
    private static array $landclaims = [];
    
    /**
     * Register a landclaim
     * 
     * @param Landclaim $land A landclaim to register
     * 
     * @throws LandExistsException if a landclaim already exists   
     */
    public static function registerLandclaim(Landclaim $land): void {
        $land_name = $land->getName();
        if (self::exists($land_name))
            Throw new LandExistsException("Landclaim named $land_name already exists!");
        self::$landclaims[$land_name] = $land;
    }
    
    /**
     * Unregister (delete) a landclaim
     * 
     * @param string $land_name Name of a landclaim to unregister
     */
    public static function unregisterLandclaim(string $land_name): void {
        if (self::exists($land_name))
            unset(self::$landclaims[$land_name]);
    }
    
    /**
     * @return Landclaim[] Array of all registered landclaims
     */
    public static function getLandclaims(): array {
        return self::$landclaims;
    }
    
    /**
     * Check if landclaim with given name exists
     * 
     * @param string $land_name Land name to be checked for existance
     * 
     * @return bool Whether land exists or not
     */
    public static function exists(string $land_name): bool {
        return array_key_exists($land_name, self::getLandclaims());
    }
}
