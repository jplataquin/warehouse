<?php

use App\Mcp\Servers\WarehouseServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', WarehouseServer::class)->middleware('auth:sanctum');
