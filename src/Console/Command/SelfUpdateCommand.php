<?php

namespace Drutiny\Console\Command;

use Composer\Semver\Comparator;
use Drutiny\Container;
use Drutiny\Credential\Manager;
use Drutiny\Http\Client;
use Drutiny\ProgressBar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Self update command.
 */
class SelfUpdateCommand extends Command
{

    const GITHUB_API_URL = 'https://api.github.com';
    const GITHUB_ACCEPT_VERSION = 'application/vnd.github.v3+json';

  /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
        ->setName('self-update')
        ->setDescription('Update Drutiny by downloading latest phar release.');
    }

  /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $logger = Container::getLogger();
        $current_version = $this->getApplication()->getVersion();

        if (strpos($current_version, 'dev') !== false) {
            $io->warning("Currently using a dev branch. Self-update is not available.");
            return;
        }

        $composer_json = json_decode(file_get_contents(DRUTINY_LIB . '/composer.json'), true);

        $current_script = realpath($_SERVER['SCRIPT_NAME']);
        if (!is_writable($current_script)) {
            $io->error("Cannot write to $current_script. Will not be able to apply update.");
            return;
        }

        $progress = new ProgressBar($output, 4);
        $progress->setTopic("Update");
        $progress->start();

      // Clear the HTTP cache.
        $cache = Container::cache('http');
        $cache->clear();
        $progress->advance();

        $headers = [
        'User-Agent' => 'drutiny-phar/' . $current_version,
        'Accept' => self::GITHUB_ACCEPT_VERSION,
        'Accept-Encoding' => 'gzip',
        ];

        try {
            $creds = Manager::load('github');
            $headers['Authorization'] = 'token ' . $creds['personal_access_token'];
        } catch (\Exception $e) {
        }

        $client = new Client([
        'base_uri' => self::GITHUB_API_URL,
        'headers' => $headers,
        'decode_content' => 'gzip',
        ]);

        $response = $client->get('repos/' . $composer_json['name'] . '/releases');
        $releases = json_decode($response->getBody(), true);
        $progress->advance();

        $latest_release = current($releases);
        $new_version = $latest_release['tag_name'];

        if (!Comparator::greaterThan($new_version, $current_version)) {
            $io->success("No new updates.");
            $progress->finish();
            return;
        }
        $progress->advance();
        $logger->notice('New update available: ' . $new_version);

      // Which one to update? There are two types of releases. The normal one and
      // the testing one. We need to find which one we are and update us with the
      // the right one uploaded.
        $version_bits = explode('-', $current_version);
        $is_testing_version = in_array('testing', $version_bits);

        $release_downloads = array_filter($latest_release['assets'], function ($asset) use ($is_testing_version) {
            $is_testing_asset = strpos($asset['name'], 'testing') !== false;
            return $is_testing_version === $is_testing_asset;
        });

        if (empty($release_downloads)) {
            $io->error("No valid release assets found for $current_version.");
            $progress->finish();
            return;
        }

        $download = reset($release_downloads);

        $tmpfile = tempnam(sys_get_temp_dir(), $download['name']);
        $resource = fopen($tmpfile, 'w');
      // $file_path = fopen(realpath($_SERVER['SCRIPT_NAME']),'w');
        $logger->notice("Downloading {$download['name']}...");

        $response = $client->get('repos/' . $composer_json['name'] . '/releases/assets/' . $download['id'], [
        'headers' => [
        'Accept' => $download['content_type'],
        ],
        ]);

        $progress->advance();

        fwrite($resource, $response->getBody());
        fclose($resource);

        chmod($tmpfile, 0766);

        $logger->notice("New release downloaded to $tmpfile.");
        $progress->finish();

        if (!rename($tmpfile, $current_script)) {
            $logger->error("Could not overwrite $current_script with $tmpfile.");
            return false;
        }
        $io->success("Updated to $new_version.");
    }
}
