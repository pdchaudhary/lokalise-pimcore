<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Command;

use Pdchaudhary\LokaliseTranslateBundle\Controller\DocumentController;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\DocumentHelper;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentSync extends AbstractCommand {

    use \Elements\Bundle\ProcessManagerBundle\ExecutionTrait;

    public function __construct(WorkflowHelper $workflowHelper, DocumentHelper $documentHelper)
    {
        parent::__construct();
        $this->workflowHelper = $workflowHelper;
        $this->documentHelper = $documentHelper;
    }

    protected function configure()
	{

		$this
			->setName('lokalise:document-sync')
			->setDescription('Document Sync Command')
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
            $controller->documentTranslationSync($this->workflowHelper, $this->documentHelper);
            $monitoringItem->setMessage('Job finished')->setCompleted();
        }catch(\Exception $e) {
            $monitoringItem->setMessage($e->getMessage());
            $monitoringItem->stopProcess();
        }
    }
}