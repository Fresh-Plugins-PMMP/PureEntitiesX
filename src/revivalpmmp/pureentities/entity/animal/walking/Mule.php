<?php

namespace revivalpmmp\pureentities\entity\animal\walking;

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Mule extends WalkingAnimal implements Rideable{
    const NETWORK_ID = 25;

    public $width = 1.3;
    public $height = 1.4;

    public function getName(){
        return "Mule";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
        }
        return false;
}

    public function getDrops(){
        return [Item::get(Item::LEATHER, 0, mt_rand(0, 2))];
    }

    public function getMaxHealth() {
        return 15;
    }

}
