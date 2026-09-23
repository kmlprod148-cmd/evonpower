<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class TranslateLangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:lang {source} {target}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate language files from source to target language using LibreTranslate API.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceLang = $this->argument('source');
        $targetLang = $this->argument('target');
        $langPath = lang_path();

        $sourcePath = "{$langPath}/{$sourceLang}";
        $targetPath = "{$langPath}/{$targetLang}";

        if (!File::isDirectory($sourcePath)) {
            $this->error("Source language directory '{$sourcePath}' does not exist.");
            return Command::FAILURE;
        }

        if (!File::isDirectory($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
            $this->info("Created target language directory: '{$targetPath}'");
        }

        $files = File::files($sourcePath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $sourceContent = require $file->getPathname();
            $translatedContent = [];

            $this->info("Translating file: {$fileName} from {$sourceLang} to {$targetLang}");

            foreach ($sourceContent as $key => $value) {
                if (is_string($value)) {
                    $response = Http::post('https://libretranslate.com/translate', [
                        'q' => $value,
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'format' => 'text'
                    ]);

                    if ($response->successful()) {
                        $translatedText = $response->json()['translatedText'];
                        $translatedContent[$key] = $translatedText;
                        $this->line("  '{$key}' => '{$translatedText}'");
                    } else {
                        $this->warn("  Failed to translate key '{$key}': " . $response->body());
                        $translatedContent[$key] = $value; // Keep original if translation fails
                    }
                } else {
                    $translatedContent[$key] = $value; // Keep non-string values as is
                }
            }

            $targetFileContent = "<?php\n\nreturn " . var_export($translatedContent, true) . ";\n";
            File::put("{$targetPath}/{$fileName}", $targetFileContent);
            $this->info("Translated content written to: {$targetPath}/{$fileName}");
        }

        $this->info("Translation process completed for '{$sourceLang}' to '{$targetLang}'.");
        return Command::SUCCESS;
    }
}