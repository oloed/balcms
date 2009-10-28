<?php

/**
 * BasePermissionGroup
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $code
 * @property Doctrine_Collection $PermissionList
 * @property Doctrine_Collection $PermissionAndPermissionGroup
 * @property Doctrine_Collection $PermissionGroupAndUser
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6508 2009-10-14 06:28:49Z jwage $
 */
abstract class BasePermissionGroup extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('permission_group');
        $this->hasColumn('id', 'integer', 2, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             'unsigned' => true,
             'length' => '2',
             ));
        $this->hasColumn('code', 'string', 15, array(
             'type' => 'string',
             'notblank' => true,
             'unique' => true,
             'length' => '15',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasMany('Permission as PermissionList', array(
             'refClass' => 'PermissionAndPermissionGroup',
             'local' => 'permissiongroup_id',
             'foreign' => 'permission_id'));

        $this->hasMany('PermissionAndPermissionGroup', array(
             'local' => 'id',
             'foreign' => 'permissiongroup_id'));

        $this->hasMany('PermissionGroupAndUser', array(
             'local' => 'id',
             'foreign' => 'permissiongroup_id'));
    }
}