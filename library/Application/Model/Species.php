<?php

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity 
 * @Table(name="species")
 */
class Application_Model_Species
{
    /**
     * @var integer
     * @Id 
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var string Globally unique identifier.
     * @Column(name="guid", type="string", length="36")
     */
    private $guid;
    
    /**
     * @var string Full species name.
     * @Column(name="name", type="string", length="128")
     */
    private $name;
    
    /**
     * @var string Species common name.
     * @Column(name="common_name", type="string", length="128")
     */
    private $commonName;
    
    /**
     * @var integer Corresponding NCBI taxonomy ID
     * @Column(name="ncbi_tax_id", type="integer")
     */
    private $ncbiTaxonId;
    
    /**
     * $var array(Application_Model_SpeciesSynonym) List of synonyms for species name.
     * @OneToMany(targetEntity="Application_Model_SpeciesSynonym", mappedBy="species", cascade={"persist"})
     */
    private $synonyms;
    
    
    public function __construct() {
        $this->synonyms = new ArrayCollection();
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getGuid() {
        return $this->guid;
    }

    public function setGuid($guid) {
        $this->guid = (string) $guid;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = (string) $name;
    }

    public function getCommonName() {
        return $this->commonName;
    }

    public function setCommonName($commonName) {
        $this->commonName = (string) $commonName;
    }

    public function getNcbiTaxonId() {
        return $this->ncbiTaxonId;
    }

    public function setNcbiTaxonId($ncbiTaxonId) {
        $this->ncbiTaxonId = (integer) $ncbiTaxonId;
    }

    public function getSynonyms() {
        return $this->synonyms;
    }

    public function addSynonym($synonym) {
        $synonym->setSpecies($this);
        $this->synonyms->add($synonym);
    }
    
    public function fromArray($data) {
        $properties = get_object_vars($this);
        foreach ($data as $key => $value) {
            if ($key == 'synonyms') {
                $this->synonyms = new ArrayCollection();
                foreach ($value as $synonymData) {
                    $synonym = new Application_Model_SpeciesSynonym();
                    $synonym->fromArray($synonymData);
                    $this->addSynonym($synonym);
                }
            } 
            else if (array_key_exists($key, $properties)) {
                $setter = 'set' . ucfirst($key);
                $this->{$setter}($value);
            }
        }
    }
    
    public function toArray() {
        $synonymsData = array();
        foreach ($this->getSynonyms() as $synonym) {
            /* @var $synonym Application_Model_SpeciesSynonym */
            $synonymsData[] = $synonym->toArray();
        }
        $data = array(
            'id' => $this->id,
            'guid' => $this->guid,
            'name' => $this->name,
            'commonName' => $this->commonName,
            'ncbiTaxonId' => $this->ncbiTaxonId,
            'synonyms' => $synonymsData,
        );
        return $data;
    }
    
    /**
     * @PrePersist @PreUpdate
     */
    public function validate() {
        if ($this->guid === null) {
            throw new Application_Exception_ValidateException("Guid required");
        }
    }
}
