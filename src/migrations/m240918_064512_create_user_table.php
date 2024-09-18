<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Handles the creation of table `{{%user}}` `{{%profile}}`.
 */
class m240918_064512_create_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user_group}}', [
            'id'                => $this->primaryKey(),           
            'title'             => $this->string(255),
            'description'       => $this->text()
        ]);
        $this->createTable('{{%user}}', [
            'id'                   => $this->primaryKey(),
            'username'             => $this->string(25)->notNull(),
            'email'                => $this->string(255)->notNull(),
            'password_hash'        => $this->string(60)->notNull(),
            'auth_key'             => $this->string(32)->notNull(),
            'confirmed_at'         => $this->integer(11),
            'unconfirmed_email'    => $this->string(255),
            'blocked_at'           => $this->integer(11),
            'registration_ip'      => $this->string(32),
            'created_at'           => $this->integer(11)->defaultValue(0),
            'updated_at'           => $this->integer(11)->defaultValue(0),
            'approved_at'          => $this->integer(11),
            'flags'                => $this->integer(11),
            'user_group_id'        => $this->integer(11),
        ]);

        $this->createIndex('user_unique_username', '{{%user}}', 'username', true);
        $this->createIndex('user_unique_email', '{{%user}}', 'email', true);
        $this->addForeignKey('fk_user_group', '{{%user}}', 'user_group_id', '{{%user_group}}', 'id', 'CASCADE', 'RESTRICT');
        
        $this->createTable('{{%profile}}', [
            'id'             => $this->primaryKey(),
            'user_id'        => $this->integer(11)->notNull(),
            'name'           => $this->string(255),
            'surname'        => $this->string(45),
            'patronymic'     => $this->string(45),           
            'avatar'         => $this->string(255),
            'phone'          => $this->string(20),
            'info'           => $this->text()

        ]);

        $this->addForeignKey('fk_user_profile', '{{%profile}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
       
        $this->createTable('{{%user_address}}', [
            'id'                    => $this->primaryKey(),
            'user_profile_id'       => $this->integer(11)->notNull(),
            'country'               => $this->string(255),
            'region'                => $this->string(255),
            'city'                  => $this->string(255),           
            'street'                => $this->string(255),
            'house'                 => $this->string(20),
            'apartment'             => $this->text(),
            'zipcode'               => $this->integer(11),
            'postoffice'            => $this->integer(6),
            'contact_person'        => $this->string(255),
            'contact_mobile1'       => $this->string(25),
            'contact_mobile2'       => $this->string(25),
            'is_default'            => $this->tinyInteger(4)

        ]);

        $this->addForeignKey('fk_user_address_profile', '{{%user_address}}', 'user_profile_id', '{{%profile}}', 'id', 'CASCADE', 'RESTRICT');
        
        $this->createTable('{{%token}}', [
            'user_id'            => $this->primaryKey(),
            'code'          => $this->string(32),
            'created_at'    => $this->integer(11),
            'type'          => $this->smallInteger(32),           
           
        ]);
        $this->createIndex('token_unique_user_created_type', '{{%token}}', ['user_id','created_at','type'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%profile}}');
        $this->dropTable('{{%token}}');
        $this->dropTable('{{%user_address}}');
        $this->dropTable('{{%user}}');
    }
}
