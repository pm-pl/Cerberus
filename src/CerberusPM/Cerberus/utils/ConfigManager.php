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

namespace CerberusPM\Cerberus\utils;

use pocketmine\utils\TextFormat;

use CerberusPM\Cerberus\Cerberus;
use CerberusPM\Cerberus\utils\LangManager;

use function is_file;
use function yaml_parse_file;
use function version_compare;
use function rename;

/**
 * A class for plugin configuration management
 */
class ConfigManager {
    private static ConfigManager $instance;
    
    private Cerberus $plugin;
    
    private array $settings;
    private array $default_settings;
    
    private function __construct() {
        $this->plugin = Cerberus::getInstance();
        
        $this->loadDefaultConfig(); // Load default settings before to make sure they will be used in version compare
        $this->loadConfig();
    }
    
    /**
     * Get ConfigManager instance
     * 
     * @return ConfigManager ConfigManager instance
     */
    public static function getInstance(): ConfigManager {
        if (!isset(self::$instance)) {
            self::$instance = new ConfigManager();
        }
        
        return self::$instance;
    }
    
    /**
     * Get a value of a setting from config.yml by setting name
     * 
     * @param string $setting                  Setting name (key) from config.yml
     * @param bool   $use_default_if_not_found Whether to try fetching a setting from default config if setting is not found
     * 
     * @return mixed Returns a value of corresponding setting in config.yml. Returns null if requested setting is not found
     */
    public function get(string $setting, bool $use_default_if_not_found=true) {
        try {
            return $this->settings[$setting];
        } catch (\ErrorException) {
            if ($use_default_if_not_found)
                return $this->getDefault($setting);
            return null;
        }
    }
    
    /**
     * Get a default value of a setting from config.yml embedded in source code
     * 
     * @param string $setting Setting name (key) from config.yml
     */
    public function getDefault(string $setting) {
        try {
            return $this->default_settings[$setting];
        } catch (\ErrorException) { //Undefined array key
            return null;
        }
    }
    
    /**
     * Get plugin prefix set in config.yml or, if not set, the default prefix
     * 
     * @return string Colorized prefix from config or default one
     */
    public function getPrefix(): string {
        try {
            return TextFormat::colorize($this->settings["prefix"]);
        } catch (\ErrorException) { //Prefix is not set in the config
            return TextFormat::colorize($this->default_settings["prefix"]);
        }
    }
    
    /**
     * Reload the configuration
     */
    public function reload(): void {
        $this->plugin->getConfig()->reload();
        $this->loadConfig();
    }
    
    private function loadConfig(): void {
        $existing_conf_path = $this->plugin->getDataFolder() . "config.yml";
        $conf_already_existed = is_file($existing_conf_path);
        $conf_updated = false;
        
        $config = $this->plugin->getConfig();

        if ($conf_already_existed) {
            $existing_conf_version = $config->get("version");
            $embedded_conf_version = $this->default_settings["version"];
            
            if (version_compare($existing_conf_version, $embedded_conf_version) < 0) { //Embedded config is newer. Fires even when verison is not set and config file is an empty array
                @rename($existing_conf_path, $existing_conf_path . ".old"); //Backup the old config
                $this->plugin->saveDefaultConfig(); //Create new config
                $conf_updated = true;
            }
            $config->reload();
        }
        
        $this->settings = $config->getAll();
        
        if ($conf_updated) //We can use LangManager only after settings are loaded
            $this->plugin->getLogger()->warning(LangManager::getInstance()->translate("plugin.outdated_config", ["$existing_conf_path.old"])); //Notify user
        //TODO: Make the config retain settings after update
    }
    
    private function loadDefaultConfig(): void {
        $default_config_path = $this->plugin->getResourcePath("config.yml"); //Default embedded config
        $this->default_settings = yaml_parse_file($default_config_path);
    }
}
