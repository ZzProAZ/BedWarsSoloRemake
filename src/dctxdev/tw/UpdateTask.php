<?php

namespace dctxdev\tw;

use dctxdev\tw\SkyWars;
use pocketmine\scheduler\Task;

class UpdateTask extends Task {
	
	public function __construct(SkyWars $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
		$this->plugin->updateTopWin();
		$this->plugin->updateTopKills();
    }
	
}
