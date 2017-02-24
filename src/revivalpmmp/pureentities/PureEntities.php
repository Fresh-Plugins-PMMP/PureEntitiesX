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

namespace revivalpmmp\pureentities;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\BaseEntity;
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
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\task\AutoDespawnTask;
use revivalpmmp\pureentities\task\AutoSpawnTask;
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
use revivalpmmp\pureentities\task\InteractionTask;
use revivalpmmp\pureentities\tile\Spawner;

class PureEntities extends PluginBase implements CommandExecutor {

    /** @var  PureEntities $instance */
    private static $instance;

    /** @var string $loglevel */
    private static $loglevel; // please don't change back to int - makes no sense - string is more human readable

    // logging constants for method call 'logOutput'
    const NORM = 0;
    const WARN = 1;
    const DEBUG = 2;

    // button texts ...
    const BUTTON_TEXT_SHEAR = "Shear";
    const BUTTON_TEXT_FEED = "Feed";
    const BUTTON_TEXT_MILK = "Milk";
    const BUTTON_TEXT_TAME = "Tame";
    const BUTTON_TEXT_SIT = "Sit";
    const BUTTON_TEXT_DYE = "Dye";

    private static $registeredClasses = [];

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PureEntities the current instance of the plugin main class
     */
    public static function getInstance(): PureEntities {
        return PureEntities::$instance;
    }


