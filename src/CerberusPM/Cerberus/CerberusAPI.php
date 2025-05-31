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

use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\item\Durable;
use pocketmine\item\StringToItemParser;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\world\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\Vector3;

use CerberusPM\Cerberus\Cerberus;
use CerberusPM\Cerberus\utils\ConfigManager;
use CerberusPM\Cerberus\utils\LangManager;
use CerberusPM\Cerberus\utils\LandManager;

use CerberusPM\Cerberus\exception\InventoryFullException;
use CerberusPM\Cerberus\exception\LandExistsException;
use CerberusPM\Cerberus\exception\LandIntersectException;

use function is_array;
use function is_null;
use function array_push;
use function strtolower;

/**
 * An API class which provides all necessary land management methods used by subcommands
 */

class CerberusAPI { 
    private static CerberusAPI $instance;
    private Cerberus $plugin;
    
    private $version = "1.0.1-DEV";
    private array $whitelist = [];
    public const TAG_CERBERUS = "Cerberus";
    public const TAG_WAND = "isWand";
	
	// Extensions:
	
    private $isUIEventListenerEnabled = false;
    // by default this will be false UNLESS you have installed CerberusUI to AVOID CRASHES;
	
	// CODE:
	
    private function __construct() {
        $this->plugin = Cerberus::getInstance();
    }
    
    /*
     * Get API class instance
     * 
     * @return CerberusAPI CerberusAPI instance
     */
    public static function getInstance(): CerberusAPI {
        if (!isset(self::$instance)) {
            self::$instance = new CerberusAPI();
        }
        
        return self::$instance;
    }
    
    /**
     * Get API version
     * 
     * @return string version
     */
    public function getVersion(): string {
        return $this->version;
    }
    
    /**
    * Get UIEventListener Status
    *
    * @return bool isUIEventListenerEnabled
    */
    public function isUIEventListenerEnabled(): bool {
        return $this->isUIEventListenerEnabled;
    }
    
    /**
     * Get owning plugin (Cerberus) instance
     * 
     * @return Cerberus Cerberus instance
     */
    public function getOwningPlugin(): Cerberus {
        return $this->plugin;
    }
    
    /**
     * Give player a wand
     * 
     * @param Player $player Player who will receive a wand
     * 
     * @throws InventoryFullException if inventory is full and there's no place for wand
     */
    public function giveWand(Player $player): void {
        $this->config_manager = ConfigManager::getInstance();
        $this->language_manager = LangManager::getInstance();
        
        //Construct the wand item
        $wand_id = $this->config_manager->get("wand-item");
        $wand_item = StringToItemParser::getInstance()->parse($wand_id) ?? LegacyStringToItemParser::getInstance()->parse($wand_id);
        if ($wand_item instanceof Durable)
            $wand_item->setUnbreakable(true);
        // Set custom name
        if ($this->config_manager->get("wand-use-default-name"))
            $wand_name = $this->language_manager->translate("wand.name"); // LangManager returns already colorized string
        else
            $wand_name = $this->config_manager->get("wand-name", false); // Don't use the value from default config as the one from language file will be used
        if (!empty($wand_name))
            $wand_item->setCustomName(TextFormat::colorize($wand_name));
        else //Looks like name option is left blank. Applying the default name
            $wand_item->setCustomName($this->language_manager->translate("wand.name"));
        // Set lore
        $lore_already_colorized = false;
        if ($this->config_manager->get("wand-use-default-lore")) {
            $wand_lore = $this->language_manager->translate("wand.lore");
            $lore_already_colorized = true;
        } else
            $wand_lore = $this->config_manager->get("wand-lore", false);
        
        if (!empty($wand_lore)) {
            if (!is_array($wand_lore)) // If lore is string, convert to array with one element since Item->setLore() requieres an array of strings
                $wand_lore = array($wand_lore);
            if (!$lore_already_colorized) {
                foreach ($wand_lore as &$lore_string)
                    $lore_string = TextFormat::colorize($lore_string);
                unset($lore_string);
            }
            $wand_item->setLore($wand_lore);
        }
        //Set enchantments
        $wand_enchantments = $this->config_manager->get("wand-enchantments");
        if (is_array($wand_enchantments)) {
            foreach ($wand_enchantments as $ench_name => $ench_lvl) {
                $ench = StringToEnchantmentParser::getInstance()->parse($ench_name);
                if (!empty($ench)) {
                    $ench_instance = new EnchantmentInstance($ench, $ench_lvl);
                    $wand_item->addEnchantment($ench_instance);
                }
            }
        }
        // Set NBT
        $cerberus_compound_tag = CompoundTag::create();
        $cerberus_compound_tag->setByte(self::TAG_WAND, 1); //This NBT tag makes wand a wand
        $wand_item->getNamedTag()->setTag(self::TAG_CERBERUS, $cerberus_compound_tag);
        //Give the item
        $player_inv = $player->getInventory();
        $selected_item = $player_inv->getItemInHand(); // Retreive currently held item, so that it'll be possible to move it to a different slot
        if ($player_inv->canAddItem($wand_item)) { // Check if inventory is full
            $player_inv->setItemInHand($wand_item); // Replace currently held item (or air) with the wand item
            $player_inv->addItem($selected_item); // Return previously held item to player by adding it to an available empty slot
        } else {
            Throw new InventoryFullException("Inventory is full");
        }
    }
    
