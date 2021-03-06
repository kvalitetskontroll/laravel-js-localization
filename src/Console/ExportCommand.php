<?php
namespace JsLocalization\Console;

use Config;
use Illuminate\Console\Command;
use File;
use JsLocalization\Exceptions\ConfigException;
use JsLocalization\Facades\ConfigCachingService;
use JsLocalization\Facades\MessageCachingService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'js-localization:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Refresh message cache and export to static files";

    /**
     * Execute the console command.
     *
     * @return void
     * @throws ConfigException
     */
    public function handle()
    {
        $this->line('Refreshing and exporting the message cache...');

        $locales = Config::get('js-localization.locales');

        if(!is_array($locales)) {
          throw new ConfigException('Please set the "locales" config! See https://github.com/andywer/laravel-js-localization#configuration');
        }

        MessageCachingService::refreshCache();
        $messagesFilePath = $this->createPath('messages.js');
        $this->generateMessagesFile($messagesFilePath);

        ConfigCachingService::refreshCache();
        $configFilePath = $this->createPath('config.js');
        $this->generateConfigFile($configFilePath);
    }
    
    /**
     * Execute the console command.
     * Compatibility with previous Laravel 5.x versions.
     *
     * @return void
     * @throws ConfigException
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Create full file path.
     * This method will also generate the directories if they don't exist already.
     *
     * @var string $filename
     *
     * @return string $path
     */
    public function createPath($filename)
    {
        $dir = Config::get('js-localization.storage_path');
        if (!is_dir($dir)) {
            mkdir($dir, '0777', true);
        }

        return $dir . $filename;
    }

    /**
     * Generate the messages file.
     *
     * @param string $path
     */
    public function generateMessagesFile($path)
    {
        $messages = MessageCachingService::getMessagesJson();

        $contents  = 'Lang.addMessages(' . $messages . ');';

        File::put($path, $contents);

        $this->line("Generated $path");
    }

    /**
     * Generage the config file.
     *
     * @param string $path
     */
    public function generateConfigFile($path)
    {
        $config = ConfigCachingService::getConfigJson();
        if ($config === '{}') {
            $this->line('No config specified. Config not written to file.');
            return;
        }

        $contents = 'Config.addConfig(' . $config . ');';

        File::put($path, $contents);

        $this->line("Generated $path");
    }
}
