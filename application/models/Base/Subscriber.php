<?php

/**
 * Base_Subscriber
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $email
 * @property boolean $enabled
 * @property string $tagstr
 * @property Doctrine_Collection $ContentList
 * @property Doctrine_Collection $ContentAndSubscriber
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
abstract class Base_Subscriber extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('subscriber');
        $this->hasColumn('id', 'integer', 2, array(
             'primary' => true,
             'type' => 'integer',
             'unsigned' => true,
             'autoincrement' => true,
             'length' => '2',
             ));
        $this->hasColumn('email', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'unique' => true,
             'email' => true,
             'length' => '255',
             ));
        $this->hasColumn('enabled', 'boolean', null, array(
             'type' => 'boolean',
             'notnull' => true,
             'default' => true,
             ));
        $this->hasColumn('tagstr', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'default' => '',
             'length' => '255',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasMany('Content as ContentList', array(
             'refClass' => 'ContentAndSubscriber',
             'local' => 'subscriber_id',
             'foreign' => 'content_id'));

        $this->hasMany('ContentAndSubscriber', array(
             'local' => 'id',
             'foreign' => 'subscriber_id'));

        $taggable0 = new Doctrine_Template_Taggable();
        $searchable0 = new Doctrine_Template_Searchable(array(
             'fields' => 
             array(
              0 => 'email',
              1 => 'tagstr',
             ),
             ));
        $this->actAs($taggable0);
        $this->actAs($searchable0);
    }
}