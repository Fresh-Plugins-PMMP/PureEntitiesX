<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;

class Sheep extends WalkingAnimal implements Colorable{
    const NETWORK_ID = 13;

    public $width = 1.45;
    public $height = 1.12;

    public function getName(){
        return "Sheep";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(8);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            if($creature->getInventory()->getItemInHand()->getId() === Item::WHEAT) {
                return $creature->spawned && $creature->isAlive() && !$creature->closed && $distance <= 49;
            } elseif($creature->getInventory()->getItemInHand()->getId() === Item::SHEARS && $this instanceof Sheep && $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHEARED) === false) {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "Shear");
            } else {
                $creature->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, "");
            }
        }
        return false;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::WOOL, 0, 1)];
        }
        return [];
    }

}
