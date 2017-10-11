<?php

/**
 * Aux class for writing translations to nBill language files.
 * Each instance of this class represents one line to write or
 * overwrite.
 */
class NbillWriteToFile
{
    /**
     * @var string
     */
    protected $fileLocation = null;

    /**
     * @var string
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $define = null;

    /**
     * @var string
     */
    protected $text = null;

    /**
     * @var string
     */
    protected $original = null;

    public function setFileLocation($fl)
    {
        $this->fileLocation = $fl;
    }

    public function getFileLocation()
    {
        return $this->fileLocation;
    }

    /**
     * Will replace " with \", since nBill language files MUST
     * use double-quote around strings.
     * @param string $text
     * @return void
     */
    public function setText($text)
    {
        if (strpos('"', $text) !== false) {
            $text = str_replace('"', '\"', $text);
        }
        $this->text = $text;
    }

    public function setOriginal($original)
    {
        $this->original = $original;
    }

    public function setDefine($def)
    {
        $this->define = $def;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Write this write object to file.
     * Assumes file is small enough to be kept in RAM at once.
     * @return array [result, error message]
     */
    public function write()
    {
        if (!file_exists($this->fileLocation)) {
            return [false, 'File does not exist: ' . $this->fileLocation];
        }

        $file = file($this->fileLocation);
        if (!$file) {
            return [false, 'File is empty or cannot be read'];
        }

        $that = $this;
        // Iterate each line to find which one to replace.
        $file = array_map(
            function($line) use ($that) {
                if (strstr($line, $this->define)) {
                    return sprintf(
                        'define("%s", "%s");' . PHP_EOL,
                        $this->define,
                        $this->text
                    );
                } else {
                    return $line;
                }
            },
            $file
        );
        $result = file_put_contents($this->fileLocation, implode('', $file));

        if ($result) {
            return [true, null];
        } else {
            return [false, 'Could not save file'];
        }
    }

    public function __toString()
    {
        return sprintf(
            'Found id %s' . PHP_EOL
            . '  Original: %s' . PHP_EOL
            . '  Translation: %s' . PHP_EOL
            . '  File location: %s' . PHP_EOL
            . '  Define: %s' . PHP_EOL,
            $this->id,
            $this->original,
            $this->text,
            $this->fileLocation,
            $this->define
        );
    }

    public function toStringShort()
    {
        return $this->define . ': ' . $this->text . PHP_EOL;
    }
}
