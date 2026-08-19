<?php

use App\Mcp\Servers\GymnamiteServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/gymnamite', GymnamiteServer::class)
    ->middleware(['web', 'auth']);
