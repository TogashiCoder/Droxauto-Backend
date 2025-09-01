<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    "api_path" => "api",
    "api_domain" => null,
    "export_path" => "api-focused.json",

    "info" => [
        "version" => env("API_VERSION", "1.0.0"),
        "description" => "Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management. This documentation includes only the 8 core APIs needed for basic system operation.",
    ],

    "ui" => [
        "title" => "DroxStock Daparto Focused API",
        "theme" => "light",
        "hide_try_it" => false,
        "hide_schemas" => false,
        "logo" => "",
        "try_it_credentials_policy" => "include",
        "layout" => "responsive",
    ],

    "servers" => null,
    "enum_descriptions_strategy" => "description",

    "extensions" => [
        \App\Scramble\SecurityExtension::class,
    ],

    "middleware" => [
        "web",
    ],
];