<?php

declare(strict_types=1);

namespace dctxdev\tw;

use pocketmine\command\{Command, CommandSender};
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use dctxdev\tw\arena\Arena;
use dctxdev\tw\arena\MapReset;
use dctxdev\tw\commands\SkyWarsCommand;
use dctxdev\tw\math\{PlayNPC, Vector3, Generator, Dinamite as TNT, Bedbug, Golem, Fireball};
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use dctxdev\tw\libs\muqsit\invmenu\InvMenu;
use dctxdev\tw\provider\YamlDataProvider;
use pocketmine\tile\Tile;
use pocketmine\entity\{Skin, Entity};
use pocketmine\Player;
use pocketmine\Server;
use jojoe77777\FormAPI; 
use slapper\events\SlapperCreationEvent; 
use dctxdev\tw\libs\muqsit\invmenu\InvMenuHandler;

/**
 * Class SkyWars
 * @package skywars
 */
class SkyWars extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;
    
    public $config;
    
    public $lastDamager = [];
    public $lastTime = [];
    public $damaged = [];

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];
    
    public static $instance;

    public function onEnable() {
        self::$instance = $this;
        //$this->getServer()->broadcastMessage("first you need red 2nd blue 3nd yellow 3nd green");
        $this->saveResource("config.yml");
        $this->saveResource("diamond.png"); 
        $this->saveResource("emerald.png"); 
        $this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
        @mkdir($this->getDataFolder() . "pdata");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 12000);
        $this->getScheduler()->scheduleRepeatingTask(new LastDamageTask($this), 20);
        $this->emptyArenaChooser = new EmptyArenaChooser($this);
        $this->dataProvider = new YamlDataProvider($this);
        $this->getServer()->getCommandMap()->register("HixXBedWarsSolo", $this->commands[] = new SkyWarsCommand($this));
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        Entity::registerEntity(TNT::class, true);
        Entity::registerEntity(Generator::class, true);
        Entity::registerEntity(Bedbug::class, true);
        Entity::registerEntity(Golem::class, true);
        Entity::registerEntity(Fireball::class, true);
    }
    
    public static function instance(){
        return self::$instance;
    }

    public function getSkinFromFile($path){
        $img = imagecreatefrompng($path);
        $bytes = '';
        $l = (int) getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($img);
        return new Skin("Standard_CustomSlim", $bytes); 
    }

    public function onSlapperCreate(SlapperCreationEvent $ev) {
        $entity = $ev->getEntity();
        $line   = $entity->getNameTag();
        if ($line == "toptwwin") {
            $entity->namedtag->setString("toptwwin", "toptwwin");
            $this->updateTopWin();
            
        } else if ($line == "topfinalkills") {
            $entity->namedtag->setString("topfinalkills", "topfinalkills");
            $this->updateTopKills();
            
        } else if ($line == "topbwplayed") {
            $entity->namedtag->setString("topbwplayed", "topbwplayed");
            $this->updateTopPlayed();
        }
    }
    
    public function updateTopWin() {
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if (!empty($entity->namedtag->getString("toptwwin", ""))) {
                    $topwin = $entity->namedtag->getString("toptwwin", "");
                    if ($topwin == "toptwwin") {
                        $data = new Config($this->getDataFolder() . "pdata/wins.yml", Config::YAML);
                        $swallet = $data->getAll();
                        $c = count($swallet);
                        $txt = "";
                        $txt = "§l§aLEADERBOARD\n§eBEDWARSSOLO Wins\n";
                        arsort($swallet);
                        $i = 1;
                        foreach ($swallet as $name => $amount) {
                            
                            $txt .= "§a{$i}.§b§l{$name} §r§e- §c{$amount} §bwins\n";
                            if($i >= 10){
                                break;
                            }
                            ++$i;
                        }
                        $entity->setNameTag($txt);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
                    }
                }
            }
        }
    }
    
    public function updateTopKills() {
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if (!empty($entity->namedtag->getString("topfinalkills", ""))) {
                    $topkills = $entity->namedtag->getString("topfinalkills", "");
                    if ($topkills == "topfinalkills") {
                        $data = new Config($this->getDataFolder() . "pdata/finalkills.yml", Config::YAML);
                        $swallet = $data->getAll();
                        $c = count($swallet);
                        $txt = "";
                        $txt .= "§l§aLEADERBOARD\n§eBEDWARSSOLO Final Kills\n";
                        arsort($swallet);
                        $i = 1;
                        foreach ($swallet as $name => $amount) {
                            
                            $txt .= "§a{$i}.§b§l{$name} §r§e- §c{$amount} §bfinalkill\n";
                            if($i >= 10){
                                break;
                            }
                            ++$i;
                        }
                        $entity->setNameTag($txt);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
                    }
                }
            }
        }
    }
    
    public function updateTopPlayed() {
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if (!empty($entity->namedtag->getString("topbwplayed", ""))) {
                    $topkills = $entity->namedtag->getString("topbwplayed", "");
                    if ($topkills == "topbwplayed") {
                        $data = new Config($this->getDataFolder() . "pdata/played.yml", Config::YAML);
                        $swallet = $data->getAll();
                        $c = count($swallet);
                        $txt = "";
                        $txt .= "§l§aLEADERBOARD\n§eBEDWARSSOLO Most Played\n";
                        arsort($swallet);
                        $i = 1;
                        foreach ($swallet as $name => $amount) {
                            
                            $txt .= "§a{$i}.§b§l{$name} §r§e- §c{$amount} §bgame\n";
                            if($i >= 10){
                                break;
                            }
                            ++$i;
                        }
                        $entity->setNameTag($txt);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
                        $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
                    }
                }
            }
        }
    }
    
    public function onDisable() {
        $this->dataProvider->saveArenas();
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if (!empty($entity->namedtag->getString("toptwwin", ""))) {
                    $lines    = explode("\n", $entity->getNameTag());
                    $lines[0] = $entity->namedtag->getString("toptwwin", "");
                    $nametag  = implode("\n", $lines);
                    $entity->setNameTag($nametag);
                    
                } else if (!empty($entity->namedtag->getString("topfinalkills", ""))) {
                    $lines    = explode("\n", $entity->getNameTag());
                    $lines[0] = $entity->namedtag->getString("topfinalkills", "");
                    $nametag  = implode("\n", $lines);
                    $entity->setNameTag($nametag);
                    
                } else if (!empty($entity->namedtag->getString("topbwplayed", ""))) {
                    $lines    = explode("\n", $entity->getNameTag());
                    $lines[0] = $entity->namedtag->getString("topbwplayed", "");
                    $nametag  = implode("\n", $lines);
                    $entity->setNameTag($nametag);
                    
                }
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled(\true);
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage("§a> BedWarsSolo setup help (1/1):\n".
                "§4help : Displays list of available setup commands\n" .
                "§4slots : Updates arena slots\n".
                "§4level : Sets arena level\n".
                "§4lobby : Sets Lobby Spawn\n".
                "§4spawn : Sets arena spawns\n".
                "§4maxy : set the maxY to the game\n".
                "§4shop : spawn the shop\n".
                "§4upgrade : spawn the upgrade shop\n".
                "§4bed : add bed for player\n".
                "§4joinsign : Sets arena join sign\n".
                "§4savelevel : Saves the arena level\n".
                "§4enable : Enables the arena");
                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7slots <int: slots>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§a> Slots updated to $args[1]!");
                break;
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("§c> Level $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§a> Arena level updated to $args[1]!");
                $arena->data["level"] = $args[1];
                break;
            case "spawn":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7spawn <int: spawn>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§cType number!");
                    break;
                }
                if((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("§cThere are only {$arena->data["slots"]} slots!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3(floor($player->getX()) + 0.0, floor($player->getY()), floor($player->getZ()) + 0.0))->__toString();
                $player->sendMessage("§a> Spawn $args[1] set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                break;
            case "bed":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7spawn <string: spawn>");
                    break;
                }
                if(!in_array($args[1], ["red", "blue", "yellow", "green", "aqua", "white", "pink", "gray"])){
                    break;
                }
                $arena->data["treasure"]["{$args[1]}"] = (new Vector3(floor($player->getX()), floor($player->getY()), floor($player->getZ())))->__toString();
                $player->sendMessage("§a> Bed $args[1] set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                break; 
            case "lobby":
                $arena->data["lobby"] = (new Vector3(floor($player->getX()) + 0.0, floor($player->getY()), floor($player->getZ()) + 0.0))->__toString();
                $player->sendMessage("§a> Lobby set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                break;
            case "maxy":
                $arena->data["maxY"] = round($player->getY());
                $player->sendMessage("§a> maxY set to: " . round($player->getY()));
                break;
            case "shop":
                Server::getInstance()->dispatchCommand($player, "slapper spawn villager §l§eITEM SHOP{line}§r§aLEFT CLICK");
                break;
            case "upgrade":
                Server::getInstance()->dispatchCommand($player, "slapper spawn villager §l§eTEAM UPGRADE{line}§r§aLEFT CLICK");
                break;
            case "joinsign":
                $player->sendMessage("§a> Break block to set join sign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "savelevel":
                if(!$arena->level instanceof Level) {
                    $player->sendMessage("§c> Error when saving level: world not found.");
                    if($arena->setup) {
                        $player->sendMessage("§6§lERROR!§r§6 you cant savelevel if you dont enable the arena.");
                    }
                    break;
                }
                $arena->mapReset->saveMap($arena->level);
                $player->sendMessage("§a§lSuccess!§r§a Level saved!");
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }

                if(!$arena->enable(false)) {
                    $player->sendMessage("§c> Could not load arena, there are missing information!");
                    break;
                }

                if($this->getServer()->isLevelGenerated($arena->data["level"])) {
                    if(!$this->getServer()->isLevelLoaded($arena->data["level"]))
                        $this->getServer()->loadLevel($arena->data["level"]);
                    if(!$arena->mapReset instanceof MapReset)
                        $arena->mapReset = new MapReset($arena);
                    $arena->mapReset->saveMap($this->getServer()->getLevelByName($arena->data["level"]));
                }

                $arena->loadArena(false);
                $player->sendMessage("§a> Arena enabled!");
                break;
            case "done":
                $player->sendMessage("§a> You have successfully left setup mode!");
                unset($this->setters[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§6> You are in setup mode.\n".
                    "§7- use §lhelp §r§7to display available commands\n"  .
                    "§7- or §ldone §r§7to leave setup mode");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§a> Join sign updated!");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled(\true);
                    break;
            }
        }
    }
    
    public function joinToRandomArena(Player $player) {
        $arena = $this->emptyArenaChooser->getRandomArena();
        if(!is_null($arena)) {
            $arena->joinToArena($player);
            return;
        }
        $player->sendMessage("§ccould not find available match now. try again later");
    } 
    
    public function addFinalKill(Player $player){
        $name = $player->getName();
        $kills = new Config($this->getDataFolder() . "pdata/finalkills.yml", Config::YAML);
        $k = $kills->get($name);
        $kills->set($name, $k + 1);
        $kills->save();
    }
    
    public function getSWFinalKills($player){
        $kills = new Config($this->getDataFolder() . "pdata/finalkills.yml", Config::YAML);
        if($player instanceof Player){
            return $kills->get($player->getName());
        } else {
            $name = $player->getName();
            return $kills->get($name);
        }
    } 
    
    public function addKill(Player $player){
        $name = $player->getName();
        $kills = new Config($this->getDataFolder() . "pdata/kills.yml", Config::YAML);
        $k = $kills->get($name);
        $kills->set($name, $k + 1);
        $kills->save();
    }
    
    public function getSWKills($player){
        $kills = new Config($this->getDataFolder() . "pdata/kills.yml", Config::YAML);
        if($player instanceof Player){
            return $kills->get($player->getName());
        } else {
            $name = $player->getName();
            return $kills->get($name);
        }
    }
    
    public function addExp(Player $player, int $xp){
        $player->addXp($xp);
    }
    
    public function getSWExp($player){
        $exp = new Config($this->getDataFolder() . "pdata/exp.yml", Config::YAML);
        if($player instanceof Player){
            return $exp->get($player->getName());
        } else {
            $name = $player->getName();
            return $exp->get($name);
        }
    }
    
    public function getSWLevel($player){
        $level = new Config($this->getDataFolder() . "pdata/level.yml", Config::YAML);
        if($player instanceof Player){
            return $level->get($player->getName());
        } else {
            $name = $player->getName();
            return $level->get($name);
        }
    }
    
    public function setPlayerEffect(Player $player, int $type){
        $name = $player->getName();
        $cage = new Config($this->getDataFolder() . "pdata/effect.yml", Config::YAML);
        $c = $cage->get($name);
        $cage->set($name, $type);
        $cage->save();
    }
    
    public function getSWEffect($player){
        $cage = new Config($this->getDataFolder() . "pdata/effect.yml", Config::YAML);
        if($player instanceof Player){
            return $cage->get($player->getName());
        } else {
            $name = $player->getName();
            return $cage->get($name);
        }
    }
    
    public function setPlayerCage(Player $player, int $type){
        $name = $player->getName();
        $cage = new Config($this->getDataFolder() . "pdata/cage.yml", Config::YAML);
        $c = $cage->get($name);
        $cage->set($name, $type);
        $cage->save();
    }
    
    public function getSWCage($player){
        $cage = new Config($this->getDataFolder() . "pdata/cage.yml", Config::YAML);
        if($player instanceof Player){
            return $cage->get($player->getName());
        } else {
            $name = $player->getName();
            return $cage->get($name);
        }
    }
    
    public function setPlayerMsg(Player $player, int $type){
        $name = $player->getName();
        $cage = new Config($this->getDataFolder() . "pdata/msg.yml", Config::YAML);
        $c = $cage->get($name);
        $cage->set($name, $type);
        $cage->save();
    }
    
    public function getSWMsg($player){
        $cage = new Config($this->getDataFolder() . "pdata/msg.yml", Config::YAML);
        if($player instanceof Player){
            return $cage->get($player->getName());
        } else {
            $name = $player->getName();
            return $cage->get($name);
        }
    }
    
    public function addBroken(Player $player){
        $name = $player->getName();
        $wins = new Config($this->getDataFolder() . "pdata/broken.yml", Config::YAML);
        $w = $wins->get($name);
        $wins->set($name, $w + 1);
        $wins->save();
    } 
    
    public function getTreasureBroken($player){
        $wins = new Config($this->getDataFolder() . "pdata/broken.yml", Config::YAML);
        if($player instanceof Player){
            return $wins->get($player->getName());
        } else {
            $name = $player->getName();
            return $wins->get($name);
        } 
    }
    
    public function addWin(Player $player){
        $name = $player->getName();
        $wins = new Config($this->getDataFolder() . "pdata/wins.yml", Config::YAML);
        $w = $wins->get($name);
        $wins->set($name, $w + 1);
        $wins->save();
    }
    
    public function getSWWins($player){
        $wins = new Config($this->getDataFolder() . "pdata/wins.yml", Config::YAML);
        if($player instanceof Player){
            return $wins->get($player->getName());
        } else {
            $name = $player->getName();
            return $wins->get($name);
        }
    }
    
    public function addLose(Player $player){
        $name = $player->getName();
        $loses = new Config($this->getDataFolder() . "pdata/loses.yml", Config::YAML);
        $l = $loses->get($name);
        $loses->set($name, $l + 1);
        $loses->save();
    }
    
    public function getSWLoses($player){
        $loses = new Config($this->getDataFolder() . "pdata/loses.yml", Config::YAML);
        if($player instanceof Player){
            return $loses->get($player->getName());
        } else {
            $name = $player->getName();
            return $loses->get($name);
        }
    }
    
    public function addDeath(Player $player){
        $name = $player->getName();
        $deaths = new Config($this->getDataFolder() . "pdata/deaths.yml", Config::YAML);
        $d = $deaths->get($name);
        $deaths->set($name, $d + 1);
        $deaths->save();
    }
    
    public function getSWDeaths($player){
        $deaths = new Config($this->getDataFolder() . "pdata/deaths.yml", Config::YAML);
        if($player instanceof Player){
            return $deaths->get($player->getName());
        } else {
            $name = $player->getName();
            return $deaths->get($name);
        }
    }
    
    public function addPlayed(Player $player){
        $name = $player->getName();
        $played = new Config($this->getDataFolder() . "pdata/played.yml", Config::YAML);
        $p = $played->get($name);
        $played->set($name, $p + 1);
        $played->save();
    }
    
    public function getSWPlayed($player){
        $played = new Config($this->getDataFolder() . "pdata/played.yml", Config::YAML);
        if($player instanceof Player){
            return $played->get($player->getName());
        } else {
            $name = $player->getName();
            return $played->get($name);
        }
    }
    
    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $kills = new Config($this->getDataFolder() . "pdata/kills.yml", Config::YAML);
        $deaths = new Config($this->getDataFolder() . "pdata/deaths.yml", Config::YAML);
        $wins = new Config($this->getDataFolder() . "pdata/wins.yml", Config::YAML);
        $loses = new Config($this->getDataFolder() . "pdata/loses.yml", Config::YAML);
        $level = new Config($this->getDataFolder() . "pdata/level.yml", Config::YAML);
        $exp = new Config($this->getDataFolder() . "pdata/exp.yml", Config::YAML);
        $played = new Config($this->getDataFolder() . "pdata/played.yml", Config::YAML);
        $cage = new Config($this->getDataFolder() . "pdata/cage.yml", Config::YAML);
        $effect = new Config($this->getDataFolder() . "pdata/effect.yml", Config::YAML);
        $msg = new Config($this->getDataFolder() . "pdata/msg.yml", Config::YAML);
        $fk = new Config($this->getDataFolder() . "pdata/finalkills.yml", Config::YAML); 
        $broken = new Config($this->getDataFolder() . "pdata/broken.yml", Config::YAML);  
        if(!$broken->exists($name)){
            $broken->set($name, 0);
            $broken->save();
        } 
        if(!$kills->exists($name)){
            $kills->set($name, 0);
            $kills->save();
        }
        if(!$deaths->exists($name)){
            $deaths->set($name, 0);
            $deaths->save();
        }
        if(!$wins->exists($name)){
            $wins->set($name, 0);
            $wins->save();
        }
        if(!$loses->exists($name)){
            $loses->set($name, 0);
            $loses->save();
        }
        if(!$level->exists($name)){
            $level->set($name, 1);
            $level->save();
        }
        if(!$exp->exists($name)){
            $exp->set($name, 0);
            $exp->save();
        }
        if(!$played->exists($name)){
            $played->set($name, 0);
            $played->save();
        }
        if(!$cage->exists($name)){
            $cage->set($name, 0);
            $cage->save();
        }
        if(!$effect->exists($name)){
            $effect->set($name, 0);
            $effect->save();
        }
        if(!$msg->exists($name)){
            $msg->set($name, 0);
            $msg->save();
        }
        if(!$fk->exists($name)){
            $fk->set($name, 0);
            $fk->save();
        }
    }
    
    public function setCage(Player $player, $pos){
        $cage = $this->getSWCage($player);
        $x = $pos->x;
        $y = $pos->y;
        $z = $pos->z;
        $vc = new Vector3(round($x) + 0.5, $y, round($z) + 0.5);
        if($cage == 0){
            $player->getLevel()->setBlock($vc, Block::get(Block::COAL_ORE));
        }
        if($cage == 1){
            $player->getLevel()->setBlock($vc, Block::get(Block::IRON_ORE));
        }
        if($cage == 2){
            $player->getLevel()->setBlock($vc, Block::get(Block::GOLD_ORE));
        }
        if($cage == 3){
            $player->getLevel()->setBlock($vc, Block::get(Block::EMERALD_ORE));
        }
        if($cage == 4){
            $player->getLevel()->setBlock($vc, Block::get(Block::DIAMOND_ORE));
        }
        if($cage == 5){
            $player->getLevel()->setBlock($vc, Block::get(Block::NETHER_REACTOR));
        }
    }
    
    public function getCageInfo(int $cage, bool $what = true){
        if($what){
        if($cage == 0){
            return "no.perm";
        }
        if($cage == 1){
            return "iron.ped";
        }
        if($cage == 2){
            return "gold.ped";
        }
        if($cage == 3){
            return "emerald.ped";
        }
        if($cage == 4){
            return "diamond.ped";
        }
        if($cage == 5){
            return "core.ped";
        }
        } else {
        if($cage == 0){
            return "Default";
        }
        if($cage == 1){
            return "Iron";
        }
        if($cage == 2){
            return "Gold";
        }
        if($cage == 3){
            return "Emerald";
        }
        if($cage == 4){
            return "Diamond";
        }
        if($cage == 5){
            return "Core";
        }
        }
    }
    
    public function removeCage(Player $player){
        $player->getLevel()->setBlock(new Vector3($player->x, $player->y - 1, $player->z), Block::get(0));
    }
    
    public function LockerForm($player){
		$formapi = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $player, $data){
		    $result = $data;
		    if($result === null){
		        return true;
		    }
		    switch($result){
		        //case 0:
		            //$this->pedestialForm($player);
		       // break;
		        case 0:
		            $this->deathForm($player);
		        break;
		        case 1:
		            $this->MsgForm($player);
		        break;
		    }
		});
		$form->setTitle("Cosmetic -> BedWars");
		$cage = $this->getCageInfo($this->getSWCage($player), false);
		$effect = $this->getEffectInfo($this->getSWEffect($player), false); 
		$msg = $this->getMsgInfo($this->getSWMsg($player), false);
		//$form->addButton("§l§dTreasure\n§r§8{$cage}");
		$form->addButton("§bDeath Effect\n§r§8{$effect}");
		$form->addButton("§bKill Pharse\n§r§8{$msg}");
		$form->sendToPlayer($player);
	}
	
	public function pedestialForm($player){
		$formapi = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $player, $data){
		    $result = $data;
		    if($result === null){
		        return true;
		    }
		    $perm = $this->getCageInfo($result, true);
		    if($player->hasPermission($perm)){
		        $this->setPlayerCage($player, $result);
		    }
		});
		$cage = $this->getSWCage($player);
		$form->setTitle("§l§8BedWARS Treas");
		if($cage == 0){
		    $form->addButton("§l§dDefault\n§r§a» selected «", -1, "");
		} else {
		    $form->addButton("§l§9Default\n§r§8» tap to select «", -1, "");
		}
		// Iron
		if($player->hasPermission("iron.ped")){
		    if($cage == 1){
		        $form->addButton("§l§dIron\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Iron\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Gold
		if($player->hasPermission("gold.ped")){
		    if($cage == 2){
		        $form->addButton("§l§dGold\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Gold\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Emerald
		if($player->hasPermission("emerald.ped")){
		    if($cage == 3){
		        $form->addButton("§l§dEmerald\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Emerald\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Diamond
		if($player->hasPermission("diamond.ped")){
		    if($cage == 4){
		        $form->addButton("§l§dDiamond\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Diamond\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Core
		if($player->hasPermission("core.ped")){
		    if($cage == 5){
		        $form->addButton("§l§dCore\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Core\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Table
		$form->sendToPlayer($player);
	}
	
	public function death(Player $player){
        $cage = $this->getSWEffect($player);
        if($cage == 0){
        }
        if($cage == 1){
            $player->getLevel()->addParticle(new DestroyBlockParticle($player->asVector3(), Block::get(Block::IRON_BLOCK)));
        }
        if($cage == 2){
            $player->getLevel()->addParticle(new DestroyBlockParticle($player->asVector3(), Block::get(Block::GOLD_BLOCK)));
        }
        if($cage == 3){
            $player->getLevel()->addParticle(new DestroyBlockParticle($player->asVector3(), Block::get(Block::EMERALD_BLOCK)));
        }
        if($cage == 4){
            $player->getLevel()->addParticle(new DestroyBlockParticle($player->asVector3(), Block::get(Block::DIAMOND_BLOCK)));
        }
        if($cage == 5){
            $player->getLevel()->addParticle(new DestroyBlockParticle($player->asVector3(), Block::get(Block::REDSTONE_BLOCK)));
        }
        if($cage == 6){
            $this->addParticle($player, $player->asVector3(), "minecraft:knockback_roar_particle");
        }
    }
    
    public function getEffectInfo(int $cage, bool $what = true){
        if($what){
        if($cage == 0){
            return "no.perm";
        }
        if($cage == 1){
            return "iron.eff";
        }
        if($cage == 2){
            return "gold.eff";
        }
        if($cage == 3){
            return "emerald.eff";
        }
        if($cage == 4){
            return "diamond.eff";
        }
        if($cage == 5){
            return "blood.eff";
        }
        if($cage == 6){
            return "ghostsmoke.eff";
        }
        } else {
        if($cage == 0){
            return "Default";
        }
        if($cage == 1){
            return "Iron";
        }
        if($cage == 2){
            return "Gold";
        }
        if($cage == 3){
            return "Emerald";
        }
        if($cage == 4){
            return "Diamond";
        }
        if($cage == 5){
            return "Blood";
        }
        if($cage == 6){
            return "Ghost Smoke";
        }
        }
    }
    
    public function deathForm($player){
		$formapi = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $player, $data){
		    $result = $data;
		    if($result === null){
		        return true;
		    }
		    $perm = $this->getEffectInfo($result, true);
		    if($player->hasPermission($perm)){
		        $this->setPlayerEffect($player, $result);
		    }
		});
		$cage = $this->getSWEffect($player);
		$form->setTitle("§b Cosmetic -> Death Effect");
		if($cage == 0){
		    $form->addButton("§l§dDefault\n§r§a» selected «", -1, "");
		} else {
		    $form->addButton("§l§9Default\n§r§8» tap to select «", -1, "");
		}
		// Iron
		if($player->hasPermission("iron.eff")){
		    if($cage == 1){
		        $form->addButton("§l§dIron\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Iron\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Gold
		if($player->hasPermission("gold.eff")){
		    if($cage == 2){
		        $form->addButton("§l§dGold\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Gold\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Emerald
		if($player->hasPermission("emerald.eff")){
		    if($cage == 3){
		        $form->addButton("§l§dEmerald\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Emerald\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Diamond
		if($player->hasPermission("diamond.eff")){
		    if($cage == 4){
		        $form->addButton("§l§dDiamond\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Diamond\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Core
		if($player->hasPermission("blood.eff")){
		    if($cage == 5){
		        $form->addButton("§l§dBlood\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Blood\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		if($player->hasPermission("ghostsmoke.eff")){
		    if($cage == 6){
		        $form->addButton("§l§dGhost Smoke\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Ghost Smoke\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		$form->sendToPlayer($player);
    }
    
    public function addParticle(Player $player, $pos, string $particlename, array $players = []) : bool{
		if($players === []){
			$players = $player->getLevel()->getPlayers();
		}
		$pk = new SpawnParticleEffectPacket();
		$pk->position = $pos->asVector3();
		$pk->particleName = $particlename;
		$this->getServer()->broadcastPacket($players, $pk);
		return true;
	}
    
    public function getKillMsg(Player $damager, Player $victim){
        $cage = $this->getSWMsg($damager);
        if($cage == 0){
            return "§b{$victim->getNametag()}§r§b has been killed by §r{$damager->getNametag()}";
        }
    }
    
    public function getMsgInfo(int $cage, bool $what = true){
        if($what){
        if($cage == 0){
            return "no.perm";
        }
        if($cage == 1){
            return "nulled.msg";
        }
        if($cage == 2){
            return "smack.msg";
        }
        if($cage == 3){
            return "ended.msg";
        }
        if($cage == 4){
            return "ghost.msg";
        }
        if($cage == 5){
            return "grave.msg";
        }
        } else {
        if($cage == 0){
            return "Default";
        }
        if($cage == 1){
            return "Nulled";
        }
        if($cage == 2){
            return "Smacked Down";
        }
        if($cage == 3){
            return "Ended Game";
        }
        if($cage == 4){
            return "Turned To Ghost";
        }
        if($cage == 5){
            return "To Their Grave";
        }
        }
    }
    
    public function MsgForm($player){
		$formapi = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $player, $data){
		    $result = $data;
		    if($result === null){
		        return true;
		    }
		    $perm = $this->getMsgInfo($result, true);
		    if($player->hasPermission($perm)){
		        $this->setPlayerMsg($player, $result);
		    }
		});
		$cage = $this->getSWMsg($player);
		$form->setTitle("§l§8BEDWARS Kill Pharse");
		if($cage == 0){
		    $form->addButton("§l§dDefault\n§r§a» selected «", -1, "");
		} else {
		    $form->addButton("§l§9Default\n§r§8» tap to select «", -1, "");
		}
		// Iron
		if($player->hasPermission("nulled.msg")){
		    if($cage == 1){
		        $form->addButton("§l§dNulled\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Nulled\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Gold
		if($player->hasPermission("smack.msg")){
		    if($cage == 2){
		        $form->addButton("§l§dSmacked Down\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Smacked Down\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Emerald
		if($player->hasPermission("ended.msg")){
		    if($cage == 3){
		        $form->addButton("§l§dEnded Game\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Ended Game\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Diamond
		if($player->hasPermission("ghost.msg")){
		    if($cage == 4){
		        $form->addButton("§l§dTurned To Ghost\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9Turned To Ghost\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		// Core
		if($player->hasPermission("grave.msg")){
		    if($cage == 5){
		        $form->addButton("§l§dTo Their Grave\n§r§a» selected «", -1, "");
		    } else {
		        $form->addButton("§l§9To Their Grave\n§r§8» tap to select «", -1, "");
		    }
		} else {
		    $form->addButton("§l§8Locked", -1, "");
		}
		$form->sendToPlayer($player);
    }

    public function StatsForm(Player $player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function(Player $player, $data){
		$result = $data;
		if($result === null){
			return true;
			}
			switch($result){
			case 0:
			break;
			}
		});
		$level = $this->getSWLevel($player);
		$played = $this->getSWPlayed($player);
		$xp = $this->getSWExp($player);
		$kills = $this->getSWKills($player);
		$fk = $this->getSWFinalKills($player); 
		$wins = $this->getSWWins($player);
		$played = $this->getSWPlayed($player);
		$treasure = $this->getTreasureBroken($player);
		$deaths = $this->getSWDeaths($player);
		$loses = $this->getSWLoses($player);
		$form->setTitle("§l§a{$player->getName()}'s PROFILE");
		$form->setContent("§6USERNAME: §b{$player->getName()}\n\n§6KILLS: §b{$kills}\n\n§6FINAL KILLS: §b{$fk}\n\n§6DEATHS: §b{$deaths}\n\n§6WINS: §b{$wins}\n\n§6BEDS BROKEN: §b{$treasure}\n\n§6GAME PLAYED: §b{$played}\n\n§6LOSES: §b{$loses}\n\n");
		$form->addButton("sumbit");
		$form->sendToPlayer($player);
	}
}
