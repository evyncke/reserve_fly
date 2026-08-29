<?php
// Simple MCP agent landing
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>RAPCS MCP Agent</title>
</head>
<body>
<h1>RAPCS MCP Agent</h1>
<p>API endpoint: <a href="api.php">api.php</a></p>
<p>Usage examples:</p>
<ul>
    <li><a href="api.php?resource=bookings">bookings</a></li>
    <li><a href="api.php?resource=users">users</a></li>
    <li><a href="api.php?resource=invoices">invoices</a></li>
    <li><a href="api.php?resource=folios">folios</a></li>
    <li><a href="api.php?resource=students">students</a></li>
</ul>
<p>Access requires authentication: the agent checks <code>$userIsMember != 0</code>.</p>
</body>
</html>
