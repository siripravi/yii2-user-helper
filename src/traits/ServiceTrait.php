<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\traits;

use siripray\userhelper\service\ConfirmationService;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
trait ServiceTrait
{
    /**
     * @var ConfirmationService
     */
    protected $confirmationService;

    /**
     * @return ConfirmationService|object
     */
    protected function getConfirmationService()
    {
        if (!$this->confirmationService) {
            $this->confirmationService = \Yii::createObject(ConfirmationService::class);
        }

        return $this->confirmationService;
    }
}