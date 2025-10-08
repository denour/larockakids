<?php

use Laravel\Mcp\Facades\Mcp;


Mcp::web('/mcp/weather', \App\Mcp\Servers\AttendanceServer::class)
    ->middleware(['throttle:mcp']);

