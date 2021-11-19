<?php

declare(strict_types=1);

namespace dctxdev\tw\libs\muqsit\invmenu\session\network\handler;

use Closure;
use dctxdev\tw\libs\muqsit\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}