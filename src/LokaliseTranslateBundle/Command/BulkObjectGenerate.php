<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Command;

use Pdchaudhary\LokaliseTranslateBundle\Controller\ObjectController;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\ObjectHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BulkObjectGenerate extends AbstractCommand {

    use \Elements\Bundle\ProcessManagerBundle\ExecutionTrait;

    public function __construct(WorkflowHelper $workflowHelper, ObjectHelper $objectHelper,KeyApiService $keyApiService)
    {
        parent::__construct();
        $this->workflowHelper = $workflowHelper;
        $this->objectHelper = $objectHelper;
        $this->keyApiService = $keyApiService;
    }

    protected function configure()
	{

		$this
			->setName('lokalise:bulk-objects-push')
			->setDescription('Bulk objects push Command')
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
            $workloadIds = $this->objectHelper->getObjectsIds();
            $monitoringItem->setCurrentWorkload(0)->setTotalWorkload(count($workloadIds))->setMessage('Starting process')->save();
            $objectController = new ObjectController();
            foreach($workloadIds as $i => $item){
                $objectController->pushToLokalise($item, $this->keyApiService,$this->workflowHelper);
                $monitoringItem->setMessage('Processing Object Id: ' .$item)->setCurrentWorkload($i+1)->save();
               
            }
            $monitoringItem->setMessage('Job finished')->setCompleted();
        }catch(\Exception $e) {
            $monitoringItem->setMessage($e->getMessage());
            $monitoringItem->stopProcess();
        }
    }
}