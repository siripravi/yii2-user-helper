<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\controllers;

use siripray\userhelper\service\exceptions\ServiceException;
use siripray\userhelper\service\RegistrationService;
use siripray\userhelper\service\ConfirmationService;
use siripray\userhelper\models\Account;
use siripray\userhelper\models\RegistrationForm;
use siripray\userhelper\models\ResendForm;
use siripray\userhelper\models\User;
use siripray\userhelper\traits\AjaxValidationTrait;
use siripray\userhelper\traits\EventTrait;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * RegistrationController is responsible for all registration process, which includes registration of a new account,
 * resending confirmation tokens, email confirmation and registration via social networks.
 *
 * @property \siripray\userhelper\Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class RegistrationController extends Controller
{
    use AjaxValidationTrait;
    use EventTrait;

    /**
     * Event is triggered after creating RegistrationForm class.
     * Triggered with \siripray\userhelper\events\FormEvent.
     */
    const EVENT_BEFORE_REGISTER = 'beforeRegister';

    /**
     * Event is triggered after successful registration.
     * Triggered with \siripray\userhelper\events\FormEvent.
     */
    const EVENT_AFTER_REGISTER = 'afterRegister';

    /**
     * Event is triggered before connecting user to social account.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_CONNECT = 'beforeConnect';

    /**
     * Event is triggered after connecting user to social account.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_CONNECT = 'afterConnect';

    /**
     * Event is triggered before confirming user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_CONFIRM = 'beforeConfirm';

    /**
     * Event is triggered before confirming user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_CONFIRM = 'afterConfirm';

    /**
     * Event is triggered after creating ResendForm class.
     * Triggered with \siripray\userhelper\events\FormEvent.
     */
    const EVENT_BEFORE_RESEND = 'beforeResend';

    /**
     * Event is triggered after successful resending of confirmation email.
     * Triggered with \siripray\userhelper\events\FormEvent.
     */
    const EVENT_AFTER_RESEND = 'afterResend';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['register', 'connect'], 'roles' => ['?']],
                    ['allow' => true, 'actions' => ['confirm', 'resend'], 'roles' => ['?', '@']],
                ],
            ],
        ];
    }

    /**
     * Displays the registration page.
     * After successful registration redirects to login page.
     *
     * @return string
     * @throws \yii\web\HttpException
     */
    public function actionRegister()
    {
        /** @var RegistrationForm $model */
        $model = \Yii::createObject(RegistrationForm::class, [$this->createRegistrationService()]);

        $this->performAjaxValidation($model);

        $this->trigger(self::EVENT_BEFORE_REGISTER, $this->getFormEvent($model));
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->getRegistrationService()->register($model);
            $this->trigger(self::EVENT_AFTER_REGISTER, $this->getFormEvent($model));
            return $this->redirect(['/user/security/login']);
        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    /**
     * Displays page where user can create new account that will be connected to social account.
     *
     * @param string $code
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionConnect($code)
    {
        /** @var Account $account */
        $account = \Yii::createObject(Account::class);
        $account = $account::find()->byCode($code)->one();

        if ($account === null || $account->getIsConnected()) {
            throw new NotFoundHttpException();
        }

        /** @var User $user */
        $user = \Yii::createObject([
            'class'    => User::class,
            'scenario' => 'connect',
            'username' => $account->username,
            'email'    => $account->email,
        ]);

        $event = $this->getConnectEvent($account, $user);

        $this->trigger(self::EVENT_BEFORE_CONNECT, $event);

        if ($user->load(\Yii::$app->request->post()) && $user->create()) {
            $account->connect($user);
            $this->trigger(self::EVENT_AFTER_CONNECT, $event);
            \Yii::$app->user->login($user, $this->module->rememberFor);
            return $this->goBack();
        }

        return $this->render('connect', [
            'model'   => $user,
            'account' => $account,
        ]);
    }

    /**
     * Attempts confirmation by code.
     *
     * @param int    $id
     * @param string $code
     *
     * @return string
     * @throws \yii\web\HttpException
     */
    public function actionConfirm($id, $code)
    {
        /** @var User $user */
        $user = \Yii::createObject(User::class);
        $user = $user::findOne($id);
        $domain = $this->createConfirmationService();

        $this->trigger(self::EVENT_BEFORE_CONFIRM, $this->getUserEvent($user));
        try {
            $domain->attemptConfirmation($user, $code);
        } catch (ServiceException $e) {
            \Yii::error($e);
            return $this->redirect(['/user/security/login']);
        }
        $this->trigger(self::EVENT_AFTER_CONFIRM, $this->getUserEvent($user));

        return \Yii::$app->user->getIsGuest()
            ? $this->redirect(['/user/security/login'])
            : $this->goHome();
    }

    /**
     * Displays page where user can request new confirmation token. If resending was successful, displays message.
     *
     * @return string
     * @throws \yii\web\HttpException
     */
    public function actionResend()
    {
        /** @var ResendForm $model */
        $model = \Yii::createObject(ResendForm::class);
        $domain = $this->createConfirmationService();

        $this->performAjaxValidation($model);

        $this->trigger(self::EVENT_BEFORE_RESEND, $this->getFormEvent($model));
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            try {
                $domain->resendConfirmationMessage($model->getUser());
                $this->trigger(self::EVENT_AFTER_RESEND, $this->getFormEvent($model));
            } catch (ServiceException $e) {
                \Yii::error($e);
            }
            return $this->redirect(['/user/security/login']);
        }

        return $this->render('resend', [
            'model' => $model,
        ]);
    }

    /**
     * @return RegistrationService
     * @throws NotFoundHttpException
     */
    protected function createRegistrationService()
    {
        /** @var RegistrationService $service */
        $service = \Yii::createObject(RegistrationService::class);
        if (!$service->isEnabled) {
            throw new NotFoundHttpException('Page not found');
        }
        return $service;
    }

    /**
     * @return ConfirmationService|object
     * @throws NotFoundHttpException
     */
    protected function createConfirmationService()
    {
        /** @var ConfirmationService $service */
        $service = \Yii::createObject(ConfirmationService::class);
        if (!$service->isEnabled || !$service->isEmailConfirmationEnabled) {
            throw new NotFoundHttpException('Page not found');
        }
        return $service;
    }
}
