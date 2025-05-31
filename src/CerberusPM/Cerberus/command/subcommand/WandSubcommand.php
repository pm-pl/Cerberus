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
use CerberusPM\Cerberus\Exception\InventoryFullException;

class WandSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->setPermission("cerberus.command.wand");
        
        $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        if ($sender instanceof Player) {
            try {
                $this->api->giveWand($sender);
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.wand.given"));
            } catch (InventoryFullException) {
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.wand.inventory_full"));
            }
        } else
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.in-game"));
    }
} 
 
