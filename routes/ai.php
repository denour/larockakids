<?php

use Laravel\Mcp\Facades\Mcp;

// Las tools registran entradas/salidas y mandan WhatsApp a tutores reales.
Mcp::web('/mcp/attendance', \App\Mcp\Servers\AttendanceServer::class)
    ->middleware('api.auth:attendance:write');
