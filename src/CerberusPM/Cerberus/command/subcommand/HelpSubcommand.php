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

use CerberusPM\Cerberus\Cerberus;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;

class HelpSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->setPermission("cerberus.command.help");
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        $lang_manager = LangManager::getInstance();
        $sender->sendMessage($lang_manager->translate("plugin.in-dev"));
        $sender->sendMessage($lang_manager->translate("plugin.version", [Cerberus::getInstance()->getDescription()->getVersion()]));
        $sender->sendMessage($lang_manager->translate("plugin.selected_language"));
        $sender->sendMessage($lang_manager->translate("plugin.author"));
        //TODO
    }
} 
