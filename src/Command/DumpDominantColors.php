<?php

namespace Akuma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpDominantColors extends Command
{
    protected function configure()
    {
        parent::configure();

        $imagesFolder = implode(DIRECTORY_SEPARATOR, [dirname(dirname(__DIR__)), 'images']);
        $this
            ->setName('akuma:colors')
            ->addArgument('imagesFolder', InputArgument::OPTIONAL, '', $imagesFolder);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $ex = new \GetMostCommonColors();

        $imagesFolder = $input->getArgument('imagesFolder');

        if (!is_dir($imagesFolder) || !is_readable($imagesFolder)) {
            $output->writeln(sprintf('<error>Unable to access folder "%s"</error>', $imagesFolder));

            return 1;
        }

        $table = new Table($output);
        $table->setHeaders(['FileName', 'DominantColor(HEX)', 'DominantColor(Value)', 'Percentage']);
        $cnt = 0;
        $rows = [];
        foreach (new \DirectoryIterator($imagesFolder) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
            $image = $fileInfo->getPathname();
            if (stripos(mime_content_type($image), 'image') !== false) {
                $data = $ex->Get_Color($image, 1);
                $colors = array_keys($data);
                $rows[] = [
                    'fileName' => $fileInfo->getFilename(),
                    'color' => reset($colors),
                    'value' => hexdec(reset($colors)),
                    'percentage' => reset($data),
                ];
                $cnt++;
            }
        }

        usort($rows, function ($a, $b) {
            if ($a['value'] == $b['value']) {
                if ($a['percentage'] == $b['percentage']) {
                    return 0;
                }

                return $a['percentage'] < $b['percentage'] ? 1 : -1;
            }

            return $a['value'] < $b['value'] ? 1 : -1;
        });
        $table->setRows($rows);
        $table->render();

        $output->writeln(sprintf(
            'Processed "%d" images for "%.2f" seconds',
            $cnt,
            (microtime(true) - $startTime)
        ));

        return 0;
    }
}
