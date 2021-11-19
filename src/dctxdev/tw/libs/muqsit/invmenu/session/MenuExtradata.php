<?php


declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu\session;

use pocketmine\math\Vector3;

class MenuExtradata{

	/** @var Vector3|null */
	protected $position;

	/** @var string|null */
	protected $name;

	public function getPosition() : ?Vector3{
		return $this->position;
	}

	public function getPositionNotNull() : Vector3{
		return $this->position;
	}

	public function getName() : ?string{
		return $this->name;
	}

	public function setPosition(?Vector3 $pos) : void{
		$this->position = $pos;
	}

	public function setName(?string $name) : void{
		$this->name = $name;
	}

	public function reset() : void{
		$this->position = null;
		$this->name = null;
	}
}