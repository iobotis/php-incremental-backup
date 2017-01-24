<?php
/**
 * @author Ioannis Botis
 * @date 24/1/2017
 * @version: AbstractBase.php 11:00 pm
 * @since 24/1/2017
 */

namespace Backup\Destination;

abstract class AbstractBase implements \Backup\Destination\Base
{

    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings()
    {
        return $this->settings;
    }
}