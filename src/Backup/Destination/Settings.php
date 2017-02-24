<?php
/**
 * @author Ioannis Botis
 * @date 24/2/2017
 * @version: Settings.php 4:44 pm
 * @since 24/2/2017
 */

namespace Backup\Destination;


trait Settings
{
    private $settings;

    /**
     * Set settings array.
     *
     * @param array $settings
     */
    protected function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }
}