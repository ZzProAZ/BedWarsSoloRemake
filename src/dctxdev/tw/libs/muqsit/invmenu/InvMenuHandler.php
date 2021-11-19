<?php


declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu;

use InvalidArgumentException;
use dctxdev\tw\libs\muqsit\invmenu\metadata\DoubleBlockMenuMetadata;
use dctxdev\tw\libs\muqsit\invmenu\metadata\MenuMetadata;
use dctxdev\tw\libs\muqsit\invmenu\metadata\SingleBlockMenuMetadata;
use dctxdev\tw\libs\muqsit\invmenu\session\network\handler\PlayerNetworkHandlerRegistry;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\plugin\Plugin;
use pocketmine\tile\Tile;

final class InvMenuHandler{

	/** @var Plugin|null */
	private static $registrant;

	/** @var MenuMetadata[] */
	private static $menu_types = [];

	public static function getRegistrant() : ?Plugin{
		return self::$registrant;
	}

	public static function register(Plugin $plugin) : void{
		if(self::isRegistered()){
			throw new InvalidArgumentException("{$plugin->getName()} attempted to register " . self::class . " twice.");
		}

		self::$registrant = $plugin;
		self::registerDefaultMenuTypes();
		PlayerNetworkHandlerRegistry::init();
		$plugin->getServer()->getPluginManager()->registerEvents(new InvMenuEventHandler($plugin), $plugin);
	}

	public static function isRegistered() : bool{
		return self::$registrant instanceof Plugin;
	}

	private static function registerDefaultMenuTypes() : void{
		self::registerMenuType(new SingleBlockMenuMetadata(InvMenu::TYPE_CHEST, 27, WindowTypes::CONTAINER, BlockFactory::get(BlockIds::CHEST), Tile::CHEST));
		self::registerMenuType(new DoubleBlockMenuMetadata(InvMenu::TYPE_DOUBLE_CHEST, 54, WindowTypes::CONTAINER, BlockFactory::get(BlockIds::CHEST), Tile::CHEST));
		self::registerMenuType(new SingleBlockMenuMetadata(InvMenu::TYPE_HOPPER, 5, WindowTypes::HOPPER, BlockFactory::get(BlockIds::HOPPER_BLOCK), "Hopper"));
	}

	public static function registerMenuType(MenuMetadata $type, bool $override = false) : void{
		if(isset(self::$menu_types[$identifier = $type->getIdentifier()]) && !$override){
			throw new InvalidArgumentException("A menu type with the identifier \"{$identifier}\" is already registered as " . get_class(self::$menu_types[$identifier]));
		}

		self::$menu_types[$identifier] = $type;
	}

	public static function getMenuType(string $identifier) : ?MenuMetadata{
		return self::$menu_types[$identifier] ?? null;
	}
}