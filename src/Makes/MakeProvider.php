<?php
namespace Laralib\L5scaffold\Makes;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Laralib\L5scaffold\Commands\ScaffoldMakeCommand;
use Laralib\L5scaffold\Validators\SchemaParser as ValidatorParser;
use Laralib\L5scaffold\Validators\SyntaxBuilder as ValidatorSyntax;


class MakeProvider
{
    use DetectsApplicationNamespace, MakerTrait;

    /**
     * Store name from Model
     *
     * @var ScaffoldMakeCommand
     */
    protected $scaffoldCommandObj;

    /**
     * Create a new instance.
     *
     * @param ScaffoldMakeCommand $scaffoldCommand
     * @param Filesystem $files
     * @return void
     */
    function __construct(ScaffoldMakeCommand $scaffoldCommand, Filesystem $files)
    {
        $this->files = $files;
        $this->scaffoldCommandObj = $scaffoldCommand;

        $this->start();
    }

    /**
     * Start make provider.
     *
     * @return void
     */
    private function start()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'ServiceProvider';
        $path = $this->getPath($name, 'provider');

        if ($this->files->exists($path)) 
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileProviderStub());

        $this->scaffoldCommandObj->info('+ Provider');
    }

    /**
     * Compile the provider stub.
     *
     * @return string
     */
    protected function compileProviderStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/provider.stub');
        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);

        return $stub;
    }
}