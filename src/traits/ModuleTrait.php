<?php


namespace siripravi\userhelper\traits;

use siripravi\userhelper\Module;

/**
 * Trait ModuleTrait
 * @property-read Module $module
 * @package siripravi\userhelper\traits
 */
trait ModuleTrait
{
    /**
     * @return Module
     */
    public function getModule()
    {
        return \Yii::$app->getModule('user');
    }
}
