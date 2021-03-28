<?php

namespace NPX\FinnishBaseForms;

use Symfony\Component\Process\Process;

class CliLemmatizer extends Lemmatizer {

    public $binaryPath;
    private $locale;
    private static $instance;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new CliLemmatizer();
        }

        return self::$instance;
    }

    public function __construct($binaryPath = 'voikkospell')
    {
        $this->binaryPath = $binaryPath;

        $process = new Process('locale -a | grep -i "utf-\?8"');
        $process->run();
        $this->locale = strtok($process->getOutput(), "\n");
        parent::__construct();
    }

    public function lemmatize(string $word)
    {
        $process = new Process("{$this->binaryPath} -M", null, [
            'LANG' => $this->locale,
            'LC_ALL' => $this->locale,
        ]);
        $process->setInput($word);
        $process->run();

        if ($process->getErrorOutput()) {
            throw new \Exception($process->getErrorOutput());
        }

        preg_match_all('/A\((.*)\).*BASEFORM=(.+)$/m', $process->getOutput(), $all_matches);

        $output = [];
        
        //dump($all_matches);

        for ($i = 0; $i < count($all_matches[0]); $i++) {

            $output_item = [];

            preg_match_all(
                '/A\((.*)\).*:' . ($i + 1) . ':BASEFORM=(.*)$/m',
                $process->getOutput(),
                $baseform_matches
            );
            preg_match_all(
                '/A\((.*)\).*:' . ($i + 1) . ':WORDBASES=(.+)$/m',
                $process->getOutput(),
                $wordbases_matches
            );

            $output_item['baseform'] = $baseform_matches[2][0];

            if (!empty($wordbases_matches)) {
                $output_item['wordbases'] = $this->parse_wordbases([$wordbases_matches[2][0]]);
            }

            if (empty($baseform_matches[1])) {
                break;
            }

            array_push($output, $output_item);
        }

        return $output;
    }
}