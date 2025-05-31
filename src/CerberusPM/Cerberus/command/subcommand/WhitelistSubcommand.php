<?php

declare(strict_types=1);

namespace CerberusPM\Cerberus\command\subcommand;

use pocketmine\command\CommandSender;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\args\RawStringArgument;

class WhitelistSubcommand extends BaseSubCommand {
    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("operation"));
        $this->registerArgument(1, new RawStringArgument("land_name"));
        $this->registerArgument(2, new RawStringArgument("player"));
        
        $this->setPermission("cerberus.command.whitelist");
    }

    public function onRun(CommandSender $sender, string $alias, array $args): void {
        if (!isset($args[0])) {
            $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.specify"));
            return;
        }

        switch ($args[0]) {
            case "add":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.should_specify_land_name"));
                    return;
                }
                if (!isset($args[2])) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.specify.player"));
                    return;
                }

                $land = $this->api->getLandByName($args[1]);
                if ($land === null) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.land_does_not_exist", [$args[1]]));
                    return;
                }
                if ($land->getOwner() !== $sender->getName() && !$sender->hasPermission("cerberus.command.whitelist.add")) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.no_other"));
                    return;
                }

                $this->api->addPlayerToWhitelist($args[2]);
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.success", [$args[1]]));
                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.should_specify_land_name.other"));
                    return;
                }
                if (!isset($args[2])) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.specify.player"));
                    return;
                }

                $land = $this->api->getLandByName($args[1]);
                if ($land === null) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.land_does_not_exist", [$args[1]]));
                    return;
                }
                if ($land->getOwner() !== $sender->getName() && !$sender->hasPermission("cerberus.command.whitelist.remove")) {
                    $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.no_other"));
                    return;
                }

                $this->api->removePlayerFromWhitelist($args[2]);
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.success.other", [$args[1]]));
                break;

            default:
                $sender->sendMessage($this->config_manager->getPrefix() . $this->lang_manager->translate("command.whitelist.invalidargs"));
                break;
        }
    }
}
