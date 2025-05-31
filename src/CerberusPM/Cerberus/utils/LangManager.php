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

namespace CerberusPM\Cerberus\utils;

use pocketmine\utils\TextFormat;

use CerberusPM\Cerberus\Cerberus;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\exception\CerberusLangException;

use function mkdir;
use function is_file;
use function str_replace;
use function yaml_parse_file;
use function is_array;
use function version_compare;
use function rename;
use function gettype;
use function strval;
use function str_contains;
use function strlen;
use function array_push;
use function trim;
use function substr;

/**
 * A class which provides capabilities for plugin messages translation by handling language files and making sure they are up to date.
 */
class LangManager {
    private static LangManager $instance;
    private Cerberus $plugin;
    
    private string $current_language;
    private array $translations;
    private array $default_translations;
    
    private function __construct() {
        //Load selected language.
        $this->plugin = Cerberus::getInstance();
        $this->loadLanguages();
        $this->loadDefaultLanguage();
    }
    
    /**
     * Translate a message by key into the language set in config.yml. If translation is not found tries fetching the translation from default English language file.
     * 
     * @param string   $key    Translation message key set in language files.
     * @param string[] $params Array of values that will replace index variables (e.g., {%0}, {%1}) with corresponding values.
     * 
     * @throws CerberusLangException if translation is not found in current and default language files.
     * 
     * @return string|string[]|null Returns colorized string or array of strings of the translation corresponding to $key. Returns null if translation is empty.
     */
    public function translate(string $key, array $params = []): string|array|null {
        try {
            $translation = $this->translations[$key];
        } catch (\ErrorException) { //Undefined array key
            $default_translation = $this->translateDefault($key, $params);
            if (isset($default_translation)) {
                return $default_translation;
            } else {
                Throw new CerberusLangException("Translation $key was not found in $this->current_language and default embedded language files!");
            }
        }
        if (isset($translation)) {
            if (is_array($translation)) { // If array is passed, array elements will be translated. Is useful for multiline strings
                foreach ($translation as &$translation_string) {
                    foreach($params as $index => $param) {
                        if (gettype($param) == "integer" || gettype($param) == "double")
                            $param = strval($param);
                        $translation_string = str_replace("{%$index}", $param, $translation_string);
                    }
                    $translation_string = $this->parseDeclensions($translation_string);
                    $translation_string = TextFormat::colorize($translation_string);
                }
                return $translation;
            } else {
                foreach ($params as $index => $param) {
                    if (gettype($param) == "integer" || gettype($param) == "double")
                        $param = strval($param);
                    $translation = str_replace("{%$index}", $param, $translation);
                }
                $translation = $this->parseDeclensions($translation);
                return TextFormat::colorize($translation);
            }
        }
        return null;
    }
    
    /**
     * Translate a message by key into the default English language using a language file embedded in plugin source code.
     * 
     * @param string   $key    Translation message key
     * @param string[] $params Array of values that will replace index variables (e.g., {%0}, {%1}) with corresponding values.
     * 
     * @return string|string[]|null Returns colorized string or array of strings of the translation corresponding to $key. Returns null if translation is not found or empty.
     */
    public function translateDefault(string $key, array $params): string|array|null {
        try {
            $translation = $this->default_translations[$key];
        } catch (\ErrorException) { //Undefined array key
            return null;
        }
        if (isset($translation)) {
            if (is_array($translation)) { // If array is passed, array elements will be translated. Is useful for multiline strings
                foreach ($translation as &$translation_string) {
                    foreach($params as $index => $param) {
                        if (gettype($param) == "integer" || gettype($param) == "double")
                            $param = strval($param);
                        $translation_string = str_replace("{%$index}", $param, $translation_string);
                    }
                    $translation_string = $this->parseDeclensions($translation_string);
                    $translation_string = TextFormat::colorize($translation_string);
                }
                return $translation;
            } else {
                foreach ($params as $index => $param) {
                    if (gettype($param) == "integer" || gettype($param) == "double")
                        $param = strval($param);
                    $translation = str_replace("{%$index}", $param, $translation);
                }
                $translation = $this->parseDeclensions($translation);
                return TextFormat::colorize($translation);
            }
        }
        return null;
    }
    
    /**
     * Get current language key as it's set in config.yml
     * 
     * @return string Language key set in the config
     */
    public function getCurrentLanguage(): string {
        return $this->current_language;
    }
    
    /**
     * Get LangManager instance
     * 
     * @return LangManager LangManager instance
     */
    public static function getInstance(): LangManager {
        if (!isset(self::$instance)) {
            self::$instance = new LangManager();
        }
        
        return self::$instance;
    }
    
    /**
     * Reload currently used language file
     */
    public function reload(): void {
        $this->loadLanguages();
    }
    
