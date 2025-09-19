<?php

namespace Katanaui;

use Symfony\Component\Yaml\Yaml;

class Katana
{
    public static function getComponents(): array
    {
        $yamlPath = __DIR__.'/../resources/views/components/katana/katana.yml';

        if (! file_exists($yamlPath)) {
            return [];
        }

        return Yaml::parseFile($yamlPath);
    }
}