    public function onLoad() {
        self::$registeredClasses = [
            Stray::class,
            Husk::class,
            Horse::class,
            Donkey::class,
            Mule::class,
            //ElderGuardian::class,
            //Guardian::class,
            //Bat::class,
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


        foreach (self::$registeredClasses as $name) {
            Entity::registerEntity($name);
            if (
                $name == IronGolem::class
                || $name == FireBall::class
                || $name == SnowGolem::class
                || $name == ZombieVillager::class
            ) {
                continue;
            }
            $item = Item::get(Item::SPAWN_EGG, $name::NETWORK_ID);
            if (!Item::isCreativeItem($item)) {
                Item::addCreativeItem($item);
            }
        }

        Tile::registerTile(Spawner::class);

        $this->checkConfig();

        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] The Original Code for this Plugin was Written by milk0417. It is now being maintained by RevivalPMMP for PMMP 'Unleashed'.");

        PureEntities::$loglevel = strtolower($this->getConfig()->getNested("logfile.loglevel", 0));
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Setting loglevel of logfile to " . PureEntities::$loglevel);

        Color::init();

        PureEntities::$instance = $this;
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoDespawnTask($this), $this->getConfig()->getNested("despawn-task.trigger-ticks", 1000));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask($this), $this->getConfig()->getNested("spawn-task.trigger-ticks", 1000));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new InteractionTask($this), $this->getConfig()->getNested("performance.check-interactive-ticks", 10));
        $this->getServer()->getLogger()->notice("[PureEntitiesX] Enabled!");
        $this->getServer()->getLogger()->notice("[PureEntitiesX] You're Running " . $this->getDescription()->getFullName());

        new PluginConfiguration($this); // create plugin configuration
    }

    /**
     * Checks if configuation is available. This function also checks if the config file available
     * is really filled - if not it will create a new config from the internal resource folder
     */
    private function checkConfig() {
        // check if a config file exists. if not - use the default config (from resources) and put it into the local config file
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveDefaultPEConfig();
        } else {
            // check for empty file ...
            if (filesize($this->getDataFolder() . "config.yml") == 0) {
                $this->saveDefaultPEConfig();
            }
        }
    }

    /**
     * Saves the default config found in resources to the disk for further usage!
     */
    private function saveDefaultPEConfig() {
        $filehandle = $this->getResource("config.yml");
        $content = stream_get_contents($filehandle);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . "[PureEntitiesX] Storing default config to " . $this->getDataFolder() . "config.yml");
        fclose($filehandle);

        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder(), 0777, true);
        }

        file_put_contents($this->getDataFolder() . "config.yml", $content);
    }

    public function onDisable() {
        $this->getServer()->getLogger()->notice("Disabled!");
    }

    /**
     * @param int|string $type
     * @param Position $source
     * @param $args
     *
     * @return Entity
     */
    public static function create($type, Position $source, ...$args) {
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
        if (PluginConfiguration::getInstance()->isRunningPmmp()) {
            return Entity::createEntity($type, $source->getLevel(), $nbt, ...$args);
        } else {
            $chunk = $source->getLevel()->getChunk($source->x >> 4, $source->z >> 4, true);
            if (!$chunk->isGenerated()) {
                $chunk->setGenerated();
            }
            if (!$chunk->isPopulated()) {
                $chunk->setPopulated();
            }
            return Entity::createEntity($type, $chunk, $nbt, ...$args);
        }
    }

    /**
     * @param Position $pos
     * @param int $entityid
     * @param Level $level
     * @param string $type
     * @param bool $baby
     * @param Entity $parentEntity
     * @param Player $owner
     *
     * @return boolean
     */
    public function scheduleCreatureSpawn(Position $pos, int $entityid, Level $level, string $type, bool $baby = false, Entity $parentEntity = null,
                                          Player $owner = null) {
        $this->getServer()->getPluginManager()->callEvent($event = new CreatureSpawnEvent($this, $pos, $entityid, $level, $type));
        if ($event->isCancelled()) {
            return false;
        } else {
            $entity = self::create($entityid, $pos);
            if ($entity !== null) {
                if ($entity instanceof IntfCanBreed and $baby and $entity->getBreedingExtension() !== false) {
                    $entity->getBreedingExtension()->setAge(-6000); // in 5 minutes it will be a an adult (atm only sheeps)
                    if ($parentEntity != null) {
                        $entity->getBreedingExtension()->setParent($parentEntity);
                    }
                }
                // new: a baby's parent (like a wolf) may belong to a player - if so, the baby is also owned by the player!
                if ($owner !== null && $entity instanceof IntfTameable) {
                    $entity->setTamed(true);
                    $entity->setOwner($owner);
                }
                PureEntities::logOutput("PureEntities: scheduleCreatureSpawn [type:$entity] [baby:$baby]", PureEntities::DEBUG);
                $entity->spawnToAll();
                return true;
            }
            self::logOutput("Cannot create entity [entityId:$entityid]", self::WARN);
            return false;
        }
    }

    /**
     * Logs an output to the plugin's logfile ...
     * @param string $logline the output to be appended
     * @param int $type the type of output to log
     * @return int|bool         returns false on failure
     */
    public static function logOutput(string $logline, int $type) {
        switch ($type) {
            case self::DEBUG:
                if (strcmp(self::$loglevel, "debug") == 0) {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[32m" . (date("j.n.Y G:i:s") . " [DEBUG] " . $logline . "\033[0m\r\n"), FILE_APPEND);
                }
                break;
            case self::WARN:
                file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[31m" . (date("j.n.Y G:i:s") . " [WARN]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
                break;
            case self::NORM:
                file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[37m" . (date("j.n.Y G:i:s") . " [INFO]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
                break;
            default:
                if (strcmp(self::$loglevel, "debug") == 0) {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[32m" . (date("j.n.Y G:i:s") . " [DEBUG] " . $logline . "\033[0m\r\n"), FILE_APPEND);
                } elseif (strcmp(self::$loglevel, "warn") == 0) {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[31m" . (date("j.n.Y G:i:s") . " [WARN]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
                } else {
                    file_put_contents('./pureentities_' . date("j.n.Y") . '.log', "\033[37m" . (date("j.n.Y G:i:s") . " [INFO]  " . $logline . "\033[0m\r\n"), FILE_APPEND);
                }
        }
        return true;
    }

    /**
     * Returns a suitable Y-position for spawning an entity, starting from the given coordinates.
     *
     * First, it's checked if the given position is AIR position. If so, we search down the y-coordinate
     * to get a first non-air block. When a non-air block is found the position returned is the last found air
     * position.
     *
     * When the given coordinates are NOT an AIR block coordinate we search upwards until the first air block is found
     * which is then returned to the caller.
     *
     * @param $x                int the x position to start search
     * @param $y                int the y position to start search
     * @param $z                int the z position to start searching
     * @param Level $level Level the level object to search in
     * @return null|Position    either NULL if no valid position was found or the final AIR spawn position
     */
    public static function getSuitableHeightPosition($x, $y, $z, Level $level) {
        $newPosition = null;
        $id = $level->getBlockIdAt($x, $y, $z);
        if ($id == 0) { // we found an air block - we need to search down step by step to get the correct block which is not a "AIR" block
            $air = true;
            $y = $y - 1;
            while ($air) {
                $id = $level->getBlockIdAt($x, $y, $z);
                if ($id != 0) { // this is an air block ...
                    $newPosition = new Position($x, $y + 1, $z, $level);
                    $air = false;
                } else {
                    $y = $y - 1;
                    if ($y < -255) {
                        break;
                    }
                }
            }
        } else { // something else than AIR block. search upwards for a valid air block
            $air = false;
            while (!$air) {
                $id = $level->getBlockIdAt($x, $y, $z);
                if ($id == 0) { // this is an air block ...
                    $newPosition = new Position($x, $y, $z, $level);
                    $air = true;
                } else {
                    $y = $y + 1;
                    if ($y > 255) {
                        break;
                    }
                }
            }
        }

        return $newPosition;
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch ($command->getName()) {
            case "peremove":
                $playerName = $sender->getName();
                $player = $this->getServer()->getPlayer($playerName);
                if ($player !== null and $player->isOnline()) {
                    foreach ($player->getLevel()->getEntities() as $entity) {
                        if ($entity instanceof BaseEntity) {
                            $entity->close();
                        }
                    }
                    $sender->sendMessage("Removed all entities (Animals/Monsters)");
                    return true;
                } else {
                    $sender->sendMessage("Can only be done ingame by a player with op status");
                }
                break;
            case "pesummon":
                if (count($args) == 1 or count($args) == 2) {
                    $playerName = count($args) == 1 ? $sender->getName() : $args[1];
                    foreach ($this->getServer()->getOnlinePlayers() as $player) {
                        if (strcasecmp($player->getName(), $playerName) == 0) {
                            // find a mob with the name issued
                            $mobName = strtolower($args[0]);
                            foreach (self::$registeredClasses as $registeredClass) {
                                if (strcmp($mobName, strtolower($this->getShortClassName($registeredClass))) == 0) {
                                    self::scheduleCreatureSpawn($player->getPosition(), $registeredClass::NETWORK_ID, $player->getLevel(), "Monster");
                                    $sender->sendMessage("Spawned $mobName");
                                    return true;
                                }
                            }
                            $sender->sendMessage("Entity not found: $mobName");
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage("Need a mob name!");
                    return true;
                }
                break;
            default:
                break;
        }
        return false;
    }

    /**
     * Returns the "short" name of a class without namespace ...
     *
     * @param string $longClassName
     * @return string
     */
    private function getShortClassName(string $longClassName): string {
        $short = "";
        $longClassName = strtok($longClassName, "\\");
        while ($longClassName !== false) {
            $short = $longClassName;
            $longClassName = strtok("\\");
        }
        return $short;
    }
}


