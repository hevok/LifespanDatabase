<?php

/**
 * CommentTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class CommentTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object ObservationTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Comment');
    }

    /**
     * Gets pending comments
     * @return Doctrine_Collection pending Comment objects
     */
    public static function findPending()
    {
        $query = Doctrine_Query::create()->from('Comment c')
            ->where('c.status = ?', 'pending')
            ->orderBy('c.createdAt DESC');
        $items = $query->execute();
        return $items;
    }

    public static function findObservationComments($observationId, $status = null)
    {
        $query = Doctrine_Query::create()->from('Comment c')
            ->where('c.parentTable = ?', 'observation')
            ->andWhere('c.parentId = ?', $observationId)
            ->orderBy('c.createdAt DESC');
        if ($status !== null) {
            $query->andWhere('c.status = ?', $status);
        }
        
        $items = $query->execute(array(), Doctrine::HYDRATE_ARRAY);
        return $items;
    }

    public static function findGeneComments($geneId, $status = null)
    {
        $query = Doctrine_Query::create()->from('Comment c')
            ->where('c.parentTable = ?', 'gene')
            ->andWhere('c.parentId = ?', $geneId)
            ->orderBy('c.createdAt DESC');
        if ($status !== null) {
            $query->andWhere('c.status = ?', $status);
        }

        $items = $query->execute(array(), Doctrine::HYDRATE_ARRAY);
        return $items;
    }

    public static function getPendingCount()
    {
        $q = Doctrine_Query::create()
            ->select('COUNT(c.id) as count')
            ->from('Comment c')
            ->where('c.status = ?', 'pending');
        $row = $q->fetchOne(array(), Doctrine_Core::HYDRATE_ARRAY);
        return $row['count'];
    }
}