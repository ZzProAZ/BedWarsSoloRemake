<?php


declare(strict_types=1);

namespace dctxdev\tw\arena;

use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\entity\{Effect, EffectInstance};
use pocketmine\level\sound\ClickSound;
use pocketmine\scheduler\Task;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use dctxdev\tw\math\Math;
use dctxdev\tw\math\Time;
use pocketmine\entity\object\ItemEntity;
use dctxdev\tw\math\Vector3;
use Scoreboards\Scoreboards;

/**
 * Class ArenaScheduler
 * @package skywars\arena
 */
class ArenaScheduler extends Task {

    /** @var Arena $plugin */
    protected $plugin;
    
    /** @var int $waitTime */
    public $waitTime = 30;
    public $kedip2 = 0;
    
    public $upgradeNext = 1;
    public $upgradeTime = 5 * 60;
    
    public $bedgone = 10 * 60;
    public $suddendeath = 10 * 60;
    public $gameover = 10 * 60;

    /** @var int $restartTime */
    public $kedip1 = 0;
    public $restartTime = 10;
    public $kedip = 0;
    public $kedip3 = 0;

    /** @var array $restartData */
    public $restartData = [];

    /** @var DragonTargetManger $dd */
    public $dd;

    /**
     * ArenaScheduler constructor.
     * @param Arena $plugin
     */
    public function __construct(Arena $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {

        $this->reloadSign();
        $text = ["§l§fB§eEDWARS", "§l§eB§fE§eDWARS", "§l§eBE§fD§eWARS", "§l§eBED§fW§eARS", "§l§eBEDW§fA§eRS", "§l§eBEDWA§fR§eS", "§l§eBEDWAR§fS", "§l§eBEDWARS", "§l§fBEDWARS", "§l§eBEDWARS", "§l§fBEDWARS"];
        if($this->plugin->setup) return;
         
        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= 8) {
                    if($this->waitTime > 0){
                          $this->waitTime--;
                              $this->kedip1++;

                    foreach($this->plugin->players as $player){
                    if(!isset($text[$this->kedip1])){
                  $this->kedip1 = 0;
                    }
                 
                  $api = Scoreboards::getInstance();
                                        
                    $api->new($player, 'starting',  $text[$this->kedip1]);
                        $date = date("d/m/Y");
                        $api->setLine($player, 1, TextFormat::GRAY . $date);
                     $api->setLine($player, 2, "                     ");
                     $api->setLine($player, 3, "Map: §a".$player->getLevel()->getFolderName());
                     $api->setLine($player, 4, "Players§7: §a" .  count($this->plugin->players) . "/{$this->plugin->data["slots"]}");
                     $api->setLine($player, 5, "           ");
                     $api->setLine($player, 6, "Starting in". " ". "§a". $this->waitTime);
                     $api->setLine($player, 7, "   ");
                     $api->setLine($player, 8, "Mode: §aSolo");
                     $api->setLine($player, 9, "    ");
                     $api->setLine($player, 10, "§eplay.urservernamedctx.com");
                  
                    }
                    }
                    if($this->waitTime == 1){
                    $this->plugin->startGame();
                    $this->plugin->broadcastMessage("§6Your chat has been changed to private team chat \n \nYou can shout message to all players type ? <message>");
                    foreach($this->plugin->players as $player){
                    $api = Scoreboards::getInstance();
                    $api->remove($player);
                    }
                    }
                } else {

                     $this->kedip++;
                    foreach($this->plugin->players as $player){
                  if(!isset($text[$this->kedip])){
                  $this->kedip = 0;
                    }
                  $api = Scoreboards::getInstance();
                  $date = date("d/m/Y");
                  $api->new($player, 'starting',  $text[$this->kedip]);
                     $api->setLine($player, 1, TextFormat::GRAY . $date);
                     $api->setLine($player, 2, "                     ");
                     $api->setLine($player, 3, "Map: §a".$player->getLevel()->getFolderName());
                     $api->setLine($player, 4, "Players§7: §a" .  count($this->plugin->players) . "/{$this->plugin->data["slots"]}");
                     $api->setLine($player, 5, "           ");
                     $api->setLine($player, 6, "Waiting for players...");
                     $api->setLine($player, 7, "   ");
                     $api->setLine($player, 8, "Mode: §aSolo");
                     $api->setLine($player, 9, "    ");
                     $api->setLine($player, 10, "§eplay.urservernamedctx.com");
                    $this->waitTime = 30;
                }
                }
                break;
            case Arena::PHASE_GAME:
                foreach($this->plugin->respawn as $r) {
                    if($this->plugin->respawnC[$r->getName()] <= 1) {
                        unset($this->plugin->respawn[$r->getName()]);
                        unset($this->plugin->respawnC[$r->getName()]);
                        $this->plugin->respawn($r);
                    } else {
                        $this->plugin->respawnC[$r->getName()]--;
                        $r->sendSubtitle("§eYou will respawn in §c{$this->plugin->respawnC[$r->getName()]}§e seconds!");
                    }
                }
                foreach($this->plugin->players as $milk){
                    if(isset($this->plugin->milk[$milk->getId()])){
                        if($this->plugin->milk[$milk->getId()] <= 0) {
                            unset($this->plugin->milk[$milk->getId()]);
                        } else {
                            $this->plugin->milk[$milk->getId()]--;
                        }
                    } 
                }
                $events = null;
                if($this->upgradeNext <= 4){
                    $this->upgradeTime--;
                    if($this->upgradeNext == 1){
                        $events = "Diamond II in: §a" . Time::calculateTime($this->upgradeTime) . "";
                    }
                    if($this->upgradeNext == 2){
                        $events = "Emerald II in: §a" . Time::calculateTime($this->upgradeTime) . "";
                    }
                    if($this->upgradeNext == 3){
                        $events = "Diamond III in: §a" . Time::calculateTime($this->upgradeTime) . "";
                    }
                    if($this->upgradeNext == 4){
                        $events = "Emerald III in: §a" . Time::calculateTime($this->upgradeTime) . "";
                    } 
                    if($this->upgradeTime == (0.0 * 60)){
                        $this->upgradeTime = 5 * 60;
                        $this->plugin->clearItem();
                        if($this->upgradeNext == 1){
                            $this->plugin->broadcastMessage("§bDiamond Generators §ehas been upgraded to Tier §cII");
                            $this->plugin->upgradeGeneratorTier("diamond", 2);
                         
                        }
                        if($this->upgradeNext == 2){
                            $this->plugin->broadcastMessage("§2Emerald Generators §ehas been upgraded to Tier §cII");
                            $this->plugin->upgradeGeneratorTier("emerald", 2); 
                             
                        }
                        if($this->upgradeNext == 3){
                            $this->plugin->broadcastMessage("§bDiamond Generators §ehas been upgraded to Tier §cIII");
                            $this->plugin->upgradeGeneratorTier("diamond", 3); 
                           
                        }
                        if($this->upgradeNext == 4){
                            $this->plugin->broadcastMessage("§2Emerald Generators §ehas been upgraded to Tier §cIII");
                            $this->plugin->upgradeGeneratorTier("emerald", 3); 
                           
                        }
                        $this->upgradeNext++;
                    }
                } else {
                    if($this->bedgone > (0.0 * 60)){
                        $this->bedgone--;
                        $events = "Bedgone in: §a" . Time::calculateTime($this->bedgone) . "";
                    } else {
                        if($this->suddendeath > (0.0 * 60)){
                            $this->suddendeath--;
                        }
                        $events = "Sudden Death in: §a" . Time::calculateTime($this->suddendeath) . "";
                    }
                    if($this->bedgone == (0.0 * 60)){
                        $this->plugin->destroyAllBeds();
                        $this->plugin->level->setTime(5000);
                        $this->plugin->clearItem(); 
                    } 
                    if($this->suddendeath <= (0.0 * 60)){
                        $this->gameover--;
                        foreach($this->plugin->players as $victim){
                            foreach(["red", "blue", "green", "yellow", "aqua", "white", "pink", "gray"] as $t){
                                $pos = Vector3::fromString($this->plugin->data["treasure"][$t]);
                                if($victim->distance($pos) < 15){
                                    $eff = new EffectInstance(Effect::getEffect(Effect::WITHER), 60, 1);
                                    $eff->setVisible(false);
                                    $victim->addEffect($eff);
                                    $victim->sendTitle("§l§eSuddenDeath", "§cthis area is too dangerous\ngo kill the enderdragons");
                                    if($victim instanceof Vector3){
                                        $this->dd = new DragonTargetManger($this->plugin, $victim->getViewers(), Math::calculateCenterPosition($victim->getPosition(), $victim->getPosition()));
                                        $this->dd->addDragon();
                                        $this->dd->addDragon();
                                    }
                                }
                            }
                        }
                        $events = "§bGame Time: §a" . Time::calculateTime($this->gameover) . "";
                        if($this->gameover == (0.0 * 60)){
                            $this->plugin->draw();
                        }
                    }
                }
                foreach($this->plugin->players as $pt){ 
                    $team = $this->plugin->getTeam($pt);
                    $pos = Vector3::fromString($this->plugin->data["treasure"][$team]);
                    if(isset($this->plugin->teamhaste[$team])){
                        if($this->plugin->getTeam($pt) == $team){
                            if($this->plugin->teamhaste[$team] > 1){
                                $eff = new EffectInstance(Effect::getEffect(Effect::HASTE), 60, ($this->plugin->teamhaste[$team] - 2));
                                $eff->setVisible(false);
                                $pt->addEffect($eff);
                            }
                        }
                    }
                    if(isset($this->plugin->teamhealth[$team])){
                        if($this->plugin->getTeam($pt) == $team){
                            if($this->plugin->teamhealth[$team] > 1){
                                if($pt->distance($pos) < 10){
                                    $eff = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 60, 0);
                                    $eff->setVisible(false);
                                    $pt->addEffect($eff);
                                }
                            }
                        }
                    }
                }

