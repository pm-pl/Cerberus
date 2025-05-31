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
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;

use function is_null;

class RemoveSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("land name"));
        
        $this->setPermission("cerberus.command.remove");
        
        $this->api = CerberusAPI::getInstance();
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        $land = $this->api->getLandByName($args["land name"]);
        if (is_null($land)) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.remove.land_does_not_exist", [$args["land name"]]));
            return;
        }
        if ($land->getOwner() != $sender->getName() && !$sender->hasPermission("cerberus.command.remove.other")) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.remove.no_other"));
            return;
        }
        $this->api->removeLand($args["land name"]);
        
        if ($land->getOwner() != $sender->getName())
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.remove.other.success", [$args["land name"], $land->getOwner()]));
        else
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.remove.success", [$args["land name"]]));
    }
} 
 
