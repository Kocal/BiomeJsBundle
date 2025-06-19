<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Command;

use Kocal\BiomeJsBundle\BiomeJsBinary;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'biomejs:download',
    description: 'Download the Biome.js CLI binary for the current platform and architecture.',
)]
final class BiomeJsDownloadCommand extends Command
{
    private SymfonyStyle $io;
    private HttpClientInterface $httpClient;

    public function __construct(
        private string $version,
        private readonly Filesystem $filesystem,
        ?HttpClientInterface $httpClient = null,
    ) {
        parent::__construct();

        $this->version = self::validateAndNormalizeVersion($version);
        $this->httpClient = $httpClient ?? new RetryableHttpClient(HttpClient::create());
    }

    protected function configure(): void
    {
        $this
            ->addArgument('destination-dir', InputArgument::OPTIONAL, 'Destination folder', default: getcwd() . '/bin')
            ->addUsage('./bin')
            ->addUsage('./path/to/bin')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $destinationDir = $input->getArgument('destination-dir');
        if (!is_string($destinationDir)) {
            throw new \InvalidArgumentException('The destination directory must be a string.');
        }

        $this->io->title('Downloading Biome.js CLI...');

        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist.', $destinationDir));
        }

        $binaryName = '\\' === \DIRECTORY_SEPARATOR ? 'biome.exe' : 'biome';
        $binary = Path::join($destinationDir, $binaryName);

        if ($this->filesystem->exists($binary)) {
            $installedVersion = $this->extractVersionFromBinary($binary);

            if ($installedVersion === $this->version) {
                $this->io->success(sprintf('Biome.js CLI version %s is already installed.', $this->version));

                return self::SUCCESS;
            }

            $this->io->warning(sprintf('Biome.js CLI version %s is already installed, but requested version is %s. Replacing it.', $installedVersion, $this->version));
            $this->filesystem->remove($binary);
        }

        $this->downloadBinary($binary);

        $this->io->success(sprintf('Done, you can now run Biome.js CLI natively with "%s".', Path::makeRelative($binary, getcwd())));

        return self::SUCCESS;
    }

    private static function validateAndNormalizeVersion(string $version): string
    {
        if (!preg_match('/^v?\d+\.\d+\.\d+$/', $version)) {
            throw new \InvalidArgumentException(sprintf('Invalid version format "%s". Expected format: "v1.9.4" or "2.0.0".', $version));
        }

        return ltrim($version, 'v');
    }

    private function extractVersionFromBinary(string $binary): string
    {
        $process = new Process([$binary, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Failed to get version from binary: %s', $process->getErrorOutput()));
        }

        // The version format is "Version: 1.2.3"
        if (!preg_match('/Version:\s*(?P<version>\d+\.\d+\.\d+)/', $process->getOutput(), $matches)) {
            throw new \RuntimeException(sprintf('Could not extract version from binary output: %s', $process->getOutput()));
        }

        return trim($matches['version']);
    }

    private function downloadBinary(string $binary): void
    {
        $url = sprintf(
            'https://github.com/biomejs/biome/releases/download/%s/%s',
            version_compare($this->version, '2.0.0', '<') ? 'cli/v' . $this->version : '@biomejs/biome@' . $this->version,
            BiomeJsBinary::getBinaryName(),
        );

        $this->io->note(sprintf('Downloading Biome.js CLI from %s', $url));

        $progressBar = null;

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->io->createProgressBar($dlSize);
                    $progressBar->start();
                }

                $progressBar->setProgress($dlNow);
            },
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(sprintf('Failed to download Biome.js CLI binary, received status code %d.', $response->getStatusCode()));
        }

        $fileHandler = fopen($binary, 'w');
        if (!is_resource($fileHandler)) {
            throw new \RuntimeException(sprintf('Cannot open file "%s" for writing.', $binary));
        }
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);
        chmod($binary, 0777);

        $progressBar?->finish();
        $this->io->newLine(2);
    }
}