                     $this->kedip2++;
                foreach($this->plugin->players as $player){
                    $team = $this->plugin->getTeam($player); 
                    if(!$player->hasEffect(14)){
                        if(isset($this->invis[$player->getId()])){
                            $this->plugin->setInvis($player, false);
                        }
                    }
                    $player->setFood(20);
                    $color = [
                        "red" => "§cRed",
                        "blue" => "§9Blue",
                        "yellow" => "§eYellow",
                        "green" => "§aGreen",
                        "aqua" => "§bAqua",
                        "white" => "§fWhite",
                        "pink" => "§dPink",
                        "gray" => "§7Gray"
                    ];
                    $date = date("d/m/Y");
                    $r = $this->plugin->teamStatus("red");
                    $a = $this->plugin->teamStatus("blue"); 
                    $y = $this->plugin->teamStatus("yellow"); 
                    $l = $this->plugin->teamStatus("green");
                    $aqs = $this->plugin->teamStatus("aqua");
                    $w = $this->plugin->teamStatus("white");
                    $p = $this->plugin->teamStatus("pink");
                    $g = $this->plugin->teamStatus("gray");

                    if(!isset($text[$this->kedip2])){
                  $this->kedip2= 0;
                    }

                    $api = Scoreboards::getInstance();
                    $api->new($player, "BedWars", $text[$this->kedip2]);
                    $api->setLine($player, 1, TextFormat::GRAY . $date);
                    $api->setLine($player, 2, "         ");
                    $api->setLine($player, 3, "{$events}");
                    $api->setLine($player, 4, "§b§b ");
                    if(isset($this->plugin->redteam[$player->getName()])){
                        $api->setLine($player, 5, "§l§cR §rRed: §r{$r}§7YOU");
                    }
                    if(!isset($this->plugin->redteam[$player->getName()])){
                        $api->setLine($player, 5, "§l§cR §rRed: §r{$r}");
                    }
                    if(isset($this->plugin->blueteam[$player->getName()])){
                        $api->setLine($player, 6, "§l§bB §rBlue: §r{$a}§7YOU");
                    }
                    if(!isset($this->plugin->blueteam[$player->getName()])){
                        $api->setLine($player, 6, "§l§1B §rBlue: §r{$a}");
                    }
                    if(isset($this->plugin->yellowteam[$player->getName()])){
                        $api->setLine($player, 7, "§l§eY §rYellow: §r{$y}§7YOU");
                    }
                    if(!isset($this->plugin->yellowteam[$player->getName()])){
                        $api->setLine($player, 7, "§l§eY §rYellow: §r{$y}");
                    }
                    if(isset($this->plugin->greenteam[$player->getName()])){
                        $api->setLine($player, 8, "§l§aG §rGreen: §r{$l}§7YOU");
                    }
                    if(!isset($this->plugin->greenteam[$player->getName()])){
                        $api->setLine($player, 8, "§l§aG §rGreen: §r{$l}");
                    }
                    if(isset($this->plugin->aquateam[$player->getName()])){
                        $api->setLine($player, 9, "§l§bA §rAqua: §r{$aqs}§7YOU");
                    }
                    if(!isset($this->plugin->aquateam[$player->getName()])){
                        $api->setLine($player, 9, "§l§bA §rAqua: §r{$aqs}");
                    }
                    if(isset($this->plugin->whiteteam[$player->getName()])){
                        $api->setLine($player, 10, "§l§fW §rWhite: §r{$w}§7YOU");
                    }
                    if(!isset($this->plugin->whiteteam[$player->getName()])){
                        $api->setLine($player, 10, "§l§fW §rWhite: §r{$w}");
                    }
                    if(isset($this->plugin->pinkteam[$player->getName()])){
                        $api->setLine($player, 11, "§l§dP §rPink: §r{$p}§7YOU");
                    }
                    if(!isset($this->plugin->pinkteam[$player->getName()])){
                        $api->setLine($player, 11, "§l§dP §rPink: §r{$p}");
                    }
                    if(isset($this->plugin->grayteam[$player->getName()])){
                        $api->setLine($player, 12, "§l§7G §rGray: §r{$g}§7YOU");
                    }
                    if(!isset($this->plugin->grayteam[$player->getName()])){
                        $api->setLine($player, 12, "§l§7G §rGray: §r{$g}");
                    }
                    $api->setLine($player, 13, "  ");
                    $api->setLine($player, 14, "§eplay.urservernamedctx.com");
                    $api->getObjectiveName($player); 
                }
                $redcount = count($this->plugin->redteam);
                $aquacount = count($this->plugin->blueteam);
                $yellowcount = count($this->plugin->yellowteam);
                $limecount = count($this->plugin->greenteam);
                $bluecount = count($this->plugin->aquateam); //aqua team
                $whitecount = count($this->plugin->whiteteam);
                $pinkcount = count($this->plugin->pinkteam);
                $graycount = count($this->plugin->grayteam);
                if($redcount <= 0 && $aquacount <= 0 && $yellowcount <= 0 && $bluecount <= 0 && $whitecount <= 0 && $pinkcount <= 0 && $graycount <= 0){
                    $this->plugin->Wins("green");
                }
                if($limecount <= 0 && $aquacount <= 0 && $yellowcount <= 0  && $bluecount <= 0 && $whitecount <= 0 && $pinkcount <= 0 && $graycount <= 0){
                    $this->plugin->Wins("red");
                }
                if($redcount <= 0 && $aquacount <= 0 && $limecount <= 0 && $bluecount <= 0 && $whitecount <= 0 && $pinkcount <= 0 && $graycount <= 0){
                    $this->plugin->Wins("yellow");
                }
                if($redcount <= 0 && $limecount <= 0 && $yellowcount <= 0 && $bluecount <= 0 && $whitecount <= 0 && $pinkcount <= 0 && $graycount <= 0){
                    $this->plugin->Wins("blue");
                }
                if($whitecount <= 0 && $pinkcount <= 0 && $graycount <= 0 && $redcount <= 0 && $limecount <= 0 && $yellowcount <= 0){
                    $this->plugin->Wins("aqua");
                }
                if($bluecount <= 0 && $pinkcount <= 0 && $graycount <= 0 && $redcount <= 0 && $limecount <= 0 && $yellowcount <= 0){
                    $this->plugin->Wins("white");
                }
                if($whitecount <= 0 && $bluecount <= 0 && $graycount <= 0 && $redcount <= 0 && $limecount <= 0 && $yellowcount <= 0){
                    $this->plugin->Wins("pink");
                }
                if($bluecount <= 0 && $whitecount <= 0 && $pinkcount <= 0 && $redcount <= 0 && $limecount <= 0 && $yellowcount <= 0){
                    $this->plugin->Wins("gray");
                }
                break;
            case Arena::PHASE_RESTART:
              $this->kedip3++;

