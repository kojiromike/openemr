<?php

declare(strict_types=1);

/**
 * CCDAEventsSubscriber.php  Listens to events to retrieve, generate, manipulate CCD-A documents.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Discover and Change <snielson@discoverandchange.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Carecoordination\Listener;

use Carecoordination\Model\CcdaGenerator;
use Carecoordination\Model\CcdaGlobalsConfiguration;
use Carecoordination\Model\CcdaUserPreferencesTransformer;
use DOMDocument;
use HTML_TreeNode;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\PatientDocuments\PatientDocumentCreateCCDAEvent;
use OpenEMR\Events\PatientDocuments\PatientDocumentTreeViewFilterEvent;
use OpenEMR\Services\CDADocumentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OpenEMR\Events\PatientDocuments\PatientDocumentViewCCDAEvent;
use XSLTProcessor;

class CCDAEventsSubscriber implements EventSubscriberInterface
{
    private \Carecoordination\Model\CcdaGenerator $ccdaGenerator;

    /**
     * @var string The url that users will be sent to inside OpenEMR to view a CCDA
     */
    private string $viewCcdaUrl;

    public function __construct(CcdaGenerator $generator)
    {
        $this->ccdaGenerator = $generator;
        $this->viewCcdaUrl = $GLOBALS['webroot'] . "/interface/modules/zend_modules/public/encountermanager/previewDocument";
    }

    public static function getSubscribedEvents()
    {
        return [
            PatientDocumentCreateCCDAEvent::EVENT_NAME_CCDA_CREATE => 'onCCDACreateEvent',
            PatientDocumentViewCCDAEvent::EVENT_NAME => 'onCCDAViewEvent',
            GlobalsInitializedEvent::EVENT_HANDLE => 'setupUserGlobalSettings',
            PatientDocumentTreeViewFilterEvent::EVENT_NAME => 'onPatientDocumentTreeViewFilter'
        ];
    }

    /**
     * Receives an event request to generate a ccda document.  Generates the document and then stores it back in the
     * event for consumers to use.
     */
    public function onCCDACreateEvent(PatientDocumentCreateCCDAEvent $patientDocumentCreateCCDAEvent): PatientDocumentCreateCCDAEvent
    {
        $dates = [];
        if ($patientDocumentCreateCCDAEvent->getDateFrom() instanceof \DateTime) {
            $dates['date_start'] = $patientDocumentCreateCCDAEvent->getDateFrom()->format("Y-m-d H:i:s");
            $dates['filter_content'] = true;
        }

        if ($patientDocumentCreateCCDAEvent->getDateTo() instanceof \DateTime) {
            $dates['date_end'] = $patientDocumentCreateCCDAEvent->getDateTo()->format("Y-m-d H:i:s");
            $dates['filter_content'] = true;
        }

        try {
            $result = $this->ccdaGenerator->generate(
                $patientDocumentCreateCCDAEvent->getPid(),
                null,
                '',
                false,
                false,
                false,
                $patientDocumentCreateCCDAEvent->getComponentsAsString(),
                $patientDocumentCreateCCDAEvent->getSectionsAsString(),
                '',
                [], // params appears to be used for the informationRecipient pieces, so we leaves this alone
                $patientDocumentCreateCCDAEvent->getDocumentType(),
                '',
                $dates
            );

            // the generator just returns the content...
            $cdaDocumentService = new CDADocumentService();
            $cdaResult = $cdaDocumentService->search(['id' => $result->getId()]);
            if ($cdaResult->hasData()) {
                $patientDocumentCreateCCDAEvent->setCcdaId($result->getId());
                $fileUrl = $cdaResult->getData()[0]['ccda_data'];
                $patientDocumentCreateCCDAEvent->setFileUrl($fileUrl);
            }
        } catch (\Exception $exception) {
            (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()
                , 'pid' => $patientDocumentCreateCCDAEvent->getPid(), 'components' => $patientDocumentCreateCCDAEvent->getComponentsAsString(), 'sections' => $patientDocumentCreateCCDAEvent->getSectionsAsString()
                , 'from' => $patientDocumentCreateCCDAEvent->getDateFrom(), 'to' => $patientDocumentCreateCCDAEvent->getDateTo()]);
        }

        return $patientDocumentCreateCCDAEvent;
    }

    /**
     * When a CCDA is viewed in the system (in the module or outside of it), grab the CCDA and transform it based upon
     * the user's ccda display preferences.
     */
    public function onCCDAViewEvent(PatientDocumentViewCCDAEvent $patientDocumentViewCCDAEvent): PatientDocumentViewCCDAEvent
    {
        try {
            // transform the xml content
            $ccdaGlobalsConfiguration = new CcdaGlobalsConfiguration();

            // user preferences can truncate, sort, etc so we handle those here
            if (!$patientDocumentViewCCDAEvent->shouldIgnoreUserPreferences()) {
                $ccdaUserPreferencesTransformer = new CcdaUserPreferencesTransformer(
                    $ccdaGlobalsConfiguration->getMaxSections(),
                    $ccdaGlobalsConfiguration->getSectionDisplayOrder()
                );
                $updatedContent = $ccdaUserPreferencesTransformer->transform($patientDocumentViewCCDAEvent->getContent());
            } else {
                $updatedContent = $patientDocumentViewCCDAEvent->getContent();
            }

            $type = $patientDocumentViewCCDAEvent->getCcdaType();

            $format = $patientDocumentViewCCDAEvent->getFormat();
            if ($format === 'html') {
                // time to use our stylesheets
                $stylesheet = dirname(__FILE__) . "/../../../../../public/xsl/";

                // from original ccr/display.php code
                if ($type === 'CCR') {
                    $stylesheet .= "ccr.xsl";
                } elseif ($type === "CCD") {
                    $stylesheet .= "cda.xsl";
                }

                if (!file_exists($stylesheet)) {
                    throw new \RuntimeException("Could not find stylesheet file at location: " . $stylesheet);
                }

                $xmlDom = new DOMDocument();
                $xmlDom->loadXML($updatedContent);
                $ss = new DOMDocument();
                $ss->load($stylesheet);
                $xsltProcessor = new XSLTProcessor();
                $xsltProcessor->importStylesheet($ss);
                $patientDocumentViewCCDAEvent->setStylesheetPath($stylesheet);
                $updatedContent = $xsltProcessor->transformToXml($xmlDom);
            }

            $patientDocumentViewCCDAEvent->setContent($updatedContent);
            return $patientDocumentViewCCDAEvent;
        } catch (\Exception $exception) {
            (new SystemLogger())->errorLogCaller($exception->getMessage(), ['trace' => $exception->getTraceAsString()
                , 'documentId' => $patientDocumentViewCCDAEvent->getDocumentId(), 'ccdaId' => $patientDocumentViewCCDAEvent->getCcdaId(), 'type' => $patientDocumentViewCCDAEvent->getCcdaType()]);
        }

        return $patientDocumentViewCCDAEvent;
    }

    /**
     * When the global configuration is initialized setup our CCDA specific settings
     */
    public function setupUserGlobalSettings(GlobalsInitializedEvent $globalsInitializedEvent): void
    {
        $globalsService = $globalsInitializedEvent->getGlobalsService();
        $ccdaGlobalsConfiguration = new CcdaGlobalsConfiguration();
        $ccdaGlobalsConfiguration->setupGlobalSections($globalsService);
    }

    public function onPatientDocumentTreeViewFilter(PatientDocumentTreeViewFilterEvent $patientDocumentTreeViewFilterEvent): PatientDocumentTreeViewFilterEvent
    {
        if ($patientDocumentTreeViewFilterEvent->getHtmlTreeNode() != null) {
            $categoryInfo = $patientDocumentTreeViewFilterEvent->getCategoryInfo();
            // we are going to setup our onclick event to launch our
            // TODO: do we want to look at our LOINC codes here as that seems to be more accurate than if we went with just names...
            if (in_array(strtoupper(trim($categoryInfo['name'] ?? "")), ["CCR","CCDA","CCD"])) {
                $htmlNode = $patientDocumentTreeViewFilterEvent->getHtmlTreeNode();
                $url = $this->viewCcdaUrl . "?docId=" . attr_url($patientDocumentTreeViewFilterEvent->getDocumentId());
                $htmlNode->events = [
                    'onClick' => "javascript:newwindow=window.open('" . $url . "','_blank');"
                ];
            }
        }

        return $patientDocumentTreeViewFilterEvent;
    }
}
