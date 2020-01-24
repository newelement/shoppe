<?php
namespace Newelement\Shoppe\Commands;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Newelement\Shoppe\Traits\Seedable;
use Newelement\Shoppe\ShoppeServiceProvider;
class InstallCommand extends Command
{
	use Seedable;

	protected $seedersPath = __DIR__.'/../../publishable/database/seeds/';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'shoppe:install';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Neutrino Shoppe package';

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production', null],
            ['with-dummy', null, InputOption::VALUE_NONE, 'Install with dummy data', null],
        ];
    }

    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" '.getcwd().'/composer.phar';
        }
        return 'composer';
    }
    
    public function fire(Filesystem $filesystem)
    {
        return $this->handle($filesystem);
    }

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing the Shoppe assets, database, and config files');
        // Publish only relevant resources on install

        $this->call('vendor:publish', ['--provider' => ShoppeServiceProvider::class]); // , '--tag' => $tags

		$this->info('Migrating the database tables into your application');
        $this->call('migrate', ['--force' => $this->option('force')]);
        $this->info('Dumping the autoloaded files and reloading all new files');

        $composer = $this->findComposer();
        $process = new Process($composer.' dump-autoload');
        $process->setTimeout(null); // Setting timeout to null to prevent installation from stopping at a certain point in time
        $process->setWorkingDirectory(base_path())->run();

		$this->info('Adding Shoppe routes to routes/web.php');

        $routes_contents = $filesystem->get(base_path('routes/web.php'));
        
        if (false === strpos($routes_contents, 'Shoppe::routes()')) {
            
            $newContents = str_replace('Neutrino::routesPublic();', '', $routes_contents);
            $filesystem->put(base_path('routes/web.php'), $newContents);
            
            $filesystem->append(
                base_path('routes/web.php'),
                "\nShoppe::routes();\n"
            );
            
            $filesystem->append(
                base_path('routes/web.php'),
                "\nNeutrino::routesPublic();\n"
            );
        }

        $this->info('Seeding data into the database');
        $this->seed('ShoppeDatabaseSeeder');

        $this->info('Successfully installed Shoppe. Enjoy!');
    }
}
