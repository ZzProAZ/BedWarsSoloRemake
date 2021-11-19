<?php


declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu\inventory;

use dctxdev\tw\libs\muqsit\invmenu\metadata\MenuMetadata;
use dctxdev\tw\libs\muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\Player;

class InvMenuInventory extends ContainerInventory{

	/** @var MenuMetadata */
	private $menu_metadata;

	public function __construct(MenuMetadata $menu_metadata){
		$this->menu_metadata = $menu_metadata;
		parent::__construct(new Position(), [], $menu_metadata->getSize());
	}

	public function moveTo(int $x, int $y, int $z) : void{
		$this->holder->setComponents($x, $y, $z);
	}

	final public function getMenuMetadata() : MenuMetadata{
		return $this->menu_metadata;
	}

	final public function getName() : string{
		// The value of this does not ALTER the title of the inventory.
		// Use InvMenu::setName() to set the inventory's name, or supply the
		// name parameter in InvMenu::send().
		return $this->menu_metadata->getIdentifier();
	}

	public function getDefaultSize() : int{
		return $this->menu_metadata->getSize();
	}

	public function getNetworkType() : int{
		return $this->menu_metadata->getWindowType();
	}

	public function onClose(Player $who) : void{
		if(isset($this->viewers[spl_object_hash($who)])){
			parent::onClose($who);
			$menu = PlayerManager::getNonNullable($who)->getCurrentMenu();
			if($menu !== null && $menu->getInventory() === $this){
				$menu->onClose($who);
			}
		}
	}
}