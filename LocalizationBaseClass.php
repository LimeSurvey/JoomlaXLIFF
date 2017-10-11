<?php

/**
 * @since 2017-05-18
 * @author Olle Haerstedt
 */
class LocalizationBaseClass extends JApplicationCli
{
    /**
     * Array of array, latest XLF files (one or many).
     * @var array
     */
    protected $latestXlfs = [];

    /**
     * Loaded from json config file.
     * @var StdClass
     */
    protected $config = array();

    public function __construct(JInputCli $input = null, JRegistry $config = null, JDispatcher $dispatcher = null)
    {
        require_once __DIR__ . '/XliffDocument.php';

        parent::__construct($input, $config, $dispatcher);

        $this->setupConfig();
    }

    /**
     * @return void
     */
    protected function setupConfig()
    {
        $configFileName = $this->input->getString('config', null);

        if (empty($configFileName)) {
            printf(
                'ERROR: No config file specified. Use --config=en2de.hjson or sim.' . PHP_EOL
            );
            $this->close(3);
        }

        if (!file_exists(__DIR__ . '/' . $configFileName)) {
            printf(
                'ERROR: Found no such config file: %s' . PHP_EOL,
                $configFileName
            );
            $this->close(2);
        }

        try {
            $parser = new HJSON\HJSONParser();
            $this->config = $parser->parse(file_get_contents(__DIR__ . '/' . $configFileName));

            $this->checkLanguages();
            $this->updateLanguageConfig();
        } catch (Exception $ex) {
            printf('Error during setup: ' . $ex->getMessage() . PHP_EOL);
            $this->close(1);
        }
    }

    /**
     * Fetch language data from database.
     * @return void
     */
    protected function updateLanguageConfig()
    {
        $srcLang = $this->getLanguage($this->config->sourceLanguage);
        $targetLang = $this->getLanguage($this->config->targetLanguage);

        $this->config->sourceLanguage = new StdClass();
        $this->config->sourceLanguage->id = $srcLang->lang_id;
        $this->config->sourceLanguage->code = $srcLang->lang_code;

        $this->config->targetLanguage = new StdClass();
        $this->config->targetLanguage->id = $targetLang->lang_id;
        $this->config->targetLanguage->code = $targetLang->lang_code;
    }

    /**
     *  Check so languages are set in config.
     *  @return void
     */
    protected function checkLanguages()
    {
        if (empty($this->config->sourceLanguage)) {
            throw new Exception('Missing source language');
        }

        // Check language from command-line.
        $cliTargetLang = $this->input->getString('targetLang', null);

        if ($cliTargetLang) {
            $this->config->targetLanguage = $cliTargetLang;
        }

        if (empty($this->config->targetLanguage)) {
            throw new Exception('Missing target language');
        }
    }

    /**
     * @param string $code Like 'en-GB'
     * @return StdClass
     */
    protected function getLanguage($code)
    {
        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true);
        $query
            ->select('*')
            ->from('#__languages')
            ->where('lang_code = ' . $dbo->quote($code));
        $result = $dbo->setQuery($query)->loadObject();

        if (empty($result)) {
            throw new Exception('Could not find language with code ' . $code);
        }

        return $result;
    }

    /**
     * @param StdClass $fileConfig
     * @return string
     */
    protected function getSourceFile(StdClass $fileConfig)
    {
        return sprintf(
            $fileConfig->repository->source,
            $this->config->sourceLanguage->code
        );
    }

    /**
     * @param StdClass $fileConfig
     * @return string
     */
    protected function getTargetFile(StdClass $fileConfig)
    {
        return sprintf(
            $fileConfig->repository->target,
            $this->config->targetLanguage->code
        );
    }

    /**
     * Fetch latest XLF content, if given argument --latestXlf.
     * Puts result in this->latestXlfx array, like [filename => [id => content, ...], ...], where
     * id = e.g. "3.content.title".
     * @return void
     */
    protected function getLatestXlf()
    {
        $filenames = explode(',', $this->getFilenames());
        foreach ($filenames as $filename) {
            if (!empty($filename)) {
                $content = file_get_contents(__DIR__ . '/' . $filename); $xml = simplexml_load_string($content);

                if (!$xml) {
                    throw new Exception('latestXlf file could not be parsed as XML');
                }

                foreach ($xml as $body) {
                    foreach ($body as $transUnits) {
                        foreach ($transUnits as $transUnit) {
                            $attributes = $transUnit->attributes();
                            $this->latestXlfs[$filename][(string) $attributes['id']] = (string) $transUnit->source;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getFilenames()
    {
        $argument = $this->input->getString('latestXlf', null);

        if (empty($argument)) {
            $argument = $this->input->getString('files', null);
        }

        if (empty($argument)) {
            $argument = $this->input->getString('file', null);
        }

        return $argument;
    }

    /**
     * @param XliffDocument $xliff
     * @param string $filename
     * @return XliffDocument
     */
    protected function addXliffFile(XliffDocument $xliff, $filename)
    {
        $xliff
            ->file(true)   // Create new <file>
            ->setAttribute('source-language', $this->config->sourceLanguage->code)
            ->setAttribute('target-language', $this->config->targetLanguage->code)
            ->setAttribute('original', $filename)
            ->setAttribute('datatype', 'plaintext')
            ->setAttribute('date', date('Y-m-d\TH:i:s'))
            ->body(true);  // Create new <body>
        return $xliff->file();
    }

    /**
     * @param XliffDocument $xliff
     * @param string $unitId
     * @param string $text
     * @param string $nbillSource 'file' or 'database', depending on source inside nBill.
     * @param string $translation
     * @return void
     */
    protected function addXliffUnit(XliffFile $xliffFile, $unitId, $text, $nbillSource, $translation = null)
    {
        $xliffFile
            ->body()
            ->unit(true)
            ->setAttribute('id', $unitId)
            ->setAttribute('nbill-source', $nbillSource)
            ->source(true)
            ->setTextContent($text);

        if ($translation) {
            $xliffFile
                ->body()
                ->unit()
                ->target(true)
                ->setTextContent($translation)
                ->setAttribute('xml:space', 'preserve')
                ->setAttribute('state', 'translated');
        } else {
            $xliffFile
                ->body()
                ->unit()
                ->target(true)
                ->setTextContent($text)
                ->setAttribute('xml:space', 'preserve')
                ->setAttribute('state', 'needs-translation');
        }
    }
}
