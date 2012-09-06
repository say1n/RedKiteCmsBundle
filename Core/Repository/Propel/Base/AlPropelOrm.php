<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Propel\Base;

use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Orm\OrmInterface;

/**
 *  Implements the OrmInterface for Propel Orm
 *
 *  @author alphalemon <webmaster@alphalemon.com>
 */
class AlPropelOrm implements OrmInterface
{
    protected static $connection = null;
    protected $affectedRecords = null;

    /**
     * Constructor
     *
     * @param \PropelPDO $connection
     */
    public function __construct(\PropelPDO $connection = null)
    {
        self::$connection = (null === $connection) ? \Propel::getConnection() : $connection;
    }

    /**
     * {@ inheritdoc}
     */
    public function setConnection($connection)
    {
        self::$connection = $connection;
    }

    /**
     * {@ inheritdoc}
     */
    public function getConnection()
    {
        return self::$connection;
    }

    /**
     * {@ inheritdoc}
     */
    public function startTransaction()
    {
        self::$connection->beginTransaction();
    }

    /**
     * {@ inheritdoc}
     */
    public function commit()
    {
        self::$connection->commit();
    }

    /**
     * {@ inheritdoc}
     */
    public function rollBack()
    {
        self::$connection->rollBack();
    }

    /**
     * {@ inheritdoc}
     */
    public function getAffectedRecords()
    {
        return $this->affectedRecords;
    }

    /**
     * {@ inheritdoc}
     */
    public function save(array $values, $modelObject = null)
    {
        try {
            if(null !== $modelObject) $this->modelObject = $modelObject;

            $this->startTransaction();
            $this->modelObject->fromArray($values);
            $this->affectedRecords = $this->modelObject->save();

            if ($this->affectedRecords == 0) {
                $success = ($this->modelObject->isModified()) ? false : null;
            } else {
                $success = true;
            }

            if (false !== $success) {
                $this->commit();
            } else {
                $this->rollBack();
            }

            return $success;
        } catch (\Exception $ex) {
            $this->rollBack();

            throw $ex;
        }
    }

    /**
     * {@ inheritdoc}
     */
    public function delete($modelObject = null)
    {
        try {
            $values = array('ToDelete' => 1);

            return $this->save($values, $modelObject);
        } catch (\Exception $ex) {

            throw $ex;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query)
    {
        $statement = self::$connection->prepare($query);

        return $statement->execute();
    }
}
