<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Dirt;
use pocketmine\block\Grass;
use pocketmine\block\TallGrass;
use pocketmine\entity\Entity;
use pocketmine\item\ItemIds;
use pocketmine\network\protocol\EntityEventPacket;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\nbt\tag\ByteTag;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;
use revivalpmmp\pureentities\features\IntfShearable;
use revivalpmmp\pureentities\InteractionHelper;
use revivalpmmp\pureentities\PureEntities;

class Sheep extends WalkingAnimal implements IntfCanBreed, IntfCanInteract, IntfShearable {
    const NETWORK_ID = Data::SHEEP;

    const DATA_COLOR_INFO = 16;

    const WHITE = 0;
    const ORANGE = 1;
    const MAGENTA = 2;
    const LIGHT_BLUE = 3;
    const YELLOW = 4;
    const LIME = 5;
    const PINK = 6;
    const GRAY = 7;
    const LIGHT_GRAY = 8;
    const CYAN = 9;
    const PURPLE = 10;
    const BLUE = 11;
    const BROWN = 12;
    const GREEN = 13;
    const RED = 14;
    const BLACK = 15;

    const NBT_KEY_COLOR = "Color";
    const NBT_KEY_SHEARED = "Sheared";


    public $width = 0.625;
    public $length = 1.4375;
    public $height = 1.8;

    private $feedableItems = array(Item::WHEAT);

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingExtension
     */
    private $breedableClass;

    public function getName() {
        return "Sheep";
    }

    public static function getRandomColor(): int {
        $rand = "";
        $rand .= str_repeat(self::WHITE . " ", 20);
        $rand .= str_repeat(self::ORANGE . " ", 5);
        $rand .= str_repeat(self::MAGENTA . " ", 5);
        $rand .= str_repeat(self::LIGHT_BLUE . " ", 5);
        $rand .= str_repeat(self::YELLOW . " ", 5);
        $rand .= str_repeat(self::GRAY . " ", 10);
        $rand .= str_repeat(self::LIGHT_GRAY . " ", 10);
        $rand .= str_repeat(self::CYAN . " ", 5);
        $rand .= str_repeat(self::PURPLE . " ", 5);
        $rand .= str_repeat(self::BLUE . " ", 5);
        $rand .= str_repeat(self::BROWN . " ", 5);
        $rand .= str_repeat(self::GREEN . " ", 5);
        $rand .= str_repeat(self::RED . " ", 5);
        $rand .= str_repeat(self::BLACK . " ", 10);
        $arr = explode(" ", $rand);
        return intval($arr[mt_rand(0, count($arr) - 1)]);
    }

    public function initEntity() {
        parent::initEntity();

        $this->breedableClass = new BreedingExtension($this);
        $this->breedableClass->init();

        $this->setColor($this->getColor());
        $this->setSheared($this->isSheared());

    }

    /**
     * Returns the breedable class or NULL if not configured
     *
     * @return BreedingExtension
     */
    public function getBreedingExtension() {
        return $this->breedableClass;
    }

    /**
     * Returns the appropiate NetworkID associated with this entity
     * @return int
     */
    public function getNetworkId() {
        return self::NETWORK_ID;
    }

    /**
     * Returns the items that can be fed to the entity
     *
     * @return array
     */
    public function getFeedableItems() {
        return $this->feedableItems;
    }

    public function checkTarget(bool $checkSkip = true) {
        if (($checkSkip and $this->isCheckTargetAllowedBySkip()) or !$checkSkip) {
            if ($this->isSheared()) {
                $this->checkBlockOfInterest();
            }
            // and of course, we should call the parent check target method (which has to call breeding methods)
            parent::checkTarget(false);
        }
    }

    public function getDrops() {
        $drops = [];
        if (!$this->isSheared() && !$this->getBreedingExtension()->isBaby()) {
            $drops = [Item::get(Item::WOOL, self::getColor(), mt_rand(0, 2))];
        }
        return $drops;
    }

    /**
     * The initEntity method of parent uses this function to get the max healthand set in NBT
     *
     * @return int
     */
    public function getMaxHealth() {
        return 8;
    }

