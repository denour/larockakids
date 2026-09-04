<?php

use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/attendance', \App\Mcp\Servers\AttendanceServer::class)
    ->middleware('api.auth');