                $this->restartTime--;
                    foreach($this->plugin->players as $player){
                    $date = date("d/m/Y");
                    $r = $this->plugin->teamStatus("red");
                    $a = $this->plugin->teamStatus("blue"); 
                    $y = $this->plugin->teamStatus("yellow"); 
                    $l = $this->plugin->teamStatus("green");
                    $aqs = $this->plugin->teamStatus("aqua");
                    $w = $this->plugin->teamStatus("white");
                    $p = $this->plugin->teamStatus("pink");
                    $g = $this->plugin->teamStatus("gray");
               if(!isset($text[$this->kedip3])){
                  $this->kedip3 = 0;
                    }
                    $api = Scoreboards::getInstance();
                    $api->new($player, "BedWars", $text[$this->kedip3]);
                    $api->setLine($player, 1, TextFormat::GRAY . $date);
                    $api->setLine($player, 2, "         ");
                    $api->setLine($player, 3,  "§eRestarting§a ". $this->restartTime);
                    $api->setLine($player, 4, "§b§b§b ");
                    $api->setLine($player, 5, "§l§cR §rRed: §r{$r}");
                    $api->setLine($player, 6, "§l§bB §rBlue: §r{$a}");
                    $api->setLine($player, 7, "§l§eY §rYellow: §r{$y}");
                    $api->setLine($player, 8, "§l§aG §rGreen: §r{$l}");
                    $api->setLine($player, 9, "§l§bA §rAqua: §r{$aqs}");
                    $api->setLine($player, 10, "§l§fW §rWhite: §r{$w}");
                    $api->setLine($player, 11, "§l§dP §rPink: §r{$p}");
                    $api->setLine($player, 12, "§l§7G §rGray: §r{$g}");
                    $api->setLine($player, 13, "  ");
                    $api->setLine($player, 14, "§eplay.urservernamedctx.com");
                    $api->getObjectiveName($player);
                        $player->setNameTag($player->getDisplayName());
                        $player->setDisplayName($player->getDisplayName());
                        $player->getInventory()->clearAll();
                        $player->getArmorInventory()->clearAll();
                    Arena::$bossbar->removePlayer($player);
                }

