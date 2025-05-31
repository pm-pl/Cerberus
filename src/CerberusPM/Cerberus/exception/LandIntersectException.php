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

namespace CerberusPM\Cerberus\exception;

use CerberusPM\Cerberus\exception\CerberusException;
use CerberusPM\Cerberus\Landclaim;

/**
 * Thrown by CerberusAPI when two lands intersect and intersation check is not bypassed
 */
class LandIntersectException extends CerberusException {
    /** @var Landclaim Landclaim which is checked for intersection with another landclaim */
    protected Landclaim $checked_land;
    /** @var Landclaim Landclaim which is found to be intersecting checked landclaim */
    protected Landclaim $intersecting_land;
    
    public function __construct($message="",$code=0, Exception $previous=null, Landclaim $checked_land=null, $intersecting_land=null) {
        $this->checked_land = $checked_land;
        $this->intersecting_land = $intersecting_land;
        parent::__construct($message, $code, $previous);
    }
    
    public function getCheckedLand(): Landclaim|null {
        return $this->checked_land;
    }
    
    public function getIntersectingLand(): Landclaim|null {
        return $this->intersecting_land;
    }
}
