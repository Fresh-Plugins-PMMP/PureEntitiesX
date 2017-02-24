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

use revivalpmmp\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use revivalpmmp\pureentities\features\BreedingExtension;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfCanInteract;

class Rabbit extends WalkingAnimal implements IntfCanBreed, IntfCanInteract {
    const NETWORK_ID = 18;

    public $width = 0.5;
    public $height = 0.5;

    private $feedableItems = array(
        Item::CARROT,
        Item::GOLDEN_CARROT,
        Item::DANDELION);

    /**
     * Is needed for breeding functionality
     *
     * @var BreedingExtension
     */
    private $breedableClass;

    public function initEntity() {
        parent::initEntity();
        $this->breedableClass = new BreedingExtension($this);
        $this->breedableClass->init();
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

    public function getSpeed(): float {
        return 1.2;
    }

    public function getName() {
        return "Rabbit";
    }

    public function getDrops() {
        $drops = [];

        array_push($drops, Item::get(Item::RABBIT_HIDE, 0, mt_rand(0, 1)));
        if ($this->isOnFire()) {
            array_push($drops, Item::get(Item::COOKED_RABBIT, 0, mt_rand(0, 1)));
        } else {
            array_push($drops, Item::get(Item::RAW_RABBIT, 0, mt_rand(0, 1)));
        }

        if (mt_rand(0, 100) <= 10) { // at 10 percent chance, rabbits drop rabbit's foot
            array_push($drops, Item::get(Item::RABBIT_FOOT, 0, 1));
        }

        return $drops;
    }

    public function getMaxHealth() {
        return 3;
    }

}
