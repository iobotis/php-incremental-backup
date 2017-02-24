<?php
/**
 * @author Ioannis Botis
 * @date 24/1/2017
 * @version: AbstractBase.php 11:00 pm
 * @since 24/1/2017
 */

namespace Backup\Destination;

abstract class AbstractBase implements Base
{
    use \Backup\Destination\Settings;

    public function __construct(array $settings)
    {
        $this->setSettings($settings);
    }
}