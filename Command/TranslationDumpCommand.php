<?php

/*
 * This file is a customized version of the TranslationUpdateCommand
 * that is delivered with the SymfonyFrameworkBundle .
 */

namespace QBT\TranslationBundle\Command;

use Symfony\Component\Translation\Writer\TranslationWriter;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;

/**
 * A command that parse templates to extract translation messages and add them into the translation files.
 *
 * @author alex
 */
class TranslationDumpCommand extends ContainerAwareCommand
{
    /**
     * Compiled catalogue of messages.
     * @var MessageCatalogue
     */
    protected $catalogue;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('qbt:translation:dump')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale'),
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages'),
                new InputOption(
                    'output-format', null, InputOption::VALUE_OPTIONAL,
                    'Override the default output format', 'yml'
                ),
                new InputOption(
                    'clean', null, InputOption::VALUE_OPTIONAL,
                    'Empty the bundle\'s translation folder before dump translation files', 'true'
                ),
                new InputOption(
                    'dump-messages', null, InputOption::VALUE_NONE,
                    'Should the messages be dumped in the console'
                ),
                new InputOption(
                    'force', null, InputOption::VALUE_NONE,
                    'Should the update be done'
                )
            ))
            ->setDescription('Dumps translation files for a given bundle')
            ->setHelp(<<<EOF
The <info>%command.name%</info> finds all translation strings in templates and dumps the corresponding translation files to the bundle.

<info>php %command.full_name% --dump-messages en AcmeBundle</info>
<info>php %command.full_name% --force fr,en,de AcmeBundle</info>
EOF
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check presence of force or dump-message
        if ($input->getOption('force') !== true && $input->getOption('dump-messages') !== true) {
            $output->writeln('<info>You must choose one of --force or --dump-messages</info>');

            return;
        }

        // check format
        $writer = $this->getContainer()->get('translation.writer');
        $supportedFormats = $writer->getFormats();
        if (!in_array($input->getOption('output-format'), $supportedFormats)) {
            $output->writeln('<error>Wrong output format</error>');
            $output->writeln('Supported formats are '.implode(', ', $supportedFormats).'.');

            return;
        }
        
        if (!$input->getArgument('locale')) {
        	$input->setArgument('locale', 'fr,en');
        }
        
        $locales = explode(',', $input->getArgument('locale'));
        
        if ($input->getArgument('bundle')) {
	        //get bundle directory
        	$foundBundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundle'));
        	foreach ($locales as $locale) {
        		$this->dumpBundleTranslation($writer, $input, $output, $locale, $foundBundle);
        	}
        } else {
        	$bundles = $this->getApplication()->getKernel()->getBundles();
        	foreach ($bundles as $bundle) {
        		foreach ($locales as $locale) {
        			$this->dumpBundleTranslation($writer, $input, $output, $locale, $bundle);
        		}
        	}
        }
    }
    
    private function dumpBundleTranslation(TranslationWriter $writer, InputInterface $input, OutputInterface $output, $locale, $bundle) {
   		$name =  $bundle->getName();
   		$path = $bundle->getPath();
    	if (strpos($path, 'vendor') > 0) {
    		return;
    	}
   		$transPath = $path . '/Resources/translations';
   		$viewPath = $path . '/Resources/views/';
   		if (!file_exists($viewPath)) {
   			return;
   		}
    	
        $output->writeln(sprintf('Generating "<info>%s</info>" translation files for "<info>%s</info>"', $locale, $name));
    	
    	// create catalogue
    	$catalogue = new MessageCatalogue($locale);
    	
    	// load any messages from templates
    	$output->writeln('Parsing templates');
    	$extractor = $this->getContainer()->get('translation.extractor');
    	$extractor->extract($viewPath, $catalogue);
    	if (count($catalogue->getDomains()) == 0) {
    		$output->writeln(sprintf('No translation to dump for bundle "<info>%s</info>"', $name));
    		return;
    	}
    	
    	// load any existing messages from the translation files
    	$globalCatalogue = new MessageCatalogue($locale);
    	$output->writeln('Loading translation files');
    	$this->loadMessagesForCatalogue($globalCatalogue);
    	
    	$output->writeln(sprintf("\nRetrieving all messages from the global catalogue for locale <info>%s</info>:\n", $locale));
    	$resultCatalogue = new MessageCatalogue($locale);
    	$globalDomains = $globalCatalogue->getDomains();
    	foreach ($catalogue->getDomains() as $domain) {
    		if (in_array($domain, $globalDomains)) {
    			$globalMessages = $globalCatalogue->all($domain);
	    		foreach ($catalogue->all($domain) as $id => $translations) {
	    			$resultCatalogue->set($id, $globalMessages[$id], $domain);
	    		}
    		} else {
    			$output->writeln(sprintf("\nThe global catalogue has no entries for domain <info>%s</info>:\n", $domain));
    		}
    	}
    	// show compiled list of messages
    	if ($input->getOption('dump-messages') === true) {
    		foreach ($resultCatalogue->getDomains() as $domain) {
    			$output->writeln(sprintf("\nDisplaying messages for domain <info>%s</info>:\n", $domain));
    			$output->writeln(Yaml::dump($catalogue->all($domain), 10));
    		}
    		if ($input->getOption('output-format') == 'xliff') {
    			$output->writeln('Xliff output version is <info>1.2</info>');
    		}
    	}
    	
    	// save the files
    	if ($input->getOption('force') === true) {
    		if (!file_exists($transPath)) {
    			mkdir($transPath);
    		}
    		if ($input->getOption('clean') === 'true') {
    			$files = scandir($transPath);
    			foreach ($files as $file) {
    				if (is_file($file)) {
    					unlink($file);
    				}
    			}
    		}
    		$output->writeln('Writing files');
    		$writer->writeTranslations($resultCatalogue, $input->getOption('output-format'), array('path' => $transPath));
    	}
    }
    
    private function loadMessagesForCatalogue($catalogue) {
    	$loader = $this->getContainer()->get('translation.loader');
    	$transpath = $this->getContainer()->getParameter('kernel.root_dir') . '/Resources/translations';
    	$loader->loadMessages($transpath, $catalogue);
    }
}
