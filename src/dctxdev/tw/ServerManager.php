<?php

declare(strict_types=1);

namespace dctxdev\tw;

use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\Player;
use pocketmine\utils\Binary;

/**
 * Class ServerManager
 * @package dctxdev\skywars
 */
class ServerManager {

    /**
     * @param Player $player
     * @param string $server
     */
    public static function transferPlayer(Player $player, string $server) {
        $player->removeAllEffects();

        $pk = new ScriptCustomEventPacket();
        $pk->eventName = "bungeecord:main";
        $pk->eventData = Binary::writeShort(strlen("Connect")) . "Connect" . Binary::writeShort(strlen($server)) . $server;
        $player->sendDataPacket($pk);
    }

}