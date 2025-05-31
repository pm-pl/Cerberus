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

use CortexPE\Commando\BaseSubCommand;

use CerberusPM\Cerberus\CerberusAPI;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;

use function is_null;
use function count;
use function strval;

class HereSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->setPermission("cerberus.command.here");
        
        $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.in-game"));
            return;
        }
        
        $current_position = $sender->getPosition();
        $landclaims = $this->api->getLandclaimsByPosition($current_position);
        
        $current_position = $current_position->round(1);
        $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.here.current_position", [$current_position->getX(),
                                                                                                                                   $current_position->getY(),
                                                                                                                                   $current_position->getZ()]));
        if (!empty($landclaims)) {
            if (count($landclaims) == 1) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.here.land_here", [$landclaims[0]->getName(), $landclaims[0]->getOwner()]));
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.info.advertisement.specific", [$landclaims[0]->getName()]));
            } else {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.here.multiple.land_here"));
                foreach ($landclaims as $index => $land)
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.here.multiple.land_list_item", [strval($index+1) . '. ', $land->getName(), $land->getOwner()]));
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.info.advertisement.general"));
            }
        }
        else
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.here.no_land_here"));
    }
} 