                if($this->restartTime >= 0) {
                        foreach ($this->plugin->level->getPlayers() as $player){
                            $this->plugin->plugin->joinToRandomArena($player);
                          if(file_exists($this->plugin->plugin->getDataFolder(). "\pdata\finalkills.yml")){
                               if(unlink($this->plugin->plugin->getDataFolder(). "\pdata\finalkills.yml")){
                                   //wtf??
                               }
                             }
                        }
                        $this->plugin->loadArena(true);
                        $this->reloadTimer();
                }
                break;
        }
    }

    public function reloadSign() {
        if(!is_array($this->plugin->data["joinsign"]) || empty($this->plugin->data["joinsign"])) return;

        $signPos = Position::fromObject(Vector3::fromString($this->plugin->data["joinsign"][0]), $this->plugin->plugin->getServer()->getLevelByName($this->plugin->data["joinsign"][1]));

        if(!$signPos->getLevel() instanceof Level || is_null($this->plugin->level)) return;

        $signText = [
            "§e§lBedWarsSolo Dantedev",
            "§7[ §c? / ? §7]",
            "§cdisable",
            "§c"
        ];

        if($signPos->getLevel()->getTile($signPos) === null) return;

        if($this->plugin->setup || $this->plugin->level === null) {
            /** @var Sign $sign */
            $sign = $signPos->getLevel()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
            return;
        }

        $signText[1] = "§7[ §c" . count($this->plugin->players) . " / " . $this->plugin->data["slots"] . " §7]";

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= $this->plugin->data["slots"]) {
                    $signText[2] = "§6Full";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                }
                else {
                    $signText[2] = "§aJoin";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                }
                break;
            case Arena::PHASE_GAME:
                $signText[2] = "§5InGame";
                $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                break;
            case Arena::PHASE_RESTART:
                $signText[2] = "§cRestarting...";
                $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                break;
        }

        /** @var Sign $sign */
        $sign = $signPos->getLevel()->getTile($signPos);
        if($sign instanceof Sign) // Chest->setText() doesn't work :D
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
    }

    public function reloadTimer() {
        $this->waitTime = 30;
        $this->upgradeNext = 1;
        $this->upgradeTime = 5 * 60;
        $this->bedgone = 10 * 60;
        $this->suddendeath = 10 * 60;
        $this->gameover = 10 * 60; 
        $this->restartTime = 10;
    }
}
