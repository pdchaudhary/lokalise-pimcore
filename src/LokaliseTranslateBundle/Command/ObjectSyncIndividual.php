<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Command;

use Pdchaudhary\LokaliseTranslateBundle\Controller\ObjectController;
use Pdchaudhary\LokaliseTranslateBundle\Service\ObjectHelper;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ObjectSyncIndividual extends AbstractCommand {

    use \Elements\Bundle\ProcessManagerBundle\ExecutionTrait;

    public function __construct(ObjectHelper $objectHelper)
    {
        parent::__construct();
        $this->objectHelper = $objectHelper;
    }

    protected function configure()
	{
	    $this
			->setName('lokalise:object-sync-individual')
			->setDescription('Objects Sync individual Command')
            ->addArgument('objectKey', InputArgument::REQUIRED, 'objectKey Required.')
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
            $controller = new ObjectController();
            $objectKey = $input->getArgument('objectKey');
            $controller->objectTranslationSync($this->objectHelper,$objectKey);
            $monitoringItem->setMessage('Job finished')->setCompleted();
        }catch(\Exception $e) {
            $monitoringItem->setMessage($e->getMessage());
            $monitoringItem->stopProcess();
        }
        return 1;
    }
}