    /**
     * Is called by EventListener. This function shears a sheep.
     *
     * @param Player $player
     * @return bool
     */
    public function shear(Player $player): bool {
        if ($this->isSheared()) { // already sheared
            return false;
        } else { // not sheared yet
            // drop correct wool color by calling getDrops of the entity (the entity knows what to drop!)
            foreach ($this->getDrops() as $drop) {
                $player->getLevel()->dropItem($this, $drop);
            }
            // set the sheep sheared
            $this->setSheared(true);
            // reset button text to empty string
            InteractionHelper::displayButtonText("", $player);
            return true;
        }
    }

    // ------------------------------------------------------------
    // very sheep specific functions
    // ------------------------------------------------------------


    /**
     * Checks if this entity is sheared
     * @return bool
     */
    public function isSheared(): bool {
        if (!isset($this->namedtag->Sheared)) {
            $this->namedtag->Sheared = new ByteTag(self::NBT_KEY_SHEARED, 0); // set not sheared
        }
        return (bool)$this->namedtag[self::NBT_KEY_SHEARED];
    }

    /**
     * Sets this entity sheared or not
     *
     * @param bool $sheared
     */
    public function setSheared(bool $sheared) {
        $this->namedtag->Sheared = new ByteTag(self::NBT_KEY_SHEARED, $sheared); // update NBT
        $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED, $sheared); // send client data
    }

    /**
     * Gets the color of the sheep
     *
     * @return int
     */
    public function getColor(): int {
        if (!isset($this->namedtag->Color)) {
            $this->namedtag->Color = new ByteTag(self::NBT_KEY_COLOR, self::getRandomColor());
        }
        return (int)$this->namedtag[self::NBT_KEY_COLOR];
    }

    /**
     * Set the color of the sheep
     *
     * @param int $color
     */
    public function setColor(int $color) {
        $this->namedtag->Color = new ByteTag(self::NBT_KEY_COLOR, $color);
        $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, $color);
    }

    /**
     * We need this function when sheep may be interested in gras floating around
     *
     * @param array $blocksAround
     * @return bool|mixed
     */
    public function isAnyBlockOfInterest(array $blocksAround) {
        if ($this->isSheared()) { // sheep has only interest in gras blocks around if sheared
            foreach ($blocksAround as $block) { // check all the given blocks
                if ($block instanceof Grass or $block instanceof TallGrass or strcmp($block->getName(), "Double Tallgrass") == 0) { // only grass blocks are eatable by sheeps
                    return $block;
                }
            }
        }
        return false;
    }

    /**
     * When a sheep is sheared, it tries to eat gras. This method signalizes, that the entity reached
     * a gras block or something that can be eaten.
     *
     * @param Block $block
     */
    protected function blockOfInterestReached($block) {
        $this->stayTime = 1000; // let this entity stay still
        // play eat grass animation but only when there are players near ...
        foreach ($this->getLevel()->getPlayers() as $player) { // don't know if this is the correct one :/
            if ($player->distance($this) <= 49) {
                $pk = new EntityEventPacket();
                $pk->eid = $this->getId();
                $pk->event = EntityEventPacket::EAT_GRASS_ANIMATION;
                $player->dataPacket($pk);
            }
        }
        // after the eat gras has been played, we reset the block through air
        if ($block->getId() == Block::GRASS or $block->getId() == Block::TALL_GRASS) { // grass blocks are replaced by dirt blocks ...
            $this->getLevel()->setBlock($block, new Dirt());
        } else {
            $this->getLevel()->setBlock($block, new Air());
        }
        // this sheep is not sheared anymore ... ;)
        $this->setSheared(false);
        // reset base target. otherwise the entity will not move anymore :D
        $this->setBaseTarget(null);
        $this->checkTarget(false); // find a new target to move to ...
    }

    /**
     * This method is called when a player is looking at this entity. This
     * method shows an interactive button or not
     *
     * @param Player $player the player to show a button eventually to
     */
    public function showButton(Player $player) {
        if ($player->getInventory() != null) { // sometimes, we get null on getInventory?! F**k
            if ($player->getInventory()->getItemInHand()->getId() === ItemIds::SHEARS && !$this->isSheared()) {
                InteractionHelper::displayButtonText(PureEntities::BUTTON_TEXT_SHEAR, $player);
                return;
            }
        }
        parent::showButton($player);
    }


}
