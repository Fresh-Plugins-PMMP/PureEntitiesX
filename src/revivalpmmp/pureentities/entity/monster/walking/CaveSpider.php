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

namespace revivalpmmp\pureentities\entity\monster\walking;

use pocketmine\entity\Effect;
use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\utils\MobDamageCalculator;

class CaveSpider extends WalkingMonster {
    const NETWORK_ID = Data::CAVE_SPIDER;

    public $width = 1.438;
    public $height = 0.547;
    public $speed = 1.3;

    public function getSpeed(): float {
        return $this->speed;
    }

    public function initEntity() {
        parent::initEntity();
        $this->setDamage([0, 2, 3, 3]);
    }

    public function getName(): string {
        return "CaveSpider";
    }

    /**
     * Attack the player
     *
     * @param Entity $player
     */
    public function attackEntity(Entity $player) {
        if ($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32) {
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                MobDamageCalculator::calculateFinalDamage($player, $this->getDamage()));
            $player->attack($ev);
            Effect::getEffect(Effect::POISON)->applyEffect($player);
            $this->checkTamedMobsAttack($player);
        }
    }

    public function getDrops() : array{
        $drops = [];
        if ($this->isLootDropAllowed()) {
            array_push($drops, Item::get(Item::STRING, 0, mt_rand(0, 2)));
            switch (mt_rand(0, 2)) {
                case 0:
                    array_push($drops, Item::get(Item::SPIDER_EYE, 0, 1));
                    break;
            }
        }
        return $drops;
    }

    public function getMaxHealth() : int{
        return 12;
    }

    public function getKillExperience(): int {
        return 5;
    }


}
