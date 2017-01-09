<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use pocketmine\block\Air;
use pocketmine\block\Grass;
use pocketmine\block\TallGrass;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Server;
use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;
use pocketmine\nbt\tag\ByteTag;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\PureEntities;

class Sheep extends WalkingAnimal {
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

    public function getName(){
        return "Sheep";
    }

    public static function getRandomColor() : int {
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

    public function initEntity(){
        parent::initEntity();

        $this->setColor($this->getColor());
        $this->setSheared ($this->isSheared());
    }

    public function targetOption(Creature $creature, float $distance) : bool {
        if($creature instanceof Player){
            if($creature->getInventory()->getItemInHand()->getId() === Item::WHEAT) {
                return $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= 49;
            } elseif($distance <= 4 and $creature->getInventory()->getItemInHand()->getId() === Item::SHEARS and self::NETWORK_ID === Data::SHEEP and $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHEARED) === false) {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "Shear");
            } else {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "");
            }
        }
        return false;
    }

    public function getDrops(){
        $drops = [];
        if ($this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHEARED) === false) {
            $drops = [Item::get(Item::WOOL, self::getColor(), mt_rand(0, 2))];
        }
        return $drops;
    }

    /**
     * The initEntity method of parent uses this function to get the max healthand set in NBT
     *
     * @return int
     */
    public function getMaxHealth(){
        return 8;
    }


    // ------------------------------------------------------------
    // very sheep specific functions
    // ------------------------------------------------------------
    /**
     * Checks if this entity is sheared
     * @return bool
     */
    public function isSheared () : bool {
        if (!isset($this->namedtag->Sheared)) {
            $this->namedtag->Sheared = new ByteTag(self::NBT_KEY_SHEARED, 0); // set not sheared
        }
        return (bool) $this->namedtag[self::NBT_KEY_SHEARED];
    }

    /**
     * Sets this entity sheared or not
     *
     * @param bool $sheared
     */
    public function setSheared (bool $sheared) {
        $this->namedtag->Sheared = new ByteTag(self::NBT_KEY_SHEARED, $sheared); // update NBT
        $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHEARED, $sheared); // send client data
    }

    /**
     * Gets the color of the sheep
     *
     * @return int
     */
    public function getColor() : int {
        if(!isset($this->namedtag->Color)){
            $this->namedtag->Color = new ByteTag(self::NBT_KEY_COLOR, self::getRandomColor());
        }
        return (int) $this->namedtag[self::NBT_KEY_COLOR];
    }

    /**
     * Set the color of the sheep
     *
     * @param int $color
     */
    public function setColor(int $color){
        $this->namedtag->Color = new ByteTag(self::NBT_KEY_COLOR, $color);
        $this->setDataProperty(self::DATA_COLOUR, self::DATA_TYPE_BYTE, $color);
    }

    /**
     * We need this function when sheep may be interested in gras floating around
     *
     * @param array $blocksAround
     * @return bool|mixed
     */
    public function isAnyBlockOfInterest (array $blocksAround) {
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
    protected function blockOfInterestReached ($block) {
        $this->stayTime = 1000; // let this entity stay still
        // play eat grass animation but only when there are players near ...
        foreach (Server::getInstance()->getOnlinePlayers() as $player) { // don't know if this is the correct one :/
            if ($player->distance($this) <= 49) {
                $pk = new EntityEventPacket();
                $pk->eid = $this->getId();
                $pk->event = EntityEventPacket::EAT_GRASS_ANIMATION;
                $player->dataPacket($pk);
            }
        }
        // after the eat gras has been played, we reset the block through air
        $this->getLevel()->setBlock($block, new Air());
        // this sheep is not sheared anymore ... ;)
        $this->setSheared(false);
        // reset base target. otherwise the entity will not move anymore :D
        $this->baseTarget = null;
    }

}
