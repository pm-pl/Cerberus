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

namespace CerberusPM\Cerberus;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;

use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;
use CerberusPM\Cerberus\utils\SelectionManager;

/**
 * Main event listener used for wand position selection
 */
class EventListener implements Listener {
    private Cerberus $plugin;
    private CerberusAPI $api;
    private ConfigManager $config_manager;
    private LangManager $lang_manager;
    
    function __construct(Cerberus $plugin) {
        $this->plugin = $plugin;
        $this->api = $plugin->getAPI();
        $this->config_manager = $plugin->getConfigManager();
        $this->lang_manager = $plugin->getLangManager();
    }
    
    /**
     * Determines whether player has permission to use wand and holds a wand, if so setting the first and the second position:
     * - Sets first position on left click (start break)
     * - Sets second position on right click (use)
     * 
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        
        if ($player->hasPermission("cerberus.command.selection") && $this->api->isWand($event->getItem())) {
            $event->cancel();
            
            $position = $event->getBlock()->getPosition();
            
            if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) { // start break
                SelectionManager::selectFirstPosition($player->getName(), $position);
                $player->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos1.selected", [$position->getX(), $position->getY(), $position->getZ()]));
            } else if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) { // use
                SelectionManager::selectSecondPosition($player->getName(), $position);
                $player->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.pos2.selected", [$position->getX(), $position->getY(), $position->getZ()]));
            }
        }
    }
    
    /**
     * Position setting is done only through PlayerInteractEvent to allow position setting in survival mode (it takes a long time to break some blocks in survival).
     * BlockBreakEvent here is used to cancel block breaking when using a wand to prevent any block destruction.
     * 
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event): void {
        if ($event->getPlayer()->hasPermission("cerberus.command.selection") && $this->api->isWand($event->getItem()))
            $event->cancel();
    }
}
