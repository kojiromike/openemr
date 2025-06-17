<?php

declare(strict_types=1);

/**
 * interface/modules/zend_modules/module/Application/src/Application/Controller/IndexController.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Remesh Babu S <remesh@zhservices.com>
 * @copyright Copyright (c) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Application\Listener\Listener;

class IndexController extends AbstractActionController
{
    protected \Application\Model\ApplicationTable $applicationTable;

    protected \Application\Listener\Listener $listenerObject;

    public function __construct(\Application\Model\ApplicationTable $applicationTable)
    {
        $this->listenerObject = new Listener();
        $this->applicationTable = $applicationTable;
    }

    public function indexAction(): void
    {
        // you can uncomment this to test the index action.
        // $request  = $this->getRequest();
        // $message  = $request->getPost()->msg;
        // $array    = array('msg' => "test message");
        // $return   = new JsonModel($array);
        // return $return;
    }

     /**
     * Function ajaxZXL
     * All JS Mesages to xl Translation
     */
    public function ajaxZxlAction(): \Laminas\View\Model\JsonModel
    {
        $request  = $this->getRequest();
        $message  = $request->getPost()->msg;
        $array    = array('msg' => $this->listenerObject->z_xl($message));
        return new JsonModel($array);
    }

    /**
     * Table Gateway
     *
     * @return type
     */
    public function getApplicationTable()
    {
        return $this->applicationTable;
    }

    /**
     * Search Mechanism
     * Auto Suggest
     *
     * @return string
     */
    public function searchAction()
    {
        $this->getRequest();
        return $this->forward()->dispatch(IndexController::class, array(
                                                      'action' => 'auto-suggest'
                                                 ));
    }

    public function autoSuggestAction(): \Laminas\View\Model\ViewModel
    {
        $request      = $this->getRequest();
        $post         = $request->getPost();
        $keyword      = $request->getPost()->queryString;
        $page         = $request->getPost()->page;
        $searchType   = $request->getPost()->searchType;
        $searchEleNo  = $request->getPost()->searchEleNo;
        $searchMode   = $request->getPost()->searchMode;
        $limit        = 20;
        $result       = $this->getApplicationTable()->listAutoSuggest($post, $limit);
      /** disable layout **/
        $viewModel        = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setVariables(array(
                                        'result'        => $result,
                                        'keyword'       => $keyword,
                                        'page'          => $page,
                                        'searchType'    => $searchType,
                                        'searchEleNo'   => $searchEleNo,
                                        'searchMode'    => $searchMode,
                                        'limit'         => $limit,
                                        'CommonPlugin'  => $this->CommonPlugin(),
                                        'listenerObject' => $this->listenerObject,
                                    ));
        return $viewModel;
    }
}
