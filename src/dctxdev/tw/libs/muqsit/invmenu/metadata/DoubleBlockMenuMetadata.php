<?php


declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu\metadata;

use dctxdev\tw\libs\muqsit\invmenu\session\MenuExtradata;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class DoubleBlockMenuMetadata extends SingleBlockMenuMetadata{

	protected function getBlockActorDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		$tag = parent::getBlockActorDataAt($pos, $name);
		$tag->setInt("pairx", $pos->x + (($pos->x & 1) ? 1 : -1));
		$tag->setInt("pairz", $pos->z);
		return $tag;
	}

	protected function getBlockPositions(MenuExtradata $metadata) : array{
		$pos = $metadata->getPositionNotNull();
		return $pos->y >= 0 && $pos->y < Level::Y_MAX ? [$pos, ($pos->x & 1) ? $pos->east() : $pos->west()] : [];
	}

	protected function calculateGraphicOffset(Player $player) : Vector3{
		$offset = parent::calculateGraphicOffset($player);
		$offset->x *= 2;
		$offset->z *= 2;
		return $offset;
	}
}