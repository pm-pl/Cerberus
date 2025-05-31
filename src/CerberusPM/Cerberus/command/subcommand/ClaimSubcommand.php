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

namespace CerberusPM\Cerberus\command\subcommand;

use pocketmine\command\CommandSender;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\args\RawStringArgument;

use CerberusPM\Cerberus\CerberusAPI;
use CerberusPM\Cerberus\utils\SelectionManager;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;
use CerberusPM\Cerberus\utils\LandManager;
use CerberusPM\Cerberus\Landclaim;

use function is_null;
use function array_push;
use function count;
use function trim;
use function strval;
use function str_contains;
use function substr;
use function strrpos;

class ClaimSubcommand extends BaseSubCommand {
	// V1.0.0 Feature to do is remove deprecated dynamic Properties in every class like so:
/* 	public CerberusAPI $api;
    public ConfigManager $config_manager;
    public LangManager $lang_manager;

    public function __construct() {
        $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    } */

    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("name")); //Name of a landclaim
        
        $this->setPermission("cerberus.command.claim");
		/*
		V1.0.0 Feature todo is to remove
		the deprecated dynamic properties 
		in every class like below and add
		the following features in above 
		todo comment
		VVVV
		*/
          $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
		
		// ^^^^
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        $selector = $sender->getName();
        //Check if all positions are selected 
        if (!SelectionManager::hasSelectedFirst($selector) && !SelectionManager::hasSelectedSecond($selector)) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.select_both_positions"));
            return;
        }
        elseif (!SelectionManager::hasSelectedFirst($selector)) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.select_pos1"));
            return;
        }
        elseif (!SelectionManager::hasSelectedSecond($selector)) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.select_pos2"));
            return;
        }
        else {
            $pos1 = SelectionManager::getSelectedFirstPosition($selector);
            $pos2 = SelectionManager::getSelectedSecondPosition($selector);
        }
        //Check if positions are located in the same world
        if ($pos1[1] != $pos2[1]) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.world_mismatch"));
            return;
        } else {
            $world = $pos1[1];
        }
        //Check if land already exists
        if ($this->api->landExists($args["name"])) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.already_exists", [$args["name"]]));
            return;
        }
        $new_land = new Landclaim($args["name"], $selector, $pos1[0], $pos2[0], $world);
        //Claim limits
        $show_limit_reach_warning = false;
        if ($this->config_manager->get("landclaim-count-limit") || $this->config_manager->get("landclaim-area-limit")) {
            $default_count_limit = $this->config_manager->get("default-landclaim-count-limit");
            $default_area_limit = $this->config_manager->get("default-landclaim-area-limit");
            $count_limit = $area_limit = 0; # We use a different counter for permission-based limits to be able to set the limit to numbers lower than default limit
            $has_count_limit_permission = $has_area_limit_permission = false;
            $no_count_limit = !$this->config_manager->get("landclaim-count-limit"); //As we check both limits in the same if (for foreach loop optimisation), we should understand whether one of those turned on or off
            $no_area_limit = !$this->config_manager->get("landclaim-area-limit");
            foreach ($sender->getEffectivePermissions() as $permission) {
                $permission_string = $permission->getPermission();
                //Landclaim count limit
                if (!$no_count_limit && str_contains($permission_string, "cerberus.command.claim.count_limit.") && $permission->getValue()) {
                    $has_count_limit_permission = true;
                    $limit = substr($permission_string, strrpos($permission_string, '.') + 1);
                    if ($limit == "unlimited")
                        $no_count_limit = true;
                    else if (intval($limit) > $count_limit) //Finding the maximum limit. There might be multiple permissions with different limits set (e.g. because of group inheritance)
                        $count_limit = intval($limit);
                }
                //Landclaim area limit
                if (!$no_area_limit && str_contains($permission_string, "cerberus.command.claim.area_limit.") && $permission->getValue()) {
                    $has_area_limit_permission = true;
                    $limit = substr($permission_string, strrpos($permission_string, '.') + 1);
                    if ($limit == "unlimited")
                        $no_area_limit = true;
                    else if (intval($limit) > $area_limit)
                        $area_limit = intval($limit);
                }
            }
            if (!$has_count_limit_permission) { # Using default limit if user has no limit permissions
                if ($default_count_limit == "unlimited")
                    $no_count_limit = true;
                else
                    $count_limit = intval($default_count_limit);
            }
            if (!$has_area_limit_permission) {
                if ($default_area_limit == "unlimited")
                    $no_area_limit = true;
                else
                    $area_limit = intval($default_area_limit);
            }
            if (!$no_count_limit && count($this->api->listLandOwnedBy($selector)) >= $count_limit) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.landclaim_count_limit_exceeded", [$count_limit]));
                return;
            }
            if ($this->config_manager->get("notify-user-when-count-limit-reached") && !$no_count_limit && count($this->api->listLandOwnedBy($selector)) == $count_limit-1)
                $show_limit_reach_warning = true;
            
            if (!$no_area_limit && $new_land->getArea() > $area_limit) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.landclaim_area_limit_exceeded", [$area_limit, $new_land->getArea()]));
                return;
            }
        }
        //Check if intersects land owned by somebody else
        $intersecting_landclaims = $this->api->getIntersectingLandclaims($new_land);
        if (!empty($intersecting_landclaims)) {
            $owned_by_somebody_else = array();
            foreach ($intersecting_landclaims as $land) { //Make a list of intersecting landclaims owned by other player
                if ($land->getOwner() != $selector)
                    array_push($owned_by_somebody_else, $land);
            }
            if (!empty($owned_by_somebody_else)) { //We allow to intersect landclaims owned by command executer themself
                if (count($owned_by_somebody_else) == 1) { //Intersects only one land. Sending appropriate messages
                    if (!$sender->hasPermission("cerberus.command.claim.bypass_intersect")) {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.intersects", [$owned_by_somebody_else[0]->getName(),
                                                                                                                                              $owned_by_somebody_else[0]->getOwner()]));
                        return;
                    } else {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.intersects.notification", [$owned_by_somebody_else[0]->getName(),
                                                                                                                                                           $owned_by_somebody_else[0]->getOwner()]));
                    }
                } else { //Intersects multiple landclaims. We should provide command executor a list
                    if (!$sender->hasPermission("cerberus.command.claim.bypass_intersect")) {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.intersects.multiple"));
                        foreach ($owned_by_somebody_else as $index => $land)
                            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.intersects.multiple.land_list_item", [strval($index+1) . ". ", $land->getName(), $land->getOwner()]));
                        return;
                    } else {
                        $inline_land_list_message = "";
                        foreach ($owned_by_somebody_else as $index => $land) {//Constructing a beautiful list of intersecting landclaims
                            if ($index+1 == count($owned_by_somebody_else)) //Last array item
                                $trailing_symbol = '';
                            elseif ($index == count($owned_by_somebody_else)-2) //Symbol before last
                                $trailing_symbol = ' ' . $this->lang_manager->translate("misc.and") . ' ';
                            else
                                $trailing_symbol = ", ";
                            $inline_land_list_message .= $this->lang_manager->translate("command.claim.intersects.multiple.inline_land_list_item", [$land->getName(), $land->getOwner()]) . $trailing_symbol;
                        }
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.intersects.multiple.notification", [$inline_land_list_message]));
                    }
                }
            }
        }
        if ($show_limit_reach_warning)
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.landclaim_count_limit_reached_warning", [$count_limit]));
        //Finally create a landclaim
        LandManager::registerLandclaim($new_land);
        SelectionManager::deselectAll($selector);
        
        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.claim.success", [$args["name"]]));
    }
}
