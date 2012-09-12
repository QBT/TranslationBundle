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
class TranslationExtractCommand extends ContainerAwareCommand
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
            ->setName('qbt:translation:extract')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale'),
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages'),
                new InputOption(
                    'prefix', null, InputOption::VALUE_OPTIONAL,
                    'Override the default prefix', '__'
                ),
                new InputOption(
                    'output-format', null, InputOption::VALUE_OPTIONAL,
                    'Override the default output format', 'yml'
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
            ->setDescription('Extract Translations from template files')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command extract translation strings from templates. It can display them or merge the new ones into the translation files.
When new translation strings are found it can automatically add a prefix to the translation
message.

<info>php %command.full_name% --dump-messages en AcmeBundle</info>
<info>php %command.full_name% --force --prefix="new_" fr,en,de AcmeBundle</info>
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
        		$this->extractAppOrBundleTranslation($writer, $input, $output, $locale, $foundBundle);
        	}
        } else {
        	$bundles = $this->getApplication()->getKernel()->getBundles();
        	array_push($bundles, null);
        	foreach ($bundles as $bundle) {
        		foreach ($locales as $locale) {
        			$this->extractAppOrBundleTranslation($writer, $input, $output, $locale, $bundle);
        		}
        	}
        }
    }
    
    private function extractAppOrBundleTranslation(TranslationWriter $writer, InputInterface $input, OutputInterface $output, $locale, $bundle = null) {
    	
    	if ($bundle === null) {
    		$name = "app";
    		$path = $this->getContainer()->getParameter('kernel.root_dir');
    	} else {
    		$name =  $bundle->getName();
    		$path = $bundle->getPath();
    	}
    	if (strpos($path, 'vendor') > 0) {
    		return;
    	}
   		$transPath = $path . '/Resources/translations';
   		$viewPath = $path . '/Resources/views/';
   		$writePath = $this->getContainer()->getParameter('kernel.root_dir') . '/Resources/translations';
   		if (!file_exists($viewPath)) {
   			return;
   		}
    	
        $output->writeln(sprintf('Generating "<info>%s</info>" translation files for "<info>%s</info>"', $locale, $name));
    	
    	// create catalogue
    	$catalogue = new MessageCatalogue($locale);
    	
    	// load any messages from templates
    	$output->writeln('Parsing templates');
    	$extractor = $this->getContainer()->get('translation.extractor');
    	$extractor->setPrefix($input->getOption('prefix'));
    	$extractor->extract($viewPath, $catalogue);
    	
    	// load any existing messages from the translation files
    	$output->writeln('Loading translation files');
    	$this->loadMessagesForCatalogue($catalogue);
//     	$loader = $this->getContainer()->get('translation.loader');
//     	if (file_exists($transPath)) {
//     		$loader->loadMessages($transPath, $catalogue);
//     	}
    	
    	// show compiled list of messages
    	if ($input->getOption('dump-messages') === true) {
    		foreach ($catalogue->getDomains() as $domain) {
    			$output->writeln(sprintf("\nDisplaying messages for domain <info>%s</info>:\n", $domain));
    			$output->writeln(Yaml::dump($catalogue->all($domain), 10));
    		}
    		if ($input->getOption('output-format') == 'xliff') {
    			$output->writeln('Xliff output version is <info>1.2</info>');
    		}
    	}
    	
    	// save the files
    	if ($input->getOption('force') === true) {
    		$output->writeln('Writing files');
    		$writer->writeTranslations($catalogue, $input->getOption('output-format'), array('path' => $writePath));
    	}
    }
    
    private function loadMessagesForCatalogue($catalogue) {
    	$bundles = $this->getApplication()->getKernel()->getBundles();
	    $loader = $this->getContainer()->get('translation.loader');
    	foreach ($bundles as $bundle) {
    		$path = $bundle->getPath();
    		if (!strpos($path, 'vendor')) {
	    		$transpath = $path . '/Resources/translations';
		    	if (file_exists($transpath)) {
		    		$loader->loadMessages($transpath, $catalogue);
	    		}
    		}
    	}
    	$transpath = $this->getContainer()->getParameter('kernel.root_dir') . '/Resources/translations';
    	$loader->loadMessages($transpath, $catalogue);
    }
}
