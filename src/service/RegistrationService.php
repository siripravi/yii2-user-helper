<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\service;

use siripray\userhelper\events\RegistrationEvent;
use siripray\userhelper\helpers\PasswordGenerator;
use siripray\userhelper\mail\RegistrationEmail;
use siripray\userhelper\Mailer;
use siripray\userhelper\models\RegistrationForm;
use siripray\userhelper\models\User;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class RegistrationService extends Component
{
    const EVENT_BEFORE_REGISTER = 'beforeRegister';
    const EVENT_AFTER_REGISTER = 'afterRegister';

    /**
     * Whether registration is enabled.
     *
     * @var bool
     */
    public $isEnabled = true;

    /**
     * Whether password is generated automatically on registration.
     *
     * @var bool
     */
    public $isPasswordGeneratorEnabled = false;

    /**
     * @var ConfirmationService
     */
    private $_confirmationService;

    /**
     * @return ConfirmationService
     */
    public function getConfirmationService()
    {
        return $this->_confirmationService;
    }

    /**
     * @param ConfirmationService $confirmationService
     */
    public function setConfirmationService(ConfirmationService $confirmationService)
    {
        $this->_confirmationService = $confirmationService;
    }

    /**
     * @return PasswordGenerator|object
     */
    public function getPasswordService()
    {
        return \Yii::createObject(PasswordGenerator::class);
    }

    /**
     * @return Mailer|object
     */
    public function getMailer()
    {
        return \Yii::createObject(Mailer::class);
    }

    /**
     * RegistrationService constructor.
     * @param ConfirmationService $confirmationService
     * @param array $config
     */
    public function __construct(ConfirmationService $confirmationService, array $config = [])
    {
        $this->setConfirmationService($confirmationService);

        $this->on(self::EVENT_BEFORE_REGISTER, [$confirmationService, 'initializeConfirmationStatus']);
        $this->on(self::EVENT_AFTER_REGISTER, [$confirmationService, 'sendConfirmationMessage']);

        parent::__construct($config);
    }

    /**
     * Registers new user.
     * NOTE: Validation on user model will be executed.
     *
     * @param RegistrationForm $form Registration form model.
     */
    public function register(RegistrationForm $form)
    {
        /** @var User $user */
        $user = \Yii::createObject(User::class);
        $email = $this->getRegistrationEmail($user);

        $this->setUserEmail($user, $form->email);
        $this->setUserUsername($user, $form->username);
        $this->setUserPassword($user, $form->password);

        $this->trigger(self::EVENT_BEFORE_REGISTER, $this->getRegistrationEvent($user, $email));
        $user->save(false);
        $this->trigger(self::EVENT_AFTER_REGISTER, $this->getRegistrationEvent($user, $email));

        $this->setUserAttributes($user, $form);

        \Yii::$app->session->setFlash(
            'info',
            \Yii::t(
                'user',
                'Your account has been created and a message with further instructions has been sent to your email'
            )
        );

        $this->getMailer()->sendRegistrationMessage($email);
    }

    /**
     * Sets user's email address.
     *
     * @param User $user
     * @param string $email
     */
    protected function setUserEmail(User $user, $email)
    {
        if (!$email) {
            throw new \BadMethodCallException('Email must not be null');
        }
        $user->setEmail($email);
    }

    /**
     * Sets user's username.
     *
     * @param User $user
     * @param string $username
     */
    protected function setUserUsername(User $user, $username)
    {
        $user->setUsername($username);
    }

    /**
     * Sets user's password or generated new password if special option is enabled.
     *
     * @param User $user
     * @param string $password
     */
    protected function setUserPassword(User $user, $password)
    {
        if ($this->isPasswordGeneratorEnabled) {
            $user->setPassword($this->getPasswordService()->generate());
        } else {
            if (!$password) {
                throw new \BadMethodCallException('Password must not be null');
            }
            $user->setPassword($password);
        }
    }

    /**
     * Sets's user's attributes and attributes of it's relations.
     *
     * @param User    $user
     * @param RegistrationForm $form
     */
    protected function setUserAttributes(User $user, RegistrationForm $form)
    {
        $models = [];
        foreach ($form->getMappings() as $formKey => $userKey) {
            $pos = strrpos($userKey, '.');
            if ($pos === false) {
                $user->setAttribute($userKey, ArrayHelper::getValue($form, $formKey));
            } else {
                $relationName = substr($userKey, 0, $pos);
                $relationKey = substr($userKey, $pos + 1);
                $model = $user->getRelation($relationName)->one();
                if ($model) {
                    $model->setAttribute($relationKey, ArrayHelper::getValue($form, $formKey));
                    $models[] = $model;
                }
            }
        }

        $user->save(false);
        foreach ($models as $relation) {
            $relation->save(false);
        }
    }

    /**
     * @param  string $key
     * @return bool
     */
    protected function isUserModelMapping($key)
    {
        return strrpos($key, '.') === false;
    }

    /**
     * @param  User $user
     * @return RegistrationEmail
     */
    protected function getRegistrationEmail(User $user)
    {
        /** @var RegistrationEmail $email */
        $email = \Yii::createObject(RegistrationEmail::class, [$user]);
        $email->setIsPasswordShown($this->isPasswordGeneratorEnabled);

        return $email;
    }

    /**
     * @param  User     $user
     * @param  RegistrationEmail $email
     * @return object|RegistrationEvent
     */
    protected function getRegistrationEvent(User $user, RegistrationEmail $email)
    {
        return \Yii::createObject([
            'class' => RegistrationEvent::class,
            'user' => $user,
            'email' => $email,
        ]);
    }
}