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

use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\player\Player;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\args\Vector3Argument;
use CortexPE\Commando\args\RawStringArgument;

use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;
use CerberusPM\Cerberus\utils\SelectionManager;

use function strval;

class FirstPositionSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->registerArgument(0, new Vector3Argument("position", true)); //Optional. If not specified uses current player position
        $this->registerArgument(1, new RawStringArgument("world", true)); //World name. Optional. If not set uses the world player is currently in
        
        $this->setPermission("cerberus.command.selection");
        
        $this->config_manager = ConfigManager::getInstance();
        $this->lang_manager = LangManager::getInstance();
    }
    
    public function onRun(CommandSender $sender, string $alias, array $args): void {
        if (isset($args["position"])) { //Set first position to specified position
            if (isset($args["world"])) {
                $world = Server::getInstance()->getWorldManager()->getWorldByName($args["world"]);
                if (!isset($world)) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos.world_not_found", [$args["world"]]));
                    return;
                }
            } else {
                if ($sender instanceof Player)
                    $world = $sender->getPosition()->getWorld();
                else {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos.should_select_world"));
                    return;
                }
            }
            SelectionManager::selectFirstPosition($sender->getName(), Position::fromObject($args["position"], $world));
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos1.selected.world", [$args["position"]->getFloorX(),
                                                                                                                                     $args["position"]->getFloorY(),
                                                                                                                                     $args["position"]->getFloorZ(),
                                                                                                                                     $world->getFolderName()]));
        } else { 
            //Position is not set manually. We should figure out player's current position or notify if the command is being used not from the game
            if ($sender instanceof Player) {
                $position = $sender->getPosition();
                SelectionManager::selectFirstPosition($sender->getName(), $position);
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos1.selected", [$position->getFloorX(),
                                                                                                                                   $position->getFloorY(),
                                                                                                                                   $position->getFloorZ()]));
            } else
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos.in-game"));
        }
    }
} 
 
