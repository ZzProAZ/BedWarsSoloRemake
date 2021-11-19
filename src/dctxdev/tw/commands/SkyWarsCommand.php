<?php

declare(strict_types=1);

namespace dctxdev\tw\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use dctxdev\tw\arena\Arena;
use dctxdev\tw\math\PlayNPC;
use dctxdev\tw\SkyWars;

/**
 * Class SkyWarsCommand
 * @package skywars\commands
 */
class SkyWarsCommand extends Command implements PluginIdentifiableCommand {

    /** @var SkyWars $plugin */
    protected $plugin;

    /**
     * SkyWarsCommand constructor.
     * @param SkyWars $plugin
     */
    public function __construct(SkyWars $plugin) {
        $this->plugin = $plugin;
        parent::__construct("bedwars", "BedWars Commands", \null, ["bw"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!isset($args[0])) {
                if(!$sender->hasPermission("sw.cmd.help")) {
                    $sender->sendMessage("§cUsage: §7/bw join:quit:random:stats");
                    return false;
                }
                $sender->sendMessage("§cUsage: §7/bw help");
            return;
        }
        if($sender instanceof ConsoleCommandSender){
            return false;
        }
        switch ($args[0]) {
            case "help":
                if(!$sender->hasPermission("sw.cmd.help")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                $sender->sendMessage("§a> BedWarsS Commands :\n" .
                    "§7/bw help : Displays list of BedWars commands\n".
                    "§7/bw create : Create BedWars arena\n".
                    "§7/bw remove : Remove BedWars arena\n".
                    "§7/bw set : Set BedWars arena\n".
                    "§7/bw arenas : Displays list of arenas\n".
                    "§7/bw bot : Add specter for bedwars\n".
                    "§7/bw join <arenaName> : join bedwars arena\n".
                    "§7/bw random : join random bedwars arena\n".
                    "§7/bw quit : quit bedwars arena");
                break;
            case "create":
                if(!$sender->hasPermission("sw.cmd.create")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bw create <arenaName>");
                    break;
                }
                if(isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] already exists!");
                    break;
                }
                $this->plugin->arenas[$args[1]] = new Arena($this->plugin, []);
                $sender->sendMessage("§a> Arena $args[1] created!");
                break;
            case "remove":
                if(!$sender->hasPermission("sw.cmd.remove")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bw remove <arenaName>");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] was not found!");
                    break;
                }

                /** @var Arena $arena */
                $arena = $this->plugin->arenas[$args[1]];

                foreach ($arena->players as $player) {
                    $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                }

                if(is_file($file = $this->plugin->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $args[1] . ".yml")) unlink($file);
                unset($this->plugin->arenas[$args[1]]);

                $sender->sendMessage("§a> Arena removed!");
                break;
            case "set":
                if(!$sender->hasPermission("sw.cmd.set")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§c> This command can be used only in-game!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bws set <arenaName>");
                    break;
                }
                if(isset($this->plugin->setters[$sender->getName()])) {
                    $sender->sendMessage("§c> You are already in setup mode!");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] does not found!");
                    break;
                }
                $sender->sendMessage("§a> You are joined setup mode.\n".
                    "§7- use §lhelp §r§7to display available commands\n"  .
                    "§7- or §ldone §r§7to leave setup mode");
                $this->plugin->setters[$sender->getName()] = $this->plugin->arenas[$args[1]];
                break;
            case "random":
                $this->plugin->joinToRandomArena($sender);
                break;
            case "join":
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bws join <arenaName>");
                    break;
                }

                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§cArena {$args[1]} not found.");
                    break;
                }

                $this->plugin->arenas[$args[1]]->joinToArena($sender);
                break;
            case "stats":
                $this->plugin->StatsForm($sender);
                break;
            case "bot":
                if(!$sender->hasPermission("sw.cmd.set")){
                    $sender->sendMessage(TextFormat::RED . "You dont have perm!");
                    break;
                }
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 1");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 2");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 3");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 4");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 5");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 6");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 7");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 8");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add df");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 9");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 10");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 12");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add 11");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s add test");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 8 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 1 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 2 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 3 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 4 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 5 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 6 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 7 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c df /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 9 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 10 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 11 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c 12 /bws random");
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "s c test /bws random");
            break;
            case "quit":
                if($sender instanceof Player){
                    Arena::$instance->disconnectPlayer($sender);
                    $sender->getInventory()->clearAll();
                    $sender->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                }
                break;
            case "arenas":
                if(!$sender->hasPermission("sw.cmd.arenas")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(count($this->plugin->arenas) === 0) {
                    $sender->sendMessage("§6> There are 0 arenas.");
                    break;
                }
                $list = "§7> Arenas:\n";
                foreach ($this->plugin->arenas as $name => $arena) {
                    if($arena->setup) {
                        $list .= "§7- $name : §cdisabled\n";
                    }
                    else {
                        $list .= "§7- $name : §aenabled\n";
                    }
                }
                $sender->sendMessage($list);
                break;
        }

    }

    /**
     * @return SkyWars|Plugin $plugin
     */
    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}
