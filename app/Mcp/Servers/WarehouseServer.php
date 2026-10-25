<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListAssetsTool;
use App\Mcp\Tools\ListLedgersTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListWarehousesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Warehouse Server')]
#[Version('1.0.0')]
#[Instructions('Read-only MCP Server for the Warehouse application to inspect ledgers, warehouses, projects, and assets.')]
class WarehouseServer extends Server
{
    protected array $tools = [
        ListLedgersTool::class,
        ListWarehousesTool::class,
        ListProjectsTool::class,
        ListAssetsTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
