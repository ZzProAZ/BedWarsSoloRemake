<?php


declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu\session\network\handler;

use Closure;
use dctxdev\tw\libs\muqsit\invmenu\session\network\NetworkStackLatencyEntry;

final class ClosurePlayerNetworkHandler implements PlayerNetworkHandler{

	/** @var Closure */
	private $creator;

	/**
	 * @param Closure $creator
	 *
	 * @phpstan-param Closure(Closure) : NetworkStackLatencyEntry
	 */
	public function __construct(Closure $creator){
		$this->creator = $creator;
	}

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry{
		return ($this->creator)($then);
	}
}