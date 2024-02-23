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

use siripray\userhelper\service\ConfirmationService;
use siripray\userhelper\filters\AccessRule;
use siripray\userhelper\models\Profile;
use siripray\userhelper\models\User;
use siripray\userhelper\models\UserSearch;
use siripray\userhelper\Module;
use siripray\userhelper\traits\EventTrait;
use siripray\userhelper\traits\ServiceTrait;
use yii\base\ExitException;
use yii\base\Model;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * AdminController allows you to administrate users.
 *
 * @property Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com
 */
class AdminController extends Controller
{
    use EventTrait;
    use ServiceTrait;

    /**
     * Event is triggered before creating new user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_CREATE = 'beforeCreate';

    /**
     * Event is triggered after creating new user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_CREATE = 'afterCreate';

    /**
     * Event is triggered before updating existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';

    /**
     * Event is triggered after updating existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';

    /**
     * Event is triggered before updating existing user's profile.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_PROFILE_UPDATE = 'beforeProfileUpdate';

    /**
     * Event is triggered after updating existing user's profile.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_PROFILE_UPDATE = 'afterProfileUpdate';

    /**
     * Event is triggered before confirming existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_CONFIRM = 'beforeConfirm';

    /**
     * Event is triggered after confirming existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_CONFIRM = 'afterConfirm';

    /**
     * Event is triggered before approving existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_APPROVE = 'beforeApprove';

    /**
     * Event is triggered after approving existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_APPROVE = 'afterApprove';

    /**
     * Event is triggered before deleting existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * Event is triggered after deleting existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * Event is triggered before blocking existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_BLOCK = 'beforeBlock';

    /**
     * Event is triggered after blocking existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_BLOCK = 'afterBlock';

    /**
     * Event is triggered before unblocking existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_BEFORE_UNBLOCK = 'beforeUnblock';

    /**
     * Event is triggered after unblocking existing user.
     * Triggered with \siripray\userhelper\events\UserEvent.
     */
    const EVENT_AFTER_UNBLOCK = 'afterUnblock';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'  => ['post'],
                    'confirm' => ['post'],
                    'block'   => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'ruleConfig' => [
                    'class' => AccessRule::class,
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        Url::remember('', 'actions-redirect');
        $searchModel = \Yii::createObject(UserSearch::class);
        $dataProvider = $searchModel->search(\Yii::$app->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel'  => $searchModel,
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        /** @var User $user */
        $user = \Yii::createObject(['class' => User::class, 'scenario' => 'create']);

        $this->performAjaxValidation($user);
        $this->trigger(self::EVENT_BEFORE_CREATE, $this->getUserEvent($user));
        if ($user->load(\Yii::$app->request->post()) && $user->create()) {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been created'));
            $this->trigger(self::EVENT_AFTER_CREATE, $this->getUserEvent($user));
            return $this->redirect(['update', 'id' => $user->id]);
        }

        return $this->render('create', [
            'user' => $user,
        ]);
    }

    /**
     * Updates an existing User model.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionUpdate($id)
    {
        Url::remember('', 'actions-redirect');
        $user = $this->findModel($id);
        $user->scenario = 'update';

        $this->performAjaxValidation($user);

        $this->trigger(self::EVENT_BEFORE_UPDATE, $this->getUserEvent($user));
        if ($user->load(\Yii::$app->request->post()) && $user->save()) {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Account details have been updated'));
            $this->trigger(self::EVENT_AFTER_UPDATE, $this->getUserEvent($user));
            return $this->refresh();
        }

        return $this->render('_account', [
            'user' => $user,
        ]);
    }

    /**
     * Updates an existing profile.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionUpdateProfile($id)
    {
        Url::remember('', 'actions-redirect');
        $user  = $this->findModel($id);
        $profile = $user->profile;

        if ($profile == null) {
            $profile = \Yii::createObject(Profile::class);
            $profile->link('user', $user);
        }

        $this->performAjaxValidation($profile);
        $this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $this->getProfileEvent($profile));
        if ($profile->load(\Yii::$app->request->post()) && $profile->save()) {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Profile details have been updated'));
            $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $this->getProfileEvent($profile));
            return $this->refresh();
        }

        return $this->render('_profile', [
            'user'    => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Shows information about user.
     *
     * @param int $id
     *
     * @return string
     */
    public function actionInfo($id)
    {
        Url::remember('', 'actions-redirect');
        $user = $this->findModel($id);

        return $this->render('_info', [
            'user' => $user,
        ]);
    }

    /**
     * If "dektrium/yii2-rbac" extension is installed, this page displays form
     * where user can assign multiple auth items to user.
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionAssignments($id)
    {
        if (!isset(\Yii::$app->extensions['dektrium/yii2-rbac'])) {
            throw new NotFoundHttpException();
        }
        Url::remember('', 'actions-redirect');
        $user = $this->findModel($id);

        return $this->render('_assignments', [
            'user' => $user,
        ]);
    }

    /**
     * Confirms the User.
     *
     * @param int $id
     *
     * @return Response
     */
    public function actionConfirm($id)
    {
        $model = $this->findModel($id);

        $this->trigger(self::EVENT_BEFORE_CONFIRM, $this->getUserEvent($model));
        $this->getConfirmationService()->confirm($model);
        $this->trigger(self::EVENT_AFTER_CONFIRM, $this->getUserEvent($model));

        \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been confirmed'));

        return $this->redirect(Url::previous('actions-redirect'));
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        $this->trigger(self::EVENT_BEFORE_APPROVE, $this->getUserEvent($model));
        $this->getConfirmationService()->approve($model);
        $this->trigger(self::EVENT_AFTER_APPROVE, $this->getUserEvent($model));

        \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been approved'));

        return $this->redirect(Url::previous('actions-redirect'));
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionDelete($id)
    {
        if ($id == \Yii::$app->user->getId()) {
            \Yii::$app->getSession()->setFlash('danger', \Yii::t('user', 'You can not remove your own account'));
        } else {
            $model = $this->findModel($id);
            $this->trigger(self::EVENT_BEFORE_DELETE, $this->getUserEvent($model));
            $model->delete();
            $this->trigger(self::EVENT_AFTER_DELETE, $this->getUserEvent($model));
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been deleted'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Blocks the user.
     *
     * @param int $id
     *
     * @return Response
     */
    public function actionBlock($id)
    {
        if ($id == \Yii::$app->user->getId()) {
            \Yii::$app->getSession()->setFlash('danger', \Yii::t('user', 'You can not block your own account'));
        } else {
            $user = $this->findModel($id);
            if ($user->getIsBlocked()) {
                $this->trigger(self::EVENT_BEFORE_UNBLOCK, $this->getUserEvent($user));
                $user->unblock();
                $this->trigger(self::EVENT_AFTER_UNBLOCK, $this->getUserEvent($user));
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been unblocked'));
            } else {
                $this->trigger(self::EVENT_BEFORE_BLOCK, $this->getUserEvent($user));
                $user->block();
                $this->trigger(self::EVENT_AFTER_BLOCK, $this->getUserEvent($user));
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been blocked'));
            }
        }

        return $this->redirect(Url::previous('actions-redirect'));
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        /** @var User $user */
        $user = \Yii::createObject(User::class);
        $user = $user::findOne($id);

        if ($user instanceof User) {
            return $user;
        }

        throw new NotFoundHttpException(\Yii::t('user', 'The requested page does not exist'));
    }

    /**
     * Performs AJAX validation.
     *
     * @param array|Model $model
     *
     * @throws ExitException
     */
    protected function performAjaxValidation($model)
    {
        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            if ($model->load(\Yii::$app->request->post())) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                echo json_encode(ActiveForm::validate($model));
                \Yii::$app->end();
            }
        }
    }
}
