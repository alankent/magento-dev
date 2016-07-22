<?php

namespace AlanKent\MagentoDev;

/**
 * Loads and saves the JSON configuration settings file to/from disk.
 */
class Config
{
    const CONFIG_FILE_NAME = '.magento-dev.json';

    /**
     * Load configuration settings from JSON file on disk into an association.
     * @return array Association of configuration settings.
     */
    public static function load()
    {
        if (file_exists(self::CONFIG_FILE_NAME)) {
            $config = json_decode(file_get_contents(self::CONFIG_FILE_NAME), true);
        } else {
            $config = array();
        }
        return $config;
    }

    /**
     * Save configuration settings to JSON file on disk.
     * @param array $config The settings to save in JSON format.
     */
    public static function save($config)
    {
        file_put_contents(self::CONFIG_FILE_NAME, json_encode($config, JSON_FORCE_OBJECT));
    }
}