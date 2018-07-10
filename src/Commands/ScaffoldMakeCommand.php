<?php

namespace Laralib\L5scaffold\Commands;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laralib\L5scaffold\Makes\MakeController;
use Laralib\L5scaffold\Makes\MakeControllerApi;
use Laralib\L5scaffold\Makes\MakeLayout;
use Laralib\L5scaffold\Makes\MakeLocalization;
use Laralib\L5scaffold\Makes\MakeMigration;
use Laralib\L5scaffold\Makes\MakeModel;
use Laralib\L5scaffold\Makes\MakeProvider;
use Laralib\L5scaffold\Makes\MakeRepository;
use Laralib\L5scaffold\Makes\MakerTrait;
use Laralib\L5scaffold\Makes\MakeSeed;
use Laralib\L5scaffold\Makes\MakeService;
use Laralib\L5scaffold\Makes\MakeView;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ScaffoldMakeCommand extends Command
{
    use DetectsApplicationNamespace, MakerTrait;

    /**
     * The console command name!
     *
     * @var string
     */
    protected $name = 'make:scaffold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a laralib scaffold';

    /**
     * Meta information for the requested migration.
     *
     * @var array
     */
    protected $meta;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Store name from Model
     *
     * @var string
     */
    private $nameModel = "";

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();


        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $header = "scaffolding: {$this->getObjName("Name")}";
        $footer = str_pad('', strlen($header), '-');
        $dump = str_pad('>DUMP AUTOLOAD<', strlen($header), ' ', STR_PAD_BOTH);

        $this->line("\n----------- $header -----------\n");

        $this->makeMeta();
        if ( !$this->options()['migration'] ) {
            $this->makeMigration();
        }

        $this->makeSeed();
        $this->makeModel();
        $this->makeController();
        $this->makeControllerApi();
        $this->makeService();
        $this->makeRepository();
        $this->makeProvider();

//         $this->makeLocalization(); //TODO - implement in future version
        $this->makeViews();
//        $this->makeViewLayout();

        $this->line("\n----------- $footer -----------");
        $this->comment("----------- $dump -----------");

        $this->composer->dumpAutoloads();
        $this->error("Don't forget to adjust: 'migrate' and 'routes'");
    }

    /**
     * Generate the desired migration.
     *
     * @return void
     */
    protected function makeMeta()
    {
        // ToDo - Verificar utilidade...
        $this->meta['action'] = 'create';
        $this->meta['var_name'] = $this->getObjName("name");
        $this->meta['table'] = $this->getObjName("names");//obsoleto

        $this->meta['ui'] = $this->option('ui');

        $this->meta['namespace'] = $this->getAppNamespace();

        $this->meta['Model'] = $this->getObjName('Name');
        $this->meta['Models'] = $this->getObjName('Names');
        $this->meta['model'] = $this->getObjName('name');
        $this->meta['models'] = $this->getObjName('names');
        $this->meta['ModelMigration'] = "Create{$this->meta['Models']}Table";
        $this->meta['prefix'] = ($prefix = $this->option('prefix')) ? "$prefix." : "";

        if ( $this->options()['migration'] ) {
            $this->meta['schema'] = $this->makeSchemaMigration();
        }else{
            $this->meta['schema'] = $this->option('schema');
        }
    }

    protected function makeSchemaMigration(){

        $excludes = array('foreign', 'comment', 'timestamps', 'softDeletes');
        $reflector = new \ReflectionClass($this->argument('name'));
        $fileName = $reflector->getFileName();
        $content = $this->files->get($fileName);

        // Match function up
        $re = '/function\s+up+.*?(\(+.*?\))\s*+({([^}]*)})/m';
        preg_match($re, $content, $matches);
        $functionUp = $matches[0];

        $re = '/\'\s*(.*?)\s*\'/m';
        preg_match($re, $functionUp, $matches);
        $this->meta['var_name'] = $this->camelize($matches[1], '_');

        $this->meta['Model'] = $this->getObjNameMigration('Name');
        $this->meta['Models'] = $this->getObjNameMigration('Names');
        $this->meta['model'] = $this->getObjNameMigration('name');
        $this->meta['models'] = $this->getObjNameMigration('names');

        // Match all columns starts with '$table->'
        $re = '/^\n*\t*\s*\$table-.*;/m';
        preg_match_all($re, $functionUp, $matches);
        $columns = $matches[0];

        $fields = array();
        foreach ($columns as $column){

            // Match all column fields
            $re = '/->\w+/m';
            preg_match_all($re, $column, $matches);
            $types = $matches[0];

            $field = array();
            $exclude = false;
            foreach ($types as $type){

                $type = str_replace('->', '', $type);

                // Match all values
                $re = '/' . $type . '\s*\([^;)]*\)/m'; // Ex: decimal('amount', 5, 2)
                preg_match($re, $column, $matches);
                $values = $matches[0];

                $re = '/' . $type . '\s*\(/';
                $values = str_replace(["'", ")", "]", "["], '', preg_replace($re, '', $values));
                $values = !empty($values) ? array_filter( preg_replace( ['/^\s*/', '/$\s/'], '', explode(',', $values)) ) : null;

                if ( in_array($type, $excludes) ){
                    $exclude = true;
                }

                if ( empty($values) ){
                    if ( !$exclude ) {
                        $field[] = $type;
                    }
                }else if ( count($values) == 1 ){

                    if ( !$exclude ){
                        $field[] = $values[0] . ':' . $type;
                    }

                }else{
                    $name = array_splice($values, 0, 1);
                    $field[] = $name[0] . ':' . $type . '(' . implode(',', $values) . ')';
                }

            }

            if ( !empty($field) ){
                $fields[] = implode(':', $field);
            }
        }

        return implode(',', $fields);
    }

    public function camelize($input, $separator = '_'){
        return str_replace($separator, '', ucwords($input, $separator));
    }


    /**
     * Generate the desired migration.
     *
     * @return void
     */
    protected function makeMigration()
    {
        new MakeMigration($this, $this->files);
    }

    /**
     * Make a Controller with default actions
     *
     * @return void
     */
    private function makeController()
    {
        new MakeController($this, $this->files);
    }

    /**
     * Make a API Controller with default actions
     *
     * @return void
     */
    private function makeControllerApi()
    {
        new MakeControllerApi($this, $this->files);
    }

    /**
     * Make a Service with default actions
     *
     * @return void
     */
    private function makeService()
    {
        new MakeService($this, $this->files);
    }

    /**
     * Make a Repository with default actions
     *
     * @return void
     */
    private function makeRepository()
    {
        new MakeRepository($this, $this->files);
    }

    /**
     * Make a Provider
     *
     * @return void
     */
    private function makeProvider()
    {
        new MakeProvider($this, $this->files);
    }

    /**
     * Make a layout.blade.php with bootstrap
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function makeViewLayout()
    {
        new MakeLayout($this, $this->files);
    }

    /**
     * Generate an Eloquent model, if the user wishes.
     *
     * @return void
     */
    protected function makeModel()
    {
        new MakeModel($this, $this->files);
    }

    /**
     * Generate a Seed
     *
     * @return void
     */
    private function makeSeed()
    {
        new MakeSeed($this, $this->files);
    }

    /**
     * Setup views and assets
     *
     * @return void
     */
    private function makeViews()
    {
        new MakeView($this, $this->files);
    }

    /**
     * Setup the localizations
     */
    private function makeLocalization()
    {
        new MakeLocalization($this, $this->files);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return
        [
            ['name', InputArgument::REQUIRED, 'The name of the model. (Ex: Post)'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return
        [
            [
                'schema',
                's',
                InputOption::VALUE_OPTIONAL,
                'Schema to generate scaffold files. (Ex: --schema="title:string")',
                null
            ],
            [
                'ui',
                'ui',
                InputOption::VALUE_OPTIONAL,
                'UI Framework to generate scaffold. (Default bs3 - bootstrap 3)',
                'bs3'
            ],
            [
                'validator',
                'a',
                InputOption::VALUE_OPTIONAL,
                'Validators to generate scaffold files. (Ex: --validator="title:required")',
                null
            ],
            [
                'localization',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Localizations to generate scaffold files. (Ex. --localization="key:value")',
                null
            ],
            [
                'lang',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Language for Localization (Ex. --lang="en")',
                null,
            ],
            [
                'form',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Use Illumintate/Html Form facade to generate input fields',
                false
            ],
            [
                'prefix',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Generate schema with prefix',
                false
            ],
            [
                'migration',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Generate schema with prefix',
                false
            ]
        ];
    }

    /**
     * Get access to $meta array
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Generate names
     *
     * @param string $config
     * @return mixed
     * @throws \Exception
     */
    public function getObjName($config = 'Name')
    {
        $names = [];

        if ( isset($this->meta['var_name']) && $this->options()['migration'] ){
            $args_name =  $this->meta['var_name'];
        }else{
            $args_name =  $this->argument('name');
        }

        // Name[0] = Tweet
        $names['Name'] = str_singular(ucfirst($args_name));
        // Name[1] = Tweets
        $names['Names'] = str_plural(ucfirst($args_name));
        // Name[2] = tweets
        $names['names'] = str_plural(strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $args_name)));
        // Name[3] = tweet
        $names['name'] = str_singular(strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $args_name)));


        if (!isset($names[$config]))
        {
            throw new \Exception("Position name is not found");
        };

        return $names[$config];
    }

    /**
     * Generate names
     *
     * @param string $config
     * @return mixed
     * @throws \Exception
     */
    public function getObjNameMigration($config = 'Name')
    {
        $names = [];
        $name = $this->getMeta()['var_name'];

        // Name[0] = Tweet
        $names['Name'] = str_singular(ucfirst($name));
        // Name[1] = Tweets
        $names['Names'] = str_plural(ucfirst($name));
        // Name[2] = tweets
        $names['names'] = str_plural(strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $name)));
        // Name[3] = tweet
        $names['name'] = str_singular(strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $name)));


        if (!isset($names[$config]))
        {
            throw new \Exception("Position name is not found");
        };

        return $names[$config];
    }

    public function handle()
    {
        $this->fire();
    }
}
