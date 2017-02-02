<?php

namespace revivalpmmp\pureentities\entity\animal\flying;

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

namespace revivalpmmp\pureentities\entity\animal\flying;

use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\entity\animal\FlyingAnimal;
use pocketmine\entity\Creature;

class Bat extends FlyingAnimal{

    const NETWORK_ID = 19;

    public $width = 0.3;
    public $height = 0.3;

    public function getName(){
        return "Bat";
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        return false;
    }

    public function getDrops(){
        return [];
    }

    public function getMaxHealth() {
        return 6;
    }

}
