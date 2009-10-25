<?php

/**
 * BaseImage
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $width
 * @property integer $height
 * @property enum $type
 * @property Doctrine_Collection $Template
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6508 2009-10-14 06:28:49Z jwage $
 */
abstract class BaseImage extends File
{
    public function setTableDefinition()
    {
        parent::setTableDefinition();
        $this->setTableName('image');
        $this->hasColumn('width', 'integer', 2, array(
             'type' => 'integer',
             'unsigned' => true,
             'length' => '2',
             ));
        $this->hasColumn('height', 'integer', 2, array(
             'type' => 'integer',
             'unsigned' => true,
             'length' => '2',
             ));
        $this->hasColumn('type', 'enum', null, array(
             'type' => 'enum',
             'values' => 
             array(
              0 => 'avatar',
              1 => 'image',
             ),
             'default' => 'image',
             'notnull' => true,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasMany('Template', array(
             'local' => 'id',
             'foreign' => 'avatar_id'));
    }
}