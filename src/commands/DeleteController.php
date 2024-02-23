<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\commands;

use siripray\userhelper\models\User;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Deletes a user.
 *
 * @property \siripray\userhelper\Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class DeleteController extends Controller
{
    /**
     * Deletes a user.
     *
     * @param string $search Email or username
     */
    public function actionIndex($search)
    {
        if ($this->confirm(Yii::t('user', 'Are you sure? Deleted user can not be restored'))) {
            /** @var User $user */
            $user = \Yii::createObject(User::class);
            $user = $user::find()->byEmailOrUsername($search)->one();
            if ($user === null) {
                $this->stdout(Yii::t('user', 'User is not found') . "\n", Console::FG_RED);
            } else {
                if ($user->delete()) {
                    $this->stdout(Yii::t('user', 'User has been deleted') . "\n", Console::FG_GREEN);
                } else {
                    $this->stdout(Yii::t('user', 'Error occurred while deleting user') . "\n", Console::FG_RED);
                }
            }
        }
    }
}
