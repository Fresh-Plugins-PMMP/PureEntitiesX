<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2016 RevivalPMMP

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

namespace revivalpmmp\pureentities;

use pocketmine\Player;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\monster\swimming\Guardian;
use revivalpmmp\pureentities\entity\monster\swimming\ElderGuardian;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\entity\animal\walking\Villager;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Chicken;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Pig;
use revivalpmmp\pureentities\entity\animal\walking\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\flying\Ghast;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\entity\monster\walking\Creeper;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\entity\monster\walking\Silverfish;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\entity\monster\walking\WitherSkeleton;
use revivalpmmp\pureentities\entity\monster\walking\SnowGolem;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\entity\monster\walking\ZombieVillager;
use revivalpmmp\pureentities\entity\monster\walking\Husk;
use revivalpmmp\pureentities\entity\monster\walking\Stray;
use revivalpmmp\pureentities\entity\projectile\FireBall;
use revivalpmmp\pureentities\event\EventListener;
use revivalpmmp\pureentities\tile\Spawner;
use revivalpmmp\pureentities\task\AutoSpawnMonsterTask;
use revivalpmmp\pureentities\task\AutoSpawnAnimalTask;
use revivalpmmp\pureentities\task\AutoDespawnTask;
use revivalpmmp\pureentities\event\CreatureSpawnEvent;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase {

    /** @var  PureEntities $instance */
    private static $instance;

    /** @var string $loglevel */
    private static $loglevel; // please don't change back to int - makes no sense - string is more human readable

    // logging constants for method call 'logOutput'
    const NORM = 0;
    const WARN = 1;
	const DEBUG = 2;

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PureEntities the current instance of the plugin main class
     */
    public static function getInstance() : PureEntities {
        return PureEntities::$instance;
    }

    public function onLoad(){
        $classes = [
            Stray::class,
            Husk::class,
            Horse::class,
            Donkey::class,
            Mule::class,
            //ElderGuardian::class,
            //Guardian::class,
            Squid::class,
            Villager::class,
            Blaze::class,
            CaveSpider::class,
            Chicken::class,
            Cow::class,
            Creeper::class,
            Enderman::class,
            Ghast::class,
            IronGolem::class,
            MagmaCube::class,
            Mooshroom::class,
            Ocelot::class,
            Pig::class,
            PigZombie::class,
            Rabbit::class,
            Sheep::class,
            Silverfish::class,
            Skeleton::class,
            WitherSkeleton::class,
            Slime::class,
            SnowGolem::class,
            Spider::class,
            Wolf::class,
            Zombie::class,
            ZombieVillager::class,
            FireBall::class
        ];
        foreach($classes as $name){
            Entity::registerEntity($name);
            if(
                $name == IronGolem::class
                || $name == FireBall::class
                || $name == SnowGolem::class
                || $name == ZombieVillager::class
            ){
                continue;
            }
            $item = Item::get(Item::SPAWN_EGG, $name::NETWORK_ID);
            if(!Item::isCreativeItem($item)){
                Item::addCreativeItem($item);
            }
        }

        Tile::registerTile(Spawner::class);
        
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] The Original Code for this Plugin was Written by milk0417. It is now being maintained by RevivalPMMP for PMMP 'Unleashed'.");

        PureEntities::$loglevel = strtolower($this->getConfig()->getNested("logfile.loglevel", 0));
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Setting loglevel of logfile to " . PureEntities::$loglevel);

        PureEntities::$instance = $this;
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnMonsterTask($this), $this->getServer()->getProperty("animal-spawns",100));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnAnimalTask($this), $this->getServer()->getProperty("monster-spawns",100));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoDespawnTask($this), 20);
	    $this->getServer()->getLogger()->notice("Enabled!");
	    $this->getServer()->getLogger()->notice("You're Running ".$this->getDescription()->getFullName());
    }

    public function onDisable(){
        $this->getServer()->getLogger()->notice("Disabled!");
    }

    /**
     * @param int|string $type
     * @param Position $source
     * @param $args
     * 
     * @return Entity
     */
    public static function create($type, Position $source, ...$args){
        $chunk = $source->getLevel()->getChunk($source->x >> 4, $source->z >> 4, true);
        if(!$chunk->isGenerated()){
            $chunk->setGenerated();
        }
        if(!$chunk->isPopulated()){
            $chunk->setPopulated();
        }

        $nbt = new CompoundTag("", [
            "Pos" => new ListTag("Pos", [
                new DoubleTag("", $source->x),
                new DoubleTag("", $source->y),
                new DoubleTag("", $source->z)
            ]),
            "Motion" => new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            "Rotation" => new ListTag("Rotation", [
                new FloatTag("", $source instanceof Location ? $source->yaw : 0),
                new FloatTag("", $source instanceof Location ? $source->pitch : 0)
            ]),
        ]);
        return Entity::createEntity($type, $chunk, $nbt, ...$args);
    }
    
    /**
     * @param Position $pos
     * @param int $entityid
     * @param Level $level
     * @param string $type
     * 
     * @return boolean
     */
    public function scheduleCreatureSpawn(Position $pos, int $entityid, Level $level, string $type) {
        $this->getServer()->getPluginManager()->callEvent($event = new CreatureSpawnEvent($this, $pos, $entityid, $level, $type));
        if($event->isCancelled()) {
            return false;
        } else {
            $entity = self::create($entityid, $pos);
            $entity->spawnToAll();
            return true;
        }
    }

    public function checkEntityCount(string $type, $water = false) : bool {
    	$i = 0;
    	foreach ($this->getServer()->getLevels() as $level) {
    		foreach ($level->getEntities() as $entity) {
    			if(!$entity instanceof Player) {
    				$i++;
			    }
		    }
	    }
	    if(strpos(strtolower($type),"animal")) {
    		if($water == true) {
			    if($i < $this->getServer()->getProperty("water-animals",5)) {
			        self::logOutput("checkEntityCount for water returns true",self::DEBUG);
				    return true;
			    }
		    }else{
			    if($i < $this->getServer()->getProperty("animals",70)) {
				    self::logOutput("checkEntityCount for animals returns true",self::DEBUG);
				    return true;
			    }
		    }
	    }else{
		    if($i < $this->getServer()->getProperty("monsters",70)) {
			    self::logOutput("checkEntityCount for monsters returns true",self::DEBUG);
			    return true;
		    }
	    }
	    self::logOutput("checkEntityCount returns false",self::DEBUG);
	    return false;
    }


	/**
	 * Logs an output to the plugin's logfile ...
	 * @param string $logline   the output to be appended
	 * @param int $type         the type of output to log
	 * @return int|bool         returns false on failure
	 */
	public static function logOutput(string $logline, int $type) {
		switch($type) {
			case self::DEBUG:
			    if (strcmp(self::$loglevel, "debug") == 0) {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[32m" . (date("j.n.Y G:i:s") . " [DEBUG] " . $logline . "\033[0m\r\n"), FILE_APPEND);
                }
				break;
			case self::WARN:
                file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[31m" . (date("j.n.Y G:i:s") . " [WARN]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
				break;
			case self::NORM:
			    if (strcmp(self::$loglevel, "info") == 0 or strcmp(self::$loglevel, "warn") == 0) {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[37m" . (date("j.n.Y G:i:s") . " [INFO]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
                }
				break;
			default:
				if(strcmp(self::$loglevel, "debug") == 0) {
					file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[32m" . (date("j.n.Y G:i:s") . " [DEBUG] " . $logline . "\033[0m\r\n"), FILE_APPEND);
				} elseif(strcmp(self::$loglevel, "warn") == 0) {
					file_put_contents('./pureentities_'.date("j.n.Y").'.log', "\033[31m".(date("j.n.Y G:i:s")." [WARN]  ".$logline."\033[0m\r\n"), FILE_APPEND);
				}else{
					file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[37m" . (date("j.n.Y G:i:s") . " [INFO]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
				}
		}
	}

    /**
     * Returns the first position of block of AIR found at above the given coordinates.
     *
     * Sometimes it seems that getHighestBlockAt is not working properly. So i introduced this additional
     * method.
     *
     * @param int $x        the x coordinate
     * @param int $y        the y coordinate (which is used in +1 until an AIR block is found)
     * @param int $z        the z coordinate
     * @param Level $level  the level to search in
     * @return Position     the Position of the first AIR block found above given coordinates
     */
    public static function getFirstAirAbovePosition ($x, $y, $z, Level $level) : Position {
        $air = false;
        $newPosition = null;
        while (!$air) {
            $id = $level->getBlockIdAt($x, $y, $z);
            if ($id == 0) { // this is an air block ...
                $newPosition = new Position($x, $y, $z, $level);
                $air = true;
            } else {
                $y = $y + 1;
            }
        }
        return $newPosition;
    }
}