<?php
// File: backend/api/me.php

require_once __DIR__ . '/auth.php';

$payload = require_auth();

respond(200, ['user' => $payload]);
