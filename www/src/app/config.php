<?php
    $container->set('config_bd', function () {
        return(object)[
            "host" => "db",
            "db" => "taller",
            "usr" => "root",
            "passw" => "12345",
            "charset" => "utf8mb4"
        ];
    });