    /**
     * Check whether item is wand
     * 
     * @param Item $item An item for wand check
     *                   Turned off by default - any item with isWand NBT tag are considered to be a wand.
     * 
     * @return bool True if is wand, false if is not a wand
     */
    public function isWand(Item $item): bool {
        if ($item->hasNamedTag()) {
            $wand_tag = $item->getNamedTag()->getCompoundTag(self::TAG_CERBERUS)->getTag(self::TAG_WAND);
            if (isset($wand_tag) && $wand_tag->getValue() == 1)
                return true;
        }
        return false;
    }
    
    /**
     * Create a landclaim
     * 
     * @param string  $land_name              Name of the landclaim (should be unique)
     * @param string  $land_owner             Landclaim owner name (who this landclaim will belong to)
     * @param Vector3 $pos1                   First position of the landclaim
     * @param Vector3 $pos2                   Second position of the landclaim
     * @param string  $world_name             Folder name of the world, where the landclaim will be created
     * @param bool    $check_for_intersection Whether to check if specified landclaim intersects a landclaim of another owner
     *
     * @throws LandExistsException    if a landclaim with given $land_name already exists
     * @throws LandIntersectException if intersection check is performed and resulting landclaim intersects a landclaim of another owner
     */
    public function createLand(string $land_name, string $land_owner, Vector3 $pos1, Vector3 $pos2, string $world_name, bool $check_for_intersection=true): void {
        if ($this->landExists($land_name))
            Throw new LandExistsException("Landclaim named $land_name already exists!");
        $land = new Landclaim($land_name, $land_owner, $pos1, $pos2, $world_name);
        if ($check_for_intersection) {
            $intersecting_land = $this->getIntersectingLand($land);
            if (!is_null($intersecting_land) && $land->getOwner() != $intersecting_land->getOwner())
                Throw new LandIntersectException("Resulting landclaim intersects landclaim" . $intersecting_land->getName() . " owned by " . $intersecting_land->getOwner(), 0, null, $land, $intersecting_land);
        }
        LandManager::registerLandclaim($land);
    }
    
    /**
     * Remove landclaim by name
     * 
     * @param string $land_name Name of a landclaim to remove
     */
    public function removeLand(string $land_name): void {
        LandManager::unregisterLandclaim($land_name);
    }
    
    /**
     * Get an array of landclaims containing position
     * 
     * @param Position $position                Position to be checked for inclusion in a landclaim
     * @param bool     $stop_on_first_occurance Whether to stop on fist occurance
     * 
     * @return Landclaim[] Array of landclaims containing given position. Empty array if no such landclaims were found
     */
    public function getLandclaimsByPosition(Position $position, bool $stop_on_first_occurance=false): array {
        $landclaims = array();
        foreach(LandManager::getLandclaims() as $land) {
            if ($land->containsPosition($position)) {
                array_push($landclaims, $land);
                if ($stop_on_first_occurance)
                    break;
            }
        }
        return $landclaims;
    }
    
    /**
     * Get landclaim with given name or null if it doesn't exist
     * 
     * @param string $land_name Name of a landclaim to get.
     * 
     * @return Landclaim|null Landclaim if exists, null if doesn't exist
     */
    public function getLandByName(string $land_name): Landclaim|null {
        if (LandManager::exists($land_name))
            return LandManager::getLandclaims()[$land_name];
        else
            return null;
    }
    
    /**
     * Check if landclaim with given name exists
     * 
     * @param string $land_name Land name to be checked for existance
     * 
     * @return bool Whether land exists or not
     */
    public function landExists(string $land_name): bool {
        return LandManager::exists($land_name);
    }
    
    /**
     * Get an array of landclaims which itersect given landclaim
     * 
     * @param Landclaim $land                    Landclaim to check for intersection
     * @param bool      $stop_on_first_occurance Whether to stop on first occurance
     * 
     * @return Landclaim[] Array of lanclaims which intersect given landclaim. Empty array if no such landclaims were found
     */
    public function getIntersectingLandclaims(Landclaim $land, bool $stop_on_first_occurance=false): array {
        $landclaims = array();
        foreach(LandManager::getLandclaims() as $land2) {
            if ($land->intersectsLandclaim($land2)) {
                array_push($landclaims, $land2);
                if ($stop_on_first_occurance)
                    break;
            }
        }
        return $landclaims;
    }
    
    /**
     * Get an array of landclaims owned by specified owner
     * 
     * @param string $land_owner Exact name of whose landclaim list has to be returned
     * 
     * @return Landclaim[] Array of landclaims owned by specified owner. Empty array if has no landclaims
     */
    public function listLandOwnedBy(string $land_owner): array {
        $landclaims = array();
        $land_owner = strtolower($land_owner);
        foreach(LandManager::getLandclaims() as $land) {
            if (strtolower($land->getOwner()) == $land_owner)
                array_push($landclaims, $land);
        }
        return $landclaims;
    }
    
public function addPlayerToWhitelist(string $player): void {
        if (!in_array($player, $this->whitelist)) {
            $this->whitelist[] = $player;
        }
    }

    public function removePlayerFromWhitelist(string $player): void {
        $index = array_search($player, $this->whitelist);
        if ($index !== false) {
            unset($this->whitelist[$index]);
            $this->whitelist = array_values($this->whitelist);  // Re-index the array
        }
    }

    public function getWhitelist(): array {
        return $this->whitelist;
    }
    private function isPlayerWhitelisted(string $name): bool {
    if (empty($name)) {
        return false;
    }

    // Check if the name exists in the whitelist
    if (!in_array($name, $this->api->getWhitelist())) {
        return false;
    }
    return true;
    }
}
