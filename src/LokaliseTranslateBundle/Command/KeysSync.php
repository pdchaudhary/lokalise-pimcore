<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class KeysSync extends AbstractCommand {

	use \Elements\Bundle\ProcessManagerBundle\ExecutionTrait;
	

    protected function configure()
	{
	    $this
			->setName('lokalise:key-sync')
			->setDescription('Keys Sync Command')
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
			$keyApiService = new KeyApiService();
			$keyApiService->syncUpAllKeysWithDb();
            $monitoringItem->setMessage('Job finished')->setCompleted();
        }catch(\Exception $e) {
            $monitoringItem->setMessage($e->getMessage());
            $monitoringItem->stopProcess();
        }
    }
}