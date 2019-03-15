<?php
namespace Qs\La\Tests;

use Encore\Admin\AdminServiceProvider;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase;
use Illuminate\Contracts\Console\Kernel;

class BrowserTestCase extends TestCase {

    use InteractsWithDatabase;

    protected $vendorDir = __DIR__ . '/../vendor/laravel/laravel/vendor/';
    protected $migrationsDir = __DIR__ . '/../vendor/laravel/laravel/database/migrations/';
    protected $baseDir = __DIR__ . '/../vendor/laravel/laravel/';
    protected $testFilesDir = __DIR__ . '/TestFiles/';

    protected $serverProcess;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * 创建 RemoteWebDriver 实例
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions())->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        )
        );
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->register("Encore\Admin\AdminServiceProvider");
        $app->register('Qs\La\Filepond\FilepondServiceProvider');
        return $app;
    }

    public function setUp() : void{
        $this->makeInstalledJson();

        parent::setUp();


        Browser::$storeScreenshotsAt = __DIR__ . '/Browser/screenshots';
        Browser::$storeConsoleLogAt = __DIR__ . '/Browser/console';

        $this->installLaravel();

        $this->installLA();

        $this->installFilepond();

        $this->copyTestFiles();

        $this->runServer();
    }

    public function tearDown() : void
    {
//        $this->uninstallLA();

        $this->uninstallLaravel();

        //$this->clearInstalledJson();

        parent::tearDown();
    }

    protected function copyTestFiles(){
        File::copy($this->testFilesDir . '2019_02_09_131310_create_posts_table.php', $this->migrationsDir . '2019_02_09_131310_create_posts_table.php');
        File::copy($this->testFilesDir . 'app.php', $this->baseDir . 'config/app.php');

        if(!File::isDirectory($this->baseDir . 'app/Models')){
            File::makeDirectory($this->baseDir . 'app/Models');
        }
        File::copy($this->testFilesDir . 'Post.php', $this->baseDir . 'app/Models/Post.php');
        File::copy($this->testFilesDir . 'routes.php', $this->baseDir . 'app/Admin/routes.php');
        FIle::copy($this->testFilesDir . 'FilepondController.php', $this->baseDir . 'app/Admin/Controllers/FilepondController.php');
        FIle::copy($this->testFilesDir . 'filesystems.php', $this->baseDir . 'config/filesystems.php');

        $this->artisan('migrate');
    }

    public function installLaravel(){
        if(!file_exists(base_path('vendor/autoload.php'))){
            $autoload = File::get(__DIR__ . '/../vendor/autoload.php');
            $autoload = str_replace('/composer/autoload_real.php', '/../../../composer/autoload_real.php', $autoload);
            File::put(base_path('vendor/autoload.php'), $autoload);
        }

        File::put(database_path("database.sqlite"), '');

        $env = File::get(base_path('.env.example'));
        $env = str_replace('APP_URL=http://localhost', 'APP_URL=' . $this->app['config']['app.url'], $env);
        $env = str_replace('APP_KEY=', 'APP_KEY=' . $this->app['config']['app.key'], $env);
//        $env = str_replace('DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite' , $env);
//        $env = str_replace('DB_DATABASE=homestead', 'DB_DATABASE=' . database_path("database.sqlite"), $env);
        $env = str_replace('DB_HOST=127.0.0.1', 'DB_HOST=' . $this->app['config']['database.connections.mysql.host'], $env);

        $env = str_replace('DB_USERNAME=homestead', 'DB_USERNAME='. $this->app['config']['database.connections.mysql.username'] , $env);
        $env = str_replace('DB_PASSWORD=secret123', 'DB_PASSWORD=' . $this->app['config']['database.connections.mysql.password'] , $env);
        File::put(base_path('.env'), $env);

//        $this->app['config']->set('database.default', 'sqlite');
//        $this->app['config']->set('database.connections.sqlite.database', database_path('database.sqlite'));

        $this->artisan('storage:link');
    }

    protected function installLA(){
        $this->artisan("vendor:publish", ['--provider' => AdminServiceProvider::class]);

        $this->app['config']->set('admin', require config_path("admin.php"));
        $this->artisan('admin:install');
    }

    protected function installFilepond(){
        $this->artisan("admin:import", ['extension' => 'filepond']);
    }

    protected function uninstallLaravel(){
        $this->artisan("migrate:reset");
        File::deleteDirectory(storage_path('app/public/files'));
    }

    protected function uninstallLA(){
        File::deleteDirectory(app("path.base").DIRECTORY_SEPARATOR . 'app' .  DIRECTORY_SEPARATOR . 'Admin');
        File::delete(app("path.base") . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'admin.php');
        $this->artisan("optimize:clear");

    }

    protected function getPackageProviders($app)
    {
        return [AdminServiceProvider::class];
    }

    protected function makeInstalledJson(){
        $composerPath =$this->vendorDir. 'composer';

        $files = new Filesystem();

        if($files->isDirectory($composerPath) === false){
            $files->makeDirectory($composerPath, 0755, true);
        }

        $files->copy(join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'composer', 'installed.json']),$composerPath . DIRECTORY_SEPARATOR . 'installed.json');
    }

    protected function clearInstalledJson(){
        $files = new Filesystem();
        $files->deleteDirectory(app("path.base") . DIRECTORY_SEPARATOR . 'vendor');
    }

    protected function runServer(){
        $phpBinaryFinder = new \Symfony\Component\Process\PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $host = str_replace('http://', '', $this->app['config']['app.url']);
        $host = str_replace('https://', '', $host);

        chdir(public_path());

        $this->serverProcess = new \Symfony\Component\Process\Process([$phpBinaryPath, '-S', $host, base_path('server.php')]);
        $this->serverProcess->start();
    }

}