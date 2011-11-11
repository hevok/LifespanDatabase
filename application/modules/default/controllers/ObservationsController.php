<?php

/**
index {html, xml, json}
show {html, xml, json}
add {html}
edit {html}
create {html(set flash, redirect to get/edit), xml, json}
update {html(set flash, redirect to get/edit), xml, json}
delete {html(set flash, redirect to index), xml, json}
 */

class Default_ObservationsController extends Zend_Controller_Action
{
    /**
     * Listing items per page.
     */
    const ITEMS_PER_PAGE = 50;
    
    /**
     * @var Application_Model_ObservationRepository 
     */
    private $repository;
    
    /**
     * @var Application_Model_User
     */
    private $currentUser;
    
    
    public function init() {
        $em = Application_Registry::getEm();
        $this->repository = $em->getRepository('Application_Model_Observation');
        $this->user = Application_Registry::getCurrentUser();
    }
    
    public function listAction() {
        // process search filters
        $params = $this->getRequest()->getParams();
        $filter = new Application_Model_ObservationFilter($params);
        $this->view->filter = $filter;
        
        // set up pager
        $count = $this->repository->getCount($filter);
        $pager = new Application_Pager($count, self::ITEMS_PER_PAGE);
        $pageNumber = $this->_getParam('page', 1);
        $pager->setCurrentPage($pageNumber);
        $this->view->pager = $pager;
        
        // fetch items
        $order = null;
        $limit = self::ITEMS_PER_PAGE;
        $offset = ($pageNumber - 1) * self::ITEMS_PER_PAGE;
        $observations = $this->repository->findBy($filter, $order, $limit, $offset);
        $this->view->observations =  $observations;
        
        $this->view->render('observations/list.json');
    }
    
    public function getAction() {
        $id = $this->getRequest()->getParam('id');
        $observation = $this->repository->findOneBy(array('id' => $id));
        if (!$observation) {
            throw new Zend_Controller_Action_Exception('Observation not found.', 404);
        }
        $this->view->observation = $observation;
    }
    
    public function addAction() {
        $form = new Application_Form_ObservationForm();
        $this->view->form = $form;
    }
    
    // post
    public function createAction() {
        // create form
        $form = new Application_Form_ObservationForm();
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getParams())) {
                $values = $form->getValues();

                // create model
                $observation = new Application_Model_Observation();
                $observation->fromArray($values);

                // validate model
                $validator = new Application_Validate_ObservationValidator();
                if ( ! $validator->isValid($observation)) {
                    $messages = $validator->getMessages();
                    throw Application_Exception_InvalidModel("Observation invalid");
                }

                // save
                $em = Application_Registry::getEm();
                $em->persist($observation);
                $em->flush();

                // redirect to show page
                $url = 'observations/' . $observation->getPublicId();
                $this->_redirect($url);
            }
        }
    }
    
    public function editAction() {
        $id = $this->getRequest()->getParam('id');
        
        
        
    }
    
    // put
    public function updateAction() {
        
        
        
        $form = new Application_Form_ObservationForm();
        
        
    }
    
    public function deleteAction() {
        $message = 'Delete operation not supported.';
        throw new Application_Exception_NotSupportedException($message);
    }
}