    /**
     * Parse plugin-unique word declension format
     * @see https://github.com/Levonzie/Cerberus/commit/ca4b058 The commit that has a detailed format description in its message
     * 
     * @param string $message Message, which declension inclusions (if there are any) should be replaced with appropriately declensed word.
     * 
     * @return string Message with proper word declensions by number
     */
    public function parseDeclensions(string $message): string {
        if (!str_contains($message, '!declense')) //Declension keyword
            return $message;
        $new_message = $message;
        
        $declension_map = array();
        $temp_keys = array();
        $message_len = strlen($message);
        $char_buffer = '';
        $block_buffer = '';
        $round_bracket_met = false;
        $curly_bracket_met = false;
        $declension_block_started = false;
        $declensing = false;
        $declense_number = "1";
        for ($i = 0; $i < $message_len; $i++) {
            if ($declension_block_started)
                $block_buffer .= $message[$i];
            if ($message[$i] == '{') {
                $curly_bracket_met = true;
                $declension_block_started = true;
                $block_buffer .= $message[$i];
                continue;
            }
            if ($curly_bracket_met && !$declensing) {
                $char_buffer .= $message[$i]; //Load current char into charbuffer
                if (str_contains($char_buffer, '!declense')) {
                    $declensing = true;
                    $char_buffer = '';
                }
            }
            if ($declensing) {
                if ($message[$i] == '(') {
                    $round_bracket_met = true;
                    continue;
                }
                if ($message[$i] == ')') {
                    $round_bracket_met = false;
                    $declense_number = $char_buffer;
                    $char_buffer = '';
                    continue;
                }
                if ($message[$i] == ',' || $message[$i] == ':') { //Key listing
                    array_push($temp_keys, trim($char_buffer));
                    $char_buffer = '';
                    continue;
                }
                if ($message[$i] == ';' || $message[$i] == '}') { //Time to assign values to declension map keys
                    $value = trim($char_buffer);
                    foreach ($temp_keys as $key)
                        $declension_map[$key] = $value;
                    $temp_keys = array();
                    $char_buffer = '';
                    if ($message[$i] == '}') { //Declension block end reached. Replace declension block with declension result
                        $ending_digits = '';
                        foreach ($declension_map as $key => $value) {
                            $key = strval($key);
                            if (substr($declense_number, -strlen($key)) == $key && strlen($ending_digits) <= strlen($key)) //We make sure that two-digit numbers are prefered over single-digit
                                $ending_digits = $key;
                        }
                        $new_message = str_replace($block_buffer, $declension_map[$ending_digits], $new_message);
                        $block_buffer = '';
                        $declension_block_started = false;
                        $declensing = false;
                        $declension_map = array();
                    }
                    continue;
                }
                $char_buffer .= $message[$i];
            }
        }
        return $new_message;
    }
    
    private function loadLanguages(): void {
        //Fetch the language from the config
        $selected_language = ConfigManager::getInstance()->get("language", false); //We don't need to fetch the default value to show the message later on
        if (!isset($selected_language)) {
            $this->plugin->getLogger()->notice("Language option is not set in config.yml. English will be used by default.");
            $selected_language = "eng";
        }
        $selected_language = str_replace(".yml", "", $selected_language); //In case somebody will add .yml at the end
        $this->current_language = $selected_language;
        
        @mkdir($this->plugin->getDataFolder() . "languages");
        
        //Create language file and load if it doesn't exist
        $selected_lang_path = $this->plugin->getDataFolder() . "languages/$selected_language.yml";
        if (!is_file($selected_lang_path)) { 
            $saved_file = $this->plugin->saveResource("languages/$selected_language.yml");
            
            if (!$saved_file) //Language file was not created
                Throw new CerberusLangException("Specified language $selected_language is not available. Please make sure you use one of the available languages (eng, rus), or manually added appropriate language file in plugin's languages folder.");
            
            $language_contents = yaml_parse_file($selected_lang_path);
            $this->translations = $language_contents;
            return;
        }
        
        //The language file exists. Check if it's alright
        $existing_langfile_contents = yaml_parse_file($selected_lang_path);
        if (!is_array($existing_langfile_contents))
            Throw new CerberusLangException("$selected_language language file is not a valid YAML file or is empty. Please check the syntax");
        $existing_langfile_version = $existing_langfile_contents["language-version"];
        //Version check
        $embedded_langfile_path = $this->plugin->getResourcePath("languages/$selected_language.yml");
        $embedded_langfile_contents = yaml_parse_file($embedded_langfile_path);
        $embedded_langfile_version = $embedded_langfile_contents["language-version"];
        
        if (version_compare($existing_langfile_version, $embedded_langfile_version) < 0) { //Embedded language file is newer
            @rename($selected_lang_path, $selected_lang_path . '.old'); //Backup the old language file
            $this->plugin->saveResource("languages/$selected_language.yml", true);
            $this->plugin->getLogger()->warning(str_replace('{%0}', "$selected_lang_path.old", $embedded_langfile_contents["plugin.outdated_langfile"])); //Languages are not yet loaded for translate() to work, so we'll translate "manually"
        }
        
        $language_contents = yaml_parse_file($selected_lang_path);
        $this->translations = $language_contents;
    }
    
    /**
     * Load translations from default language file, embedded in source code
     */
    private function loadDefaultLanguage(): void {
        $langfile_path = $this->plugin->getResourcePath("languages/eng.yml"); //Default embedded language file
        $this->default_translations = yaml_parse_file($langfile_path);
    }

}
