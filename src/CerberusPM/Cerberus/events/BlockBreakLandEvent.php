<?php
declare(strict_types=1);

namespace CerberusPM\Cerberus;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;  
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;
use CerberusPM\Cerberus\utils\SelectionManager;
use CerberusPM\Cerberus\EventListener;

class BlockBreakLandEvent extends EventListener implements Listener {
    private Cerberus $plugin;
    private CerberusAPI $api;
    private ConfigManager $config_manager;
    private LangManager $lang_manager;

    function __construct(Cerberus $plugin) {
        parent::__construct(); // Call the parent constructor if necessary
        $this->plugin = $plugin;
        $this->api = $plugin->getAPI();
        $this->config_manager = $plugin->getConfigManager();
        $this->lang_manager = $plugin->getLangManager();
    }

     
    public function onBreak(BlockBreakEvent $event): void {
    $player = $event->getPlayer();
    $position = $player->getPosition(); // Get the player's current position

    $land = $this->api->getLandClaimsByPosition($position);

    if ($land === null) {
        // Handle case where the player is not standing on any land
        return; // or some other action
    }

    if ($player === $land->getOwner()) {
        // Owner allowed to break
    }
    elseif ($this->api->isPlayerWhitelisted($player)) {
        // Whitelisted player allowed to break
    }
    else {
        $event->cancel(true); // Cancel the event for other players
    }
    }
}
