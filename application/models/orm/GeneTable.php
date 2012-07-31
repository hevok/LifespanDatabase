<?php

/**
 * GeneTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class GeneTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object GeneTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Gene');
    }

    public static function import($ncbiGeneId)
    {
        // import new gene information
        $data = Application_Model_Service_Ncbi::getGeneData($ncbiGeneId);
        if (!$data) {
            return null;
        }        

        // fetch taxonomy or import
        $ncbiTaxId = $data['taxon_id'];
        $taxonomy = Doctrine_Core::getTable('Taxonomy')->findOneBy('ncbiTaxId', $ncbiTaxId);
        if (!$taxonomy) {
            $taxonomy = Doctrine_Core::getTable('Taxonomy')->import($ncbiTaxId);
        }
        $taxonomyId = ($taxonomy != null) ? $taxonomy->id : null;
        $species = ($taxonomy != null) ? $taxonomy->name : null;

        $gene = new Gene();
        $gene->ncbiGeneId = $data['gene_id'];
        $gene->ncbiProteinId = $data['protein_id'];
        $gene->taxonomyId = $taxonomyId;
        $gene->species = $species;
        $gene->symbol = $data['symbol'];
        $gene->locusTag = $data['locus_tag'];
        $gene->description = $data['description'];
        $gene->save();

        // import supporting data
        self::_importGeneSynonyms($gene);
        self::_importGeneLinks($gene);
        self::_importGeneGos($gene);
        self::_importGeneHomologs($gene);

        return $gene;
    }

    private static function _importGeneSynonyms(Gene $gene)
    {
        // import gene synonyms
        $synonyms = Application_Model_Service_Ncbi::getGeneSynonyms($gene->ncbiGeneId);
        foreach ($synonyms as $synonym) {
            $geneSynonym = new GeneSynonym();
            $geneSynonym->geneId = $gene->id;
            $geneSynonym->synonym = $synonym;
            $geneSynonym->save();
        }
    }

    private static function _importGeneLinks(Gene $gene)
    {
        $dbxrefs = Application_Model_Service_Ncbi::getGeneDbxrefs($gene->ncbiGeneId);
        foreach ($dbxrefs as $dbxref) {
            $values = explode(':', $dbxref);
            if (count($values) < 2) {
                continue; // skip
            }

            $databaseId = $values[0];
            $identifier = $values[1];

            $geneLink = new GeneLink();
            $geneLink->geneId = $gene->id;
            $geneLink->databaseId = $databaseId;
            $geneLink->identifier = $identifier;
            $geneLink->save();
        }
    }

    private static function _importGeneGos(Gene $gene)
    {
        $rows = Application_Model_Service_Ncbi::getGeneGos($gene->ncbiGeneId);
        foreach ($rows as $row) {
            $geneGo = new GeneGo();
            $geneGo->geneId = $gene->id;
            $geneGo->goId = $row['go_id'];
            $geneGo->evidence = $row['evidence'];
            $geneGo->category = trim($row['category']);
            $geneGo->term = $row['term'];
            $geneGo->save();
        }
    }

     private static function _importGeneHomologs(Gene $gene)
     {
         $rows = Application_Model_Service_Ncbi::getGeneHomologs($gene->ncbiGeneId);
         foreach ($rows as $row) {
             $proteinId = $row['protein_id'];

             // look up ncbi gene id for ppod homolog
             $ncbiGeneIds = array();
             if ($row['database_id'] == 'NCBI') {
                 $ncbiGeneIds = Application_Model_Service_Ncbi::getGeneIdsBy('protein_acc', $proteinId);
             } elseif ($row['database_id'] == 'UniProtKB') {
                 $ncbiGeneIds = Application_Model_Service_Ncbi::getGeneIdsBy('uniprot_id', $proteinId);
             }

             foreach ($ncbiGeneIds as $ncbiGeneId) {
                 $geneHomolog = new GeneHomolog();
                 $geneHomolog->geneId = $gene->id;
                 $geneHomolog->ncbiGeneId = $gene->ncbiGeneId;
                 $geneHomolog->homologNcbiGeneId = $ncbiGeneId;
                 $geneHomolog->algorithm = $row['algorithm'];
                 $geneHomolog->family = $row['family_id'];
                 $geneHomolog->proteinDatabase = $row['database_id'];
                 $geneHomolog->proteinId = $row['protein_id'];

                 // check to see if homolog is in agingdb, if so, add its id
                 $homologGene = GeneTable::getInstance()->findOneBy('ncbiGeneId', $ncbiGeneId);
                 if (!$homologGene) {
                    $homologGeneData = Application_Model_Service_Ncbi::getGeneData($ncbiGeneId);
                    if ($homologGeneData) {
                        // uncomment line below to import gene information for
                        // all homologs recursively
                        //self::import($ncbiGeneId);

                        $geneHomolog->species = $homologGeneData['species'];
                        $geneHomolog->symbol = $homologGeneData['symbol'];
                    } else {
                        continue; // gene not in ncbi, skip saving
                    }
                 } else {
                     $geneHomolog->homologGeneId = $homologGene->id;
                     $geneHomolog->species = $homologGene->species;
                     $geneHomolog->symbol = $homologGene->symbol;
                 }
                 $geneHomolog->save();
             }
         }
     }

}