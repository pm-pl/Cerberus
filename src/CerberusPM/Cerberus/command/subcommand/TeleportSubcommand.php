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
use pocketmine\player\Player;
use pocketmine\world\Position;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\args\RawStringArgument;

use CerberusPM\Cerberus\CerberusAPI;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;

use function count;
use function array_push;

class TeleportSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("land name", true)); //If omitted, teleports to the spawnpoint of the land player is standing at. Shows usage message if not in land bounds and land name is not specified
        $this->registerArgument(1, new RawStringArgument("player name", true)); //Optional. Player to teleport
        
        $this->setPermission("cerberus.command.teleport");
        
        $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        //Get one landclaim, suitable for teleportation
        //By the end of execution of this code section $land should contain a landclaim player will be teleported to
        if (isset($args["land name"])) {
            $land = $this->api->getLandByName($args["land name"]);
            if (!isset($land)) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.land_does_not_exist", [$args["land name"]]));
                return;
            }
            if ($land->getOwner() != $sender->getName() && !$sender->hasPermission("cerberus.command.teleport.to.other")) {
                if (isset($args["player name"]))
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.to.no_other.no_other"));
                else
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.to.no_other"));
                return;
            }
            if ($land->getSpawnpoint() === null) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.land_has_no_spawnpoint", [$args["land name"]]));
                return;
            }
        } else {
            if ($sender instanceof Player) { //Figure out a landclaim where player is standing
                $landclaims = $this->api->getLandclaimsByPosition($sender->getPosition());
                if (empty($landclaims)) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.no_land_at_current_position"));
                    return;
                }
                if (count($landclaims) > 1) {
                    $landclaims_avail_for_tp = array();
                    foreach ($landclaims as $landclaim) {
                        if ($landclaim->getSpawnpoint() !== null) {
                            if ($landclaim->getOwner() == $sender->getName()) {
                                array_push($landclaims_avail_for_tp, $landclaim);
                                continue;
                            }
                            if ($landclaim->getOwner() != $sender->getName() && $sender->hasPermission("command.teleport.to.other"))
                                array_push($landclaims_avail_for_tp, $landclaim);
                        }
                    }
                    if (count($landclaims_avail_for_tp) == 1)
                        $land = $landclaims_avail_for_tp[0];
                    else if (count($landclaims_avail_for_tp) < 1) {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.multiple_unsuitable_land_at_current_pos"));
                        return;
                    } else {
                        $message = $this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.multiple_suitable_land_at_current_pos");
                        if ($sender->hasPermission("cerberus.command.here"))
                            $message .= ' ' . $this->lang_manager->translate("command.teleport.multiple_suitable_land_at_current_pos.here_ad");
                        $sender->sendMessage($message);
                        return;
                    }
                }
                if (count($landclaims) == 1) {
                    if ($landclaims[0]->getOwner() != $sender->getName() && !$sender->hasPermission("cerberus.command.teleport.to.other")) {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.to_here.no_other"));
                        return;
                    }
                    $land = $landclaims[0];
                    if ($land->getSpawnpoint() === null) {
                        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.land_here_has_no_spawnpoint", [$land->getName()]));
                        return;
                    }
                }
            } else {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.should_specify_land_name"));
                return;
            }
        }
        //Get a player who will get teleported
        if (isset($args["player name"])) {
            $player = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($args["player name"]);
            if (!$sender->hasPermission("cerberus.command.teleport.other") && !(isset($player) && $player->getName() == $sender->getName())) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.no_other"));
                return;
            }
            if (!isset($player)) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.player_not_found", [$args["player name"]]));
                return;
            }
        } else {
            if ($sender instanceof Player)
                $player = $sender;
            else {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.must_be_player"));
                return;
            }
        }
        //Teleport player
        $player->teleport(Position::fromObject($land->getSpawnpoint(), $this->getOwningPlugin()->getServer()->getWorldManager()->getWorldByName($land->getWorldName())));
        if (isset($args["player name"])) {
            if ($player->getName() != $sender->getName())
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.success.other", [$player->getName(), $land->getName()]));
            if ($this->config_manager->get("notify-player-on-teleportation") || $player->getName() == $sender->getName())
                $player->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.success", [$land->getName()]));
        } else
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.teleport.success", [$land->getName()]));
    }
} 
