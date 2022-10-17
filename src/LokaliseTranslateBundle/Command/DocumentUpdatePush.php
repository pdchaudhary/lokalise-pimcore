<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Command;

use Pdchaudhary\LokaliseTranslateBundle\Controller\DocumentController;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\DocumentHelper;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;

class DocumentUpdatePush extends AbstractCommand {

    use \Elements\Bundle\ProcessManagerBundle\ExecutionTrait;

    public function __construct(WorkflowHelper $workflowHelper, DocumentHelper $documentHelper,KeyApiService $keyApiService)
    {
        parent::__construct();
        $this->workflowHelper = $workflowHelper;
        $this->documentHelper = $documentHelper;
        $this->keyApiService = $keyApiService;
    }

    protected function configure()
	{

		$this
			->setName('lokalise:document-update-push')
			->setDescription('Document Update Sync Command')
            ->addArgument('documentId', InputArgument::REQUIRED, 'documentId Required.')
            ->addOption(
                'monitoring-item-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Contains the monitoring item if executed via the Pimcore backend'
            )
            ;
	}

    function execute(InputInterface $input, OutputInterface $output){
        $monitoringItem = $this->initProcessManager($input->getOption('monitoring-item-id'),['autoCreate' => true]);
        try{
            $controller = new DocumentController();
            $documentId = $input->getArgument('documentId');
            $controller->pushDocumentKeyOnUpdate($documentId, $this->keyApiService, $this->documentHelper, $this->workflowHelper);
            $monitoringItem->setMessage('Job finished')->setCompleted();
        }catch(\Exception $e) {
            $monitoringItem->setMessage($e->getMessage());
            $monitoringItem->stopProcess();
        }
        return 1;
    